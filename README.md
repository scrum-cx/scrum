# Scrum

A web-based project management application for managers, team members and administrators.
Users form teams through invite links, run several projects per team, manage tasks on a
To Do / Doing / Done board, chat, schedule meetings, share versioned files, get deadline
warnings, and submit weekly check-ins and peer ratings. Designed for desktop browsers.

CSE470 Software Engineering · Fall 2026 · Section 6 · Group 7

## Team and features

| # | Feature | Owner | Status |
|---|---|---|---|
| F01 | Onboarding page: checklist, welcome notes, pending tasks | Safwan Noor | |
| F02 | Team formation and invites: teams with roles (owner/manager/member), invite links | Arnab Sen | |
| F03 | Project management: projects under a single team, several managed at once | Sadman Hasan | |
| F04 | Manager dashboard and admin analytics: team health, inactive-member alerts, workload trends | Sadman Hasan | |
| F05 | Employee personal dashboard and To Do / Doing / Done board | Safwan Noor | |
| F06 | Communication: messaging, media, polls | Arnab Sen | |
| F07 | Contribution tracking and exportable PDF report | Sadman Hasan | |
| F08 | Meetings: scheduler, in-app team call, meeting notes | Safwan Noor | |
| F09 | File sharing and versioning | Arnab Sen | |
| F10 | Email and in-app notifications, deadline collision warnings | Sadman Hasan | |
| F11 | Weekly check-ins and end-of-project peer rating | Safwan Noor | |
| F12 | Support requests | Arnab Sen | |
| F13 | AI individual and team weekly summaries | Safwan Noor | |
| F14 | Google Workspace integration (Docs, Sheets) | Sadman Hasan | |
| F15 | Payment gateway for the optional paid plan | Arnab Sen | |

## Technology stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework (MVC) | Laravel 12 |
| Views | Blade templates, Tailwind CSS 3, Alpine.js |
| Authentication scaffolding | Laravel Breeze (Blade) |
| Database | MariaDB 10.4+ (bundled with XAMPP) |
| Build tool | Vite |
| Package managers | Composer, npm |

**MVC in this project:** Routes in `routes/web.php` map each URL to `Controller@action`.
Controllers in `app/Http/Controllers` handle requests, check permissions and validate input.
Models in `app/Models` hold the data rules and every database query. Views in
`resources/views` render the HTML from values the controller passes. See
[docs/MVC.md](docs/MVC.md) for the layer map and a request traced step by step, and
[docs/STRUCTURE.md](docs/STRUCTURE.md) for the folder structure, where the database is, and
where each feature's files go.

## Requirements

| Tool | Version |
|---|---|
| PHP | 8.2 or newer, with `zip`, `gd`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| Composer | 2.x |
| Node.js / npm | 20 or newer |
| MariaDB / MySQL | MariaDB 10.4+ (XAMPP) |
| Git | any recent version |

## Setup

### Windows (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) with PHP 8.2 into `C:\xampp`.
2. Clone the repo:
   ```powershell
   git clone https://github.com/scrum-cx/scrum.git
   cd scrum
   ```
3. Install / check the tools (Git, Node.js, Composer, PHP extensions). Run once per PC:
   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts\setup-machine.ps1
   ```
4. Start **MySQL** in the XAMPP Control Panel, then prepare the project:
   ```powershell
   powershell -ExecutionPolicy Bypass -File scripts\setup-project.ps1
   ```

### Linux (Ubuntu, cloud sessions)

```bash
bash scripts/setup-machine.sh
bash scripts/setup-project.sh
```

`setup-project` installs packages, creates `.env` from `.env.example`, creates the `scrum`
database, runs migrations and seeders, and builds the front-end. It is safe to re-run.

**After pulling changes that alter existing tables** (the script stops with "table already exists"),
rebuild the database. This deletes its data and reloads the demo data:
`scripts\setup-project.ps1 -Fresh` (Windows) or `bash scripts/setup-project.sh --fresh` (Linux),
or simply `php artisan migrate:fresh --seed`.

## Running

```bash
php artisan serve
```

Open http://127.0.0.1:8000 and log in with a demo account (password `password` for all):

| Email | Site role | Teams |
|---|---|---|
| `admin@example.com` | Admin | Sees and manages every team and project |
| `owner@example.com` | Member | Owner of Platform Team |
| `manager@example.com` | Member | Manager in Platform Team, owner of Design Team |
| `test@example.com` | Member | Member of Platform Team and Design Team |
| `dev@example.com` | Member | Member of Platform Team, owner of Research Guild |

Each demo team also has an active invite link (shown on the team page to its owner/managers).

**Roles.** Site-wide a user is an *Admin* or a *Member*. Inside each team they have a team role:

| Team role | Can do |
|---|---|
| Owner (one per team) | Everything in the team: edit/delete the team, change roles, hand over ownership, remove anyone, invite managers or members, manage projects |
| Manager | Edit the team, invite members, remove plain members, create/edit/delete the team's projects |
| Member | See the team and its projects; edit a project they lead |

New sign-ups are site members. To make someone an admin, use
`php artisan tinker` → `$u = App\Models\User::firstWhere('email', '...'); $u->role = 'admin'; $u->save();`

While editing views or CSS, also run `npm run dev` in a second terminal for live reload.

| Task | Command |
|---|---|
| Run tests | `php artisan test` |
| Reset database with demo data | `php artisan migrate:fresh --seed` |
| List routes | `php artisan route:list` |

## Configuration and secrets

Database settings in `.env.example` match XAMPP defaults (`root`, no password, database
`scrum`). API keys (Google, payment sandbox, AI) go in your own `.env` only. **Never commit
`.env`**: this repository is public.

## Third-party packages and services

| Package / service | Purpose |
|---|---|
| Laravel Breeze | Login, registration and profile scaffolding |
| Tailwind CSS, Alpine.js | Styling and small interactive UI behaviour |

This list grows as features are added (e.g. PDF export, Google APIs, payment gateway).
