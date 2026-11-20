<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;

/**
 * Validation for editing a project. The team can't be changed;
 * only team owners/managers may change the lead.
 */
class UpdateProjectRequest extends StoreProjectRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    public function rules(): array
    {
        $canAssignLead = $this->user()->can('assignLead', $this->route('project'));

        return [
            'name' => ['required', 'string', 'max:120', $this->uniqueNameInTeam()],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'lead_id' => $canAssignLead
                ? ['nullable', 'integer', $this->leadIsTeamMember()]
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'lead_id.prohibited' => 'Only the team owner or a manager can change the project lead.',
        ];
    }

    protected function teamId(): int
    {
        return $this->route('project')->team_id;
    }

    protected function currentProjectId(): ?int
    {
        return $this->route('project')->id;
    }
}
