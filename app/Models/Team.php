<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * MODEL: a team. Users join teams (many-to-many, with a TeamRole each);
 * every project belongs to one team.
 * All database queries and data rules about teams live here.
 */
class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // ---- Relationships ----

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->using(TeamMember::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Queries used by controllers ----

    /**
     * Teams the user can see (admins see all), with member and project counts,
     * and `my_role` set to the user's role in each team (null for admins who aren't members).
     */
    public static function visibleTo(User $user): Collection
    {
        $teams = static::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('members', fn ($m) => $m->whereKey($user->id)))
            ->withCount(['members', 'projects'])
            ->orderBy('name')
            ->get();

        $roles = $user->teamRoles();
        $teams->each(fn (Team $team) => $team->setAttribute('my_role', $roles[$team->id] ?? null));

        return $teams;
    }

    /** Members ordered owner → managers → members, then by name. Each has ->pivot->role. */
    public function membersByRole(): \Illuminate\Support\Collection
    {
        return $this->members()->orderBy('name')->get()
            ->sortBy(fn (User $u) => $u->pivot->role->rank())
            ->values();
    }

    /** Id/name pairs of the team's members, for the project-lead drop-down. */
    public function memberOptions(): Collection
    {
        return $this->members()->orderBy('name')->get(['users.id', 'users.name']);
    }

    /** This team's projects, optionally filtered by search term and status. */
    public function filteredProjects(?string $search, ?string $status): Collection
    {
        return $this->projects()
            ->with('lead')
            ->search($search)
            ->status($status)
            ->orderByRaw('due_date IS NULL, due_date')
            ->get();
    }

    /** Invite links that can still be used, newest first. */
    public function activeInvitations(): Collection
    {
        return $this->invitations()
            ->with('creator')
            ->usable()
            ->latest()
            ->get();
    }

    public function roleOf(User $user): ?TeamRole
    {
        return TeamMember::where('team_id', $this->id)->where('user_id', $user->id)->first()?->role;
    }

    public static function teamHasMember(int $teamId, int $userId): bool
    {
        return TeamMember::where('team_id', $teamId)->where('user_id', $userId)->exists();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->whereKey($userId)->exists();
    }

    public function hasProjects(): bool
    {
        return $this->projects()->exists();
    }

    public function activeProjectsCount(): int
    {
        return $this->projects()->where('status', ProjectStatus::Active)->count();
    }

    // ---- Changes (data rules) ----

    /** Create a team and make $owner its owner. */
    public static function createWithOwner(array $attributes, User $owner): self
    {
        return DB::transaction(function () use ($attributes, $owner) {
            $team = new static($attributes);
            $team->created_by = $owner->id;
            $team->save();
            $team->members()->attach($owner->id, ['role' => TeamRole::Owner]);

            return $team;
        });
    }

    public function addMember(User $user, TeamRole $role): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role]]);
    }

    /**
     * Change a member's role. A team always has exactly one owner, so making someone
     * the owner turns the current owner into a manager (ownership transfer).
     */
    public function changeRole(User $member, TeamRole $role): void
    {
        DB::transaction(function () use ($member, $role) {
            if ($role === TeamRole::Owner) {
                $currentOwnerIds = $this->members()->wherePivot('role', TeamRole::Owner->value)->pluck('users.id');
                foreach ($currentOwnerIds as $ownerId) {
                    $this->members()->updateExistingPivot($ownerId, ['role' => TeamRole::Manager]);
                }
            }

            $this->members()->updateExistingPivot($member->id, ['role' => $role]);
        });
    }

    /** Remove a member. Projects they were leading in this team lose their lead. */
    public function removeMember(User $member): void
    {
        DB::transaction(function () use ($member) {
            $this->projects()->where('lead_id', $member->id)->update(['lead_id' => null]);
            $this->members()->detach($member->id);
        });
    }
}
