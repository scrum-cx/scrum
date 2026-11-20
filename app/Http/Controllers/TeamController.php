<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TeamRole;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CONTROLLER for F02 teams: list, create, view (members, projects, invite links), edit, delete.
 */
class TeamController extends Controller
{
    /** GET /teams */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Team::class);

        return view('teams.index', [
            'teams' => Team::visibleTo($request->user()),
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    /** GET /teams/create */
    public function create(): View
    {
        Gate::authorize('create', Team::class);

        return view('teams.create', ['team' => new Team]);
    }

    /** POST /teams — the creator becomes the owner. */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = Team::createWithOwner($request->validated(), $request->user());

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team created. Share an invite link to add people.');
    }

    /** GET /teams/{team} */
    public function show(Request $request, Team $team): View
    {
        Gate::authorize('view', $team);

        $user = $request->user();
        $filters = $request->only('search', 'status');
        $canInvite = $user->can('invite', $team);

        $members = $team->membersByRole()->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->pivot->role,
            'joined' => $member->pivot->created_at,
            'isMe' => $member->is($user),
            'canChangeRole' => $user->can('changeRole', [$team, $member]),
            'canRemove' => $user->can('removeMember', [$team, $member]),
        ]);

        return view('teams.show', [
            'team' => $team,
            'myRole' => $team->roleOf($user),
            'members' => $members,
            'projects' => $team->filteredProjects($filters['search'] ?? null, $filters['status'] ?? null),
            'hasProjects' => $team->hasProjects(),
            'statuses' => ProjectStatus::cases(),
            'filters' => $filters,
            'invitations' => $canInvite ? $team->activeInvitations() : collect(),
            'inviteRoles' => array_values(array_filter(
                TeamRole::invitable(),
                fn (TeamRole $role) => $user->can('inviteWithRole', [$team, $role]),
            )),
            'expiryDays' => TeamInvitation::EXPIRY_DAYS,
            'teamRoles' => TeamRole::cases(),
            'canUpdate' => $user->can('update', $team),
            'canDelete' => $user->can('delete', $team),
            'canInvite' => $canInvite,
            'canCreateProject' => $user->can('createInTeam', [Project::class, $team]),
        ]);
    }

    /** GET /teams/{team}/edit */
    public function edit(Team $team): View
    {
        Gate::authorize('update', $team);

        return view('teams.edit', ['team' => $team]);
    }

    /** PUT /teams/{team} */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()->route('teams.show', $team)->with('success', 'Team updated.');
    }

    /** DELETE /teams/{team} */
    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', "Team \"{$team->name}\" deleted.");
    }
}
