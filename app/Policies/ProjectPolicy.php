<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

/**
 * Permission rules for projects.
 * Team members see their team's projects. Team owners/managers create, edit and delete them.
 * The project lead can edit their own project (but not reassign the lead or delete it).
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->team->roleOf($user) !== null;
    }

    /** Can create projects in at least one team. */
    public function create(User $user): bool
    {
        return $user->managesAnyTeam();
    }

    /** Can create projects in this particular team. */
    public function createInTeam(User $user, Team $team): bool
    {
        return $user->isAdmin() || ($team->roleOf($user)?->canManage() ?? false);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->manage($user, $project) || $project->lead_id === $user->id;
    }

    /** Choosing the project lead is for team owners/managers only. */
    public function assignLead(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->manage($user, $project);
    }

    private function manage(User $user, Project $project): bool
    {
        return $this->createInTeam($user, $project->team);
    }
}
