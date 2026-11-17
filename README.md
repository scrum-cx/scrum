# Scrum

A web-based project management application for managers, team members and administrators.
Users create departments and projects, form teams through invite links, manage tasks on a
To Do / Doing / Done board, chat, schedule meetings, share versioned files, get deadline
warnings, and submit weekly check-ins and peer ratings. Designed for desktop browsers.

CSE470 Software Engineering · Fall 2026 · Section 6 · Group 7

## Team and features

| # | Feature | Owner |
|---|---|---|
| F01 | Onboarding page: checklist, welcome notes, pending tasks | Safwan Noor |
| F02 | Department and project management | Sadman Hasan |
| F03 | Team formation and invite links, with roles | Arnab Sen |
| F04 | Manager dashboard and admin analytics: team health, inactive-member alerts, workload trends | Sadman Hasan |
| F05 | Employee personal dashboard and To Do / Doing / Done board | Safwan Noor |
| F06 | Communication: messaging, media, polls | Arnab Sen |
| F07 | Contribution tracking and exportable PDF report | Sadman Hasan |
| F08 | Meetings: scheduler, in-app team call, meeting notes | Safwan Noor |
| F09 | File sharing and versioning | Arnab Sen |
| F10 | Email and in-app notifications, deadline collision warnings | Sadman Hasan |
| F11 | Weekly check-ins and end-of-project peer rating | Safwan Noor |
| F12 | Support requests | Arnab Sen |
| F13 | AI individual and team weekly summaries | Safwan Noor |
| F14 | Google Workspace integration (Docs, Sheets) | Sadman Hasan |
| F15 | Payment gateway for the optional paid plan | Arnab Sen |

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

**MVC in this project:** Models in `app/Models` hold data and data rules (Eloquent).
Views in `resources/views` render Blade templates. Controllers in `app/Http/Controllers`
handle requests, validate input (Form Requests) and pass data between models and views.
Routes live in `routes/web.php`.

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

## Running

```bash
php artisan serve
```

Open http://127.0.0.1:8000 and log in with the demo account `test@example.com` / `password`.

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
