<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Enums\TeamRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }


    /** Is this email already used by another account? */
    public static function emailTaken(string $email, ?int $exceptUserId = null): bool
    {
        return static::where('email', $email)
            ->when($exceptUserId, fn ($q) => $q->whereKeyNot($exceptUserId))
            ->exists();
    }

    // ---- Teams ----

    /** Teams this user belongs to; each has ->pivot->role (TeamRole). */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->using(TeamMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** [team id => TeamRole] for every team the user is in. */
    public function teamRoles(): array
    {
        return $this->teams()->get(['teams.id'])
            ->mapWithKeys(fn (Team $team) => [$team->id => $team->pivot->role])
            ->all();
    }

    /** Teams where the user can create and manage projects (owner/manager; admins: all teams). */
    public function manageableTeams(): Collection
    {
        if ($this->isAdmin()) {
            return Team::orderBy('name')->get(['id', 'name']);
        }

        return $this->teams()
            ->wherePivotIn('role', [TeamRole::Owner->value, TeamRole::Manager->value])
            ->orderBy('name')
            ->get(['teams.id', 'teams.name']);
    }

    public function managesAnyTeam(): bool
    {
        return $this->isAdmin() || $this->manageableTeams()->isNotEmpty();
    }

    public function canManageTeamId(int $teamId): bool
    {
        return $this->manageableTeams()->contains('id', $teamId);
    }

    /** Projects this user leads. */
    public function ledProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'lead_id');
    }
}
