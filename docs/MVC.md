# MVC in Scrum

Scrum follows the MVC architecture from the CSE470 lecture:

```
Browser → Router → Controller → Model → Database → Model → Controller → View → Browser
```

| Layer | Folder | Responsibility |
|---|---|---|
| Router | `routes/web.php` | Matches each URL + HTTP method to `Controller@action` |
| Controller | `app/Http/Controllers` | Handles the request, checks permissions, validates, calls the model, picks the view |
| Model | `app/Models` | Data and data rules; all database queries |
| View | `resources/views` | HTML/CSS shown to the user, filled with values from the controller |
| Database | MariaDB (`database/migrations` define the tables) | Stores the data |

Helpers that belong to the controller layer:

| Helper | Folder | Used for |
|---|---|---|
| Form Requests | `app/Http/Requests` | Validation rules for a form, run before the controller action. Checks that need the database call a model method (e.g. `Project::nameTakenInTeam()`) |
| Policies | `app/Policies` | Permission rules ("can this user delete this team?") |
| View composer | `app/Http/ViewComposers/LayoutComposer.php` | Prepares the data every page's layout needs (navigation links, current user, success message), so layout views don't read the session or the logged-in user themselves |

## Rules every file follows

- **Routes** point to `Controller@action`. No closures, no shortcut routes.
- **Controllers** never build queries; they call model methods. They read the session
  and decide permissions, then pass plain values and flags (`$canUpdate`) to the view.
- **Models** hold every database query and data rule.
- **Views** only display what they receive. No queries, no session, no logged-in-user
  or permission checks.

`tests/Feature/MvcArchitectureTest.php` checks these rules on every test run and names the file
and line that breaks them.

## Whole-project map

| Area | Routes | Controller | Model | Views |
|---|---|---|---|---|
| Landing page | `GET /` | `HomeController@index` | — | `welcome` |
| Dashboard | `GET /dashboard` | `DashboardController@index` | `User` | `dashboard` |
| Login, register, password reset, email verification | `routes/auth.php` | `Auth/*Controller` | `User` | `auth/*` |
| Profile | `/profile` | `ProfileController` | `User` | `profile/*` |
| F02 Teams & invites | `/teams`, `/teams/{team}/members`, `/teams/{team}/invitations`, `/invitations/{token}` | `TeamController`, `TeamMemberController`, `TeamInvitationController` | `Team`, `TeamMember`, `TeamInvitation`, `User` | `teams/*`, `invitations/*` |
| F03 Projects | `/projects` | `ProjectController` | `Project`, `Team`, `User` | `projects/*` |

Logging in and sending password-reset emails use Laravel's built-in auth and password
services (`Auth::attempt`, `Password::sendResetLink`), which read users through the `User` model.

## F02 Team formation and invites

| Layer | Files |
|---|---|
| Routes | `routes/web.php`, section F02 (13 routes) |
| Controllers | `TeamController`, `TeamMemberController`, `TeamInvitationController` |
| Models | `Team`, `TeamMember` (the `team_members` row), `TeamInvitation`, `User`; enums `TeamRole`, `Role` |
| Views | `resources/views/teams/*`, `resources/views/invitations/show.blade.php` |
| Validation | `StoreTeamRequest`, `UpdateTeamRequest`, `UpdateTeamMemberRequest`, `StoreTeamInvitationRequest` |
| Permissions | `TeamPolicy` (view, update, delete, invite, inviteWithRole, changeRole, removeMember) |
| Tables | `teams`, `team_members` (user ↔ team with role), `team_invitations` |

## F03 Project management

| Layer | Files |
|---|---|
| Routes | `routes/web.php`, section F03 (7 routes) |
| Controller | `ProjectController` |
| Models | `Project` (belongs to one `Team`), enum `ProjectStatus` |
| Views | `resources/views/projects/*` (+ the projects section of `teams/show`) |
| Validation | `StoreProjectRequest`, `UpdateProjectRequest` |
| Permissions | `ProjectPolicy` (view, create, createInTeam, update, assignLead, delete) |
| Tables | `projects` (`team_id`, `lead_id`) |

### Tracing one request: `GET /teams/1?status=active`

1. **Browser** sends `GET /teams/1?status=active`.
2. **Router** matches `GET /teams/{team}` → `TeamController@show`
   and loads team 1 from the database (route model binding).
3. **Controller** `TeamController::show()` checks `Gate::authorize('view', $team)`
   (rule in `TeamPolicy::view`: the user must be a member, or an admin).
4. **Controller → Model**: calls `$team->filteredProjects(null, 'active')`, `$team->membersByRole()`
   and `$team->activeInvitations()`.
5. **Model → Database**: `Team::filteredProjects()` builds
   `SELECT * FROM projects WHERE team_id = 1 AND status = 'active' ORDER BY due_date IS NULL, due_date`.
6. **Database → Model**: rows come back as `Project` objects (with their leads loaded).
7. **Model → Controller**: the projects, members and invite links are returned.
8. **Controller → View**: `view('teams.show', [...])` with the team, projects, members (each with
   `canChangeRole` / `canRemove` flags) and page flags (`$canInvite`, `$canCreateProject`, …).
9. **View** `teams/show.blade.php` renders the HTML.
10. **Browser** receives `200 OK` with the page.

Writes follow the same path, e.g. `POST /invitations/{token}/accept` → `TeamInvitationController@accept`
→ `TeamInvitation::acceptFor($user)` locks the link row, checks it is still usable, adds the member and
counts the use → redirect to `GET /teams/{id}`.
