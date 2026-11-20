# Project file structure

Where everything in Scrum lives, where the database is, and where each feature's files go.
For *why* code goes in each layer, see [MVC.md](MVC.md).

## 1. Top-level folders

```
scrum/
├── app/                      PHP code of the application
│   ├── Enums/                Fixed lists of values (Role, ProjectStatus, …)
│   ├── Http/
│   │   ├── Controllers/      CONTROLLERS: one per resource (ProjectController, …)
│   │   │   └── Auth/         Login, register, password reset (from Breeze)
│   │   ├── Requests/         Form validation, one per form (StoreProjectRequest, …)
│   │   └── ViewComposers/    Data for the shared layout (navigation, success banner)
│   ├── Models/               MODELS: one per table (Team, Project, User, …)
│   ├── Policies/             Permission rules, one per model (ProjectPolicy, …)
│   ├── Providers/            App start-up settings (AppServiceProvider)
│   └── View/Components/      Layout wrappers used by <x-app-layout> / <x-guest-layout>
├── bootstrap/                Framework start-up (don't edit)
├── config/                   Settings files; values come from .env
├── database/                 DATABASE DEFINITION (see section 2)
│   ├── migrations/           One file per table change
│   ├── seeders/              Demo data
│   └── factories/            Fake data for tests
├── docs/                     Team documentation (this file, MVC.md)
├── public/                   The web root: index.php, built CSS/JS (public/build), favicon
├── resources/
│   ├── views/                VIEWS: Blade templates, one folder per controller
│   │   ├── layouts/          Shared page frame (app, guest, navigation)
│   │   ├── components/       Reusable UI pieces (buttons, inputs, modal)
│   │   ├── auth/  profile/   Breeze pages
│   │   └── teams/  invitations/  projects/  …one folder per feature resource
│   ├── css/app.css           Tailwind entry file
│   └── js/app.js             Alpine.js entry file
├── routes/
│   ├── web.php               ROUTER: every URL → Controller@action, grouped by feature
│   ├── auth.php              Login/register routes (Breeze)
│   └── console.php           Scheduled jobs (e.g. daily deadline check in F10)
├── scripts/                  Setup scripts (Windows .ps1, Linux .sh)
├── storage/                  Logs, cache, sessions and UPLOADED FILES (not in Git)
├── tests/
│   ├── Feature/              One test file per feature + MvcArchitectureTest
│   └── Unit/
├── vendor/  node_modules/    Downloaded packages (not in Git; setup script installs them)
├── .env                      YOUR settings and secrets (not in Git)
├── .env.example              Template for .env (in Git, no secrets)
├── composer.json             PHP packages
├── package.json              JS/CSS packages
├── tailwind.config.js        Tailwind settings
├── vite.config.js            Front-end build settings
├── CLAUDE.md                 Rules Claude follows in this project
└── README.md                 Setup and run instructions
```

## 2. Where is the database?

**The database is not a file inside the project.** It lives in the MariaDB server that XAMPP runs.
The project only contains the *description* of the tables and the connection settings.

| What | Where |
|---|---|
| The actual data (on your PC) | Inside MariaDB, database **`scrum`**. XAMPP stores it in `C:\xampp\mysql\data\scrum\`. Don't touch these files directly. |
| Look at / browse the data | **phpMyAdmin**: start Apache + MySQL in XAMPP, open http://localhost/phpmyadmin → `scrum` |
| Connection settings | `.env` → `DB_CONNECTION=mariadb`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=scrum`, `DB_USERNAME=root`, `DB_PASSWORD=` |
| Table structure (columns, keys) | `database/migrations/` — one file per change, run in date order |
| Demo data (users, teams, projects, invite links) | `database/seeders/DatabaseSeeder.php` |
| Fake data for tests | `database/factories/` |
| Database used by tests | A temporary in-memory SQLite database (set in `phpunit.xml`), so tests never touch your real data |
| Uploaded files (chat media, shared files) | `storage/app/private/` (private) and `storage/app/public/` (public, linked to `public/storage`) |

Everyday commands:

| Command | What it does |
|---|---|
| `php artisan migrate` | Create any new tables / columns from new migration files |
| `php artisan migrate:fresh --seed` | **Delete all tables**, recreate them and load the demo data |
| `php artisan db:seed` | Load the demo data again (safe to repeat) |
| `php artisan make:migration create_tasks_table` | Start a new migration file |

**Rule:** never change tables by hand in phpMyAdmin. Make a migration, so the change reaches everyone's PC when they pull.

### Current tables

| Table | Created by | Used by |
|---|---|---|
| `users` (+ `role` column: admin / member) | Breeze | everything |
| `teams` | F02 | almost every feature |
| `team_members` (user ↔ team, with role owner / manager / member) | F02 | almost every feature |
| `team_invitations` (invite links) | F02 | F02 |
| `projects` (belongs to one team; optional lead) | F03 | almost every feature |
| `sessions`, `cache`, `jobs`, `password_reset_tokens` | Laravel | login sessions, queues, password resets |

## 3. Where each feature's files go

Every feature uses the same set of places. For feature **FXX** with a resource called *Thing*:

| Layer | Location | Naming |
|---|---|---|
| Routes | `routes/web.php`, in its own block `// ---- FXX Thing ----` | `GET /things → ThingController@index` |
| Controller | `app/Http/Controllers/ThingController.php` | singular + `Controller` |
| Model | `app/Models/Thing.php` | singular, matches table `things` |
| Migration | `database/migrations/YYYY_MM_DD_HHMMSS_create_things_table.php` | `create_<table>_table` / `add_<column>_to_<table>_table` |
| Validation | `app/Http/Requests/StoreThingRequest.php`, `UpdateThingRequest.php` | `Store…` / `Update…` |
| Permissions | `app/Policies/ThingPolicy.php` | model name + `Policy` |
| Fixed value lists | `app/Enums/ThingStatus.php` | |
| Views | `resources/views/things/` → `index`, `show`, `create`, `edit`, `_form` | plural folder, `_` for partials |
| Test data | `database/factories/ThingFactory.php` + a section in `DatabaseSeeder.php` | |
| Tests | `tests/Feature/ThingManagementTest.php` | |
| External API code (F13–F15) | `app/Services/…Service.php` | belongs to the model layer: it fetches/sends data |
| Notifications / emails (F10) | `app/Notifications/`, email views in `resources/views/mail/` | |

### Feature by feature

F02 and F03 are built. The other names are the plan and may be adjusted when each feature is designed.

| Feature | Owner | Controllers | Models → tables | Views folder | Tests |
|---|---|---|---|---|---|
| **F01** Onboarding page | Safwan | `OnboardingController` | `OnboardingItem` → `onboarding_items`, progress in `onboarding_item_user` | `onboarding/` | `OnboardingTest` |
| **F02** Team formation & invites ✅ | Arnab | `TeamController`, `TeamMemberController`, `TeamInvitationController` | `Team` → `teams`, `TeamMember` → `team_members` (with team role), `TeamInvitation` → `team_invitations` | `teams/`, `invitations/` | `TeamManagementTest`, `TeamMemberTest`, `TeamInvitationTest` |
| **F03** Project management ✅ | Sadman | `ProjectController` | `Project` → `projects` (`team_id`, `lead_id`) | `projects/` | `ProjectManagementTest` |
| **F04** Manager dashboard & admin analytics | Sadman | `ManagerDashboardController`, `AdminAnalyticsController` | no new tables; query methods added to `Project`, `Task`, `User`, `CheckIn` | `manager/`, `admin/` | `ManagerDashboardTest`, `AdminAnalyticsTest` |
| **F05** Personal dashboard & task board | Safwan | `DashboardController` (exists), `TaskController`, `TaskBoardController` | `Task` → `tasks` | `dashboard.blade.php`, `tasks/` | `TaskBoardTest`, `PersonalDashboardTest` |
| **F06** Chat, media & polls | Arnab | `MessageController`, `PollController` | `Message` → `messages`, `MessageAttachment` → `message_attachments`, `Poll` / `PollOption` / `PollVote` | `chat/`, `polls/` | `ChatTest`, `PollTest` |
| **F07** Contribution tracking & PDF report | Sadman | `ContributionController`, `ContributionReportController` | query methods on `Task`, `User`; PDF via a PDF package | `contributions/` (+ `contributions/pdf.blade.php`) | `ContributionTest` |
| **F08** Meetings & notes | Safwan | `MeetingController`, `MeetingNoteController` | `Meeting` → `meetings`, `meeting_participants`, `MeetingNote` → `meeting_notes` | `meetings/` | `MeetingTest` |
| **F09** File sharing & versions | Arnab | `SharedFileController`, `FileVersionController` | `SharedFile` → `shared_files`, `FileVersion` → `file_versions`; files in `storage/app/private/files/` | `files/` | `FileSharingTest` |
| **F10** Notifications & deadline warnings | Sadman | `NotificationController` | Laravel `notifications` table; classes in `app/Notifications/`; daily check scheduled in `routes/console.php` | `notifications/`, `mail/` | `NotificationTest`, `DeadlineWarningTest` |
| **F11** Check-ins & peer rating | Safwan | `CheckInController`, `PeerRatingController` | `CheckIn` → `check_ins`, `PeerRating` → `peer_ratings` | `check-ins/`, `peer-ratings/` | `CheckInTest`, `PeerRatingTest` |
| **F12** Support requests | Arnab | `SupportTicketController` | `SupportTicket` → `support_tickets`, `SupportReply` → `support_replies` | `support/` | `SupportTicketTest` |
| **F13** AI weekly summaries | Safwan | `WeeklySummaryController` | `WeeklySummary` → `weekly_summaries`; `app/Services/AiSummaryService.php` | `summaries/` | `WeeklySummaryTest` |
| **F14** Google Workspace (Docs, Sheets) | Sadman | `GoogleAuthController`, `GoogleExportController` | `GoogleAccount` → `google_accounts`; `app/Services/GoogleWorkspaceService.php` | `integrations/google/` | `GoogleIntegrationTest` |
| **F15** Payment gateway | Arnab | `SubscriptionController`, `PaymentController` | `Subscription` → `subscriptions`, `Payment` → `payments`; `app/Services/PaymentGatewayService.php` | `billing/` | `PaymentTest` |

API keys for F13–F15 go in `.env` (names listed in `.env.example`, values never committed) and are read through `config/services.php`.

## 4. Files you will rarely touch

| Path | Why |
|---|---|
| `bootstrap/`, `public/index.php`, `artisan` | Framework start-up |
| `config/*.php` | Change values in `.env` instead |
| `vendor/`, `node_modules/` | Installed packages; delete and re-run setup if broken |
| `public/build/` | Generated by `npm run build` |
| `storage/framework/`, `storage/logs/` | Cache, compiled views, error logs (`storage/logs/laravel.log` is where errors appear) |
