<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a project (controller layer).
 * Database checks are done by asking the models.
 */
class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', $this->manageableTeam()],
            'name' => ['required', 'string', 'max:120', $this->uniqueNameInTeam()],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'lead_id' => ['nullable', 'integer', $this->leadIsTeamMember()],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'The due date cannot be before the start date.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /** The team the project belongs to (chosen in the form when creating). */
    protected function teamId(): int
    {
        return (int) $this->input('team_id');
    }

    /** Id of the project being edited (none when creating). */
    protected function currentProjectId(): ?int
    {
        return null;
    }

    private function manageableTeam(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! $this->user()->canManageTeamId((int) $value)) {
                $fail('You can only create projects in teams you own or manage.');
            }
        };
    }

    /** Project names are unique within their team. */
    protected function uniqueNameInTeam(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if ($this->teamId() && Project::nameTakenInTeam((string) $value, $this->teamId(), $this->currentProjectId())) {
                $fail('This team already has a project with this name.');
            }
        };
    }

    /** The project lead must belong to the project's team. */
    protected function leadIsTeamMember(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if ($this->teamId() && ! Team::teamHasMember($this->teamId(), (int) $value)) {
                $fail('The project lead must be a member of the team.');
            }
        };
    }
}
