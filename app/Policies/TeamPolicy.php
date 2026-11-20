<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Permission rules for teams, their members and invite links.
 * Admins can do everything except leave a team they aren't in.
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Members of the team (and admins) can open it. */
    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->roleOf($user) !== null;
    }

    /** Anyone signed in can start a team; they become its owner. */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin() || ($team->roleOf($user)?->canManage() ?? false);
    }

    /** Only the owner (or an admin), and only once the team has no projects. */
    public function delete(User $user, Team $team): Response
    {
        if (! $user->isAdmin() && $team->roleOf($user) !== TeamRole::Owner) {
            return Response::deny('Only the team owner can delete the team.');
        }

        return $team->hasProjects()
            ? Response::deny('Delete this team\'s projects first.')
            : Response::allow();
    }

    /** Owners and managers can create and revoke invite links. */
    public function invite(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    /** Which role an invite link may give: managers can only invite members. */
    public function inviteWithRole(User $user, Team $team, TeamRole $role): bool
    {
        if ($role === TeamRole::Owner || ! $this->invite($user, $team)) {
            return false;
        }

        return $role === TeamRole::Member || $user->isAdmin() || $team->roleOf($user) === TeamRole::Owner;
    }

    /** Only the owner (or an admin) changes roles, and not their own. */
    public function changeRole(User $user, Team $team, User $member): bool
    {
        if ($user->is($member)) {
            return false;
        }

        return $user->isAdmin() || $team->roleOf($user) === TeamRole::Owner;
    }

    /**
     * Removing someone from the team:
     * - anyone may leave, except the owner (they must hand ownership over first);
     * - the owner (or an admin) may remove anyone but the owner;
     * - a manager may remove plain members.
     */
    public function removeMember(User $user, Team $team, User $member): bool
    {
        $memberRole = $team->roleOf($member);
        if ($memberRole === null || $memberRole === TeamRole::Owner) {
            return false;
        }

        if ($user->is($member)) {
            return true;
        }

        $myRole = $team->roleOf($user);

        return $user->isAdmin()
            || $myRole === TeamRole::Owner
            || ($myRole === TeamRole::Manager && $memberRole === TeamRole::Member);
    }
}
