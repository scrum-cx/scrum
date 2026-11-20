<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CONTROLLER for F03 project management.
 * Every project belongs to one team; the project list shows the projects of all
 * the user's teams together so several projects can be managed at once.
 */
class ProjectController extends Controller
{
    /** GET /projects */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Project::class);

        $user = $request->user();
        $filters = $request->only('search', 'status', 'team');

        return view('projects.index', [
            'projects' => Project::listFor($user, $filters),
            'statusCounts' => Project::statusCountsFor($user),
            'teams' => Team::visibleTo($user),
            'statuses' => ProjectStatus::cases(),
            'filters' => $filters,
            'hasFilters' => array_filter($filters) !== [],
            'canCreate' => $user->can('create', Project::class),
        ]);
    }

    /** GET /projects/create?team={id} */
    public function create(Request $request): View
    {
        Gate::authorize('create', Project::class);

        $user = $request->user();
        $teams = $user->manageableTeams();

        $project = new Project(['status' => ProjectStatus::Planning, 'lead_id' => $user->id]);
        $project->team_id = $teams->contains('id', $request->integer('team')) ? $request->integer('team') : $teams->first()?->id;

        return view('projects.create', [
            'project' => $project,
            'teams' => $teams,
            'statuses' => ProjectStatus::cases(),
            'canAssignLead' => true,
            // Lead choices per team, so the form can show the right people when the team changes.
            'leadOptions' => $teams->mapWithKeys(fn (Team $team) => [
                $team->id => $team->memberOptions()->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values(),
            ]),
        ]);
    }

    /** POST /projects */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $team = $request->user()->manageableTeams()->firstWhere('id', (int) $data['team_id']);
        unset($data['team_id']);

        $project = Project::createInTeam($team, $data, $request->user());

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    /** GET /projects/{project} */
    public function show(Request $request, Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.show', [
            'project' => $project->loadDetails(),
            'canUpdate' => $request->user()->can('update', $project),
            'canDelete' => $request->user()->can('delete', $project),
        ]);
    }

    /** GET /projects/{project}/edit */
    public function edit(Request $request, Project $project): View
    {
        Gate::authorize('update', $project);

        $canAssignLead = $request->user()->can('assignLead', $project);
        $members = $project->team->memberOptions()->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])->values();

        return view('projects.edit', [
            'project' => $project->loadDetails(),
            'teams' => collect([$project->team]),
            'statuses' => ProjectStatus::cases(),
            'canAssignLead' => $canAssignLead,
            'leadOptions' => [$project->team_id => $members],
        ]);
    }

    /** PUT /projects/{project} */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    /** DELETE /projects/{project} */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $team = $project->team;
        $project->delete();

        return redirect()->route('teams.show', $team)
            ->with('success', "Project \"{$project->name}\" deleted.");
    }
}
