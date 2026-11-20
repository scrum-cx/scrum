<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODEL: a project. Each project belongs to exactly one team.
 * All database queries about projects live here; controllers call these methods.
 */
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'status', 'start_date', 'due_date', 'lead_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    // ---- Relationships ----

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ---- Queries used by controllers ----

    /**
     * Projects of every team the viewer belongs to (admins: all projects), filtered by
     * optional search, status and team id, soonest deadline first (undated last).
     */
    public static function listFor(User $viewer, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return static::query()
            ->visibleTo($viewer)
            ->with(['team', 'lead'])
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->when((int) ($filters['team'] ?? 0), fn ($q, $id) => $q->where('team_id', $id))
            ->orderByRaw('due_date IS NULL, due_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Number of visible projects per status, e.g. ['active' => 3, 'planning' => 1, ...]. */
    public static function statusCountsFor(User $viewer): array
    {
        $counts = static::query()->visibleTo($viewer)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])
            ->all();
    }

    /** Is this name already used by another project in the same team? */
    public static function nameTakenInTeam(string $name, int $teamId, ?int $exceptId = null): bool
    {
        return static::where('team_id', $teamId)
            ->where('name', $name)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();
    }

    /** Load what the project detail page shows. */
    public function loadDetails(): static
    {
        return $this->load(['team', 'lead', 'creator']);
    }

    // ---- Changes ----

    /** Create a project in $team. */
    public static function createInTeam(Team $team, array $attributes, User $creator): self
    {
        $project = new static($attributes);
        $project->team_id = $team->id;
        $project->created_by = $creator->id;
        $project->save();

        return $project;
    }

    // ---- Data rules ----

    /** Past its due date and not completed. */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->status !== ProjectStatus::Completed
            && $this->due_date->isBefore(today());
    }

    // ---- Query scopes ----

    /** Only projects in teams the user belongs to (admins see everything). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereIn('team_id', $user->teams()->select('teams.id'));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return ProjectStatus::tryFrom((string) $status)
            ? $query->where('status', $status)
            : $query;
    }
}
