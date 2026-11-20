<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\Role;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data. Safe to run more than once. Every demo account's password is "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = $this->user('Ayesha Admin', 'admin@example.com', Role::Admin);
        $owner = $this->user('Mahin Owner', 'owner@example.com');
        $manager = $this->user('Nusrat Manager', 'manager@example.com');
        $member = $this->user('Test User', 'test@example.com');
        $dev = $this->user('Rafi Developer', 'dev@example.com');

        $today = today();

        // [team name, description, owner, [member, role]..., [project, status, lead, start offset, due offset]...]
        $teams = [
            ['Platform Team', 'Builds and runs the company\'s web platform.', $owner,
                [[$manager, TeamRole::Manager], [$member, TeamRole::Member], [$dev, TeamRole::Member]],
                [
                    ['Customer Portal Redesign', ProjectStatus::Active, $manager, -20, 25],
                    ['Mobile App v2', ProjectStatus::Planning, $dev, 5, 60],
                    ['Legacy API Migration', ProjectStatus::Active, $owner, -45, -3],   // overdue
                ]],
            ['Design Team', 'Product, brand and UX design.', $manager,
                [[$member, TeamRole::Member]],
                [
                    ['Design System 2026', ProjectStatus::OnHold, $manager, -30, 40],
                    ['Winter Campaign Visuals', ProjectStatus::Completed, $member, -60, -10],
                ]],
            ['Research Guild', 'Market and user research.', $dev, [], []],
        ];

        foreach ($teams as [$name, $description, $teamOwner, $members, $projects]) {
            $team = Team::firstWhere('name', $name) ?? Team::createWithOwner(['name' => $name, 'description' => $description], $teamOwner);

            foreach ($members as [$user, $role]) {
                $team->addMember($user, $role);
            }

            foreach ($projects as [$projectName, $status, $lead, $startOffset, $dueOffset]) {
                $project = Project::firstOrNew(['team_id' => $team->id, 'name' => $projectName]);
                $project->fill([
                    'description' => "Demo project of the {$name}.",
                    'status' => $status,
                    'start_date' => $today->copy()->addDays($startOffset),
                    'due_date' => $today->copy()->addDays($dueOffset),
                    'lead_id' => $lead->id,
                ]);
                $project->team_id = $team->id;
                $project->created_by ??= $teamOwner->id;
                $project->save();
            }

            if ($team->activeInvitations()->isEmpty()) {
                TeamInvitation::createFor($team, $teamOwner, TeamRole::Member, 30, null);
            }
        }
    }

    private function user(string $name, string $email, Role $role = Role::Member): User
    {
        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->fill(['name' => $name, 'password' => Hash::make('password')]);
            $user->email_verified_at = now();
        }
        $user->role = $role;   // role is not mass-assignable on purpose
        $user->save();

        return $user;
    }
}
