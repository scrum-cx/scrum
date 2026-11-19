# Scrum — project instructions

CSE470 project (Laravel 12, Blade, Tailwind, Alpine, MariaDB via XAMPP).
Domain: users join one or more teams (role per team: owner/manager/member); each project belongs to one team.
Site-wide roles are only admin/member. The course requires
strict MVC as taught in the "4.2 MVC Architecture" lecture. The **entire project** must follow it,
including the Breeze login/profile code. `tests/Feature/MvcArchitectureTest.php` enforces the rules;
it must always pass (`php artisan test`).

## MVC rules (from the lecture)

Request flow: Browser → Router → Controller → Model → Database → Model → Controller → View → Browser.

- **Routes** (`routes/web.php`, `routes/auth.php`): write every route explicitly as
  `METHOD /url → Controller@action`, grouped and commented by feature (e.g. `// ---- F05 Task board ----`).
  No closures, no `Route::resource` / `Route::view` / `Route::redirect`.
- **Models** (`app/Models`): all data logic and **every database query** (select/insert/update/delete,
  filters, counts, sorting, pagination). Expose them as named methods/scopes
  (e.g. `Project::listFor($user, $filters)`, `$team->filteredProjects(...)`, `User::manageableTeams()`).
  Data rules such as `isOverdue()` live here too.
- **Controllers** (`app/Http/Controllers`, one per resource, e.g. `ProjectController`): receive the request,
  authorize (`Gate::authorize` + policies in `app/Policies`), validate (Form Requests in `app/Http/Requests`),
  call model methods, apply business rules, and pass **ready-made values** to a view
  (`return view('projects.show', [...])`). No query building in controllers, form requests or policies.
  Validation that needs the database calls a model method from a closure rule
  (e.g. `Project::nameTakenInTeam()`, `User::emailTaken()`), never `unique:`/`exists:`/`Rule::unique`.
  Controllers read the session (flash/status) and pass the result to the view.
- **Views** (`resources/views/<resource>/`, shared layout in `resources/views/layouts`): HTML/CSS only.
  Display what the controller passed. No queries, no `@can`, no `auth()`/`Auth::`, no `session()`,
  no `request()`/`$request`, no `Route::has`, no `instanceof`, no business decisions — the controller
  passes flags such as `$canUpdate`, `$canAssignLead`. (`old()`, `route()`, `$errors`, `@csrf` are fine.)
  Reading attributes and display helpers of passed models (`$project->status->label()`) is fine.
- **Shared layout data** (navigation links, current user, success banner) comes from
  `app/Http/ViewComposers/LayoutComposer.php` (registered in `AppServiceProvider`). Add new top-level
  pages to its `NAV_LINKS`.
- A controller may have several views (index/show/create/edit + `_form` partials).

## Conventions

- Enums in `app/Enums`; Tailwind scans them, so badge classes can live there.
- Flash messages: `->with('success', '...')` (the `status` key is used by Breeze profile pages).
- `role` on users is not mass-assignable; set it explicitly.
- Every feature gets feature tests in `tests/Feature` (permissions, validation, normal + invalid cases).
- Demo data in `database/seeders/DatabaseSeeder.php` must stay re-runnable.
- Put each feature's files where `docs/STRUCTURE.md` plans them (section 3), and update that table
  (and the "Current tables" list) when a feature is built or a name changes.
- Keep `README.md` (feature status, demo accounts, packages), `docs/MVC.md` and `docs/STRUCTURE.md` up to date.
