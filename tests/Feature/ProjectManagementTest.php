<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** F03: projects under a single team. */
class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private User $member;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->manager = User::factory()->create();
        $this->member = User::factory()->create();
        $this->team = Team::factory()->ownedBy($this->owner)->create();
        $this->team->addMember($this->manager, TeamRole::Manager);
        $this->team->addMember($this->member, TeamRole::Member);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'team_id' => $this->team->id,
            'name' => 'Customer Portal',
            'description' => 'Rebuild the portal',
            'status' => ProjectStatus::Active->value,
            'start_date' => '2026-12-01',
            'due_date' => '2027-01-31',
            'lead_id' => $this->member->id,
        ], $overrides);
    }

    public function test_team_manager_can_create_a_project_in_their_team(): void
    {
        $this->actingAs($this->manager)->post('/projects', $this->validData())->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Customer Portal',
            'team_id' => $this->team->id,
            'lead_id' => $this->member->id,
            'created_by' => $this->manager->id,
        ]);
    }

    public function test_plain_members_cannot_create_projects(): void
    {
        $this->actingAs($this->member)->get('/projects/create')->assertForbidden();
        $this->actingAs($this->member)->post('/projects', $this->validData())->assertForbidden();
    }

    public function test_cannot_create_a_project_in_someone_elses_team(): void
    {
        $otherTeam = Team::factory()->withOwner()->create();

        $this->actingAs($this->manager)
            ->post('/projects', $this->validData(['team_id' => $otherTeam->id]))
            ->assertSessionHasErrors('team_id');
    }

    public function test_lead_must_be_a_member_of_the_team(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->post('/projects', $this->validData(['lead_id' => $outsider->id]))
            ->assertSessionHasErrors('lead_id');
    }

    public function test_due_date_cannot_be_before_start_date(): void
    {
        $this->actingAs($this->owner)
            ->post('/projects', $this->validData(['start_date' => '2027-01-10', 'due_date' => '2027-01-09']))
            ->assertSessionHasErrors('due_date');
    }

    public function test_status_must_be_a_known_value(): void
    {
        $this->actingAs($this->owner)
            ->post('/projects', $this->validData(['status' => 'cancelled']))
            ->assertSessionHasErrors('status');
    }

    public function test_project_names_are_unique_within_a_team_only(): void
    {
        Project::factory()->for($this->team)->create(['name' => 'Customer Portal']);
        $this->actingAs($this->owner)->post('/projects', $this->validData())->assertSessionHasErrors('name');

        $secondTeam = Team::factory()->ownedBy($this->owner)->create();
        $this->actingAs($this->owner)
            ->post('/projects', $this->validData(['team_id' => $secondTeam->id, 'lead_id' => null]))
            ->assertSessionHasNoErrors();
    }

    public function test_only_team_members_can_see_a_project(): void
    {
        $project = Project::factory()->for($this->team)->create();

        $this->actingAs($this->member)->get("/projects/{$project->id}")->assertOk();
        $this->actingAs(User::factory()->create())->get("/projects/{$project->id}")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get("/projects/{$project->id}")->assertOk();
    }

    public function test_project_list_only_shows_projects_of_my_teams(): void
    {
        Project::factory()->for($this->team)->create(['name' => 'Alpha Portal']);
        Project::factory()->create(['name' => 'Gamma Elsewhere']);

        $this->actingAs($this->member)->get('/projects')
            ->assertSee('Alpha Portal')->assertDontSee('Gamma Elsewhere');
    }

    public function test_project_list_combines_several_teams_and_filters(): void
    {
        $secondTeam = Team::factory()->withOwner()->create();
        $secondTeam->addMember($this->member, TeamRole::Member);
        Project::factory()->for($this->team)->create(['name' => 'Alpha Portal', 'status' => ProjectStatus::Active]);
        Project::factory()->for($this->team)->create(['name' => 'Beta Archive', 'status' => ProjectStatus::Completed]);
        Project::factory()->for($secondTeam)->create(['name' => 'Delta Design', 'status' => ProjectStatus::Active]);

        $this->actingAs($this->member)->get('/projects')
            ->assertSee('Alpha Portal')->assertSee('Beta Archive')->assertSee('Delta Design');

        $this->actingAs($this->member)->get("/projects?team={$secondTeam->id}")
            ->assertSee('Delta Design')->assertDontSee('Alpha Portal');

        $this->actingAs($this->member)->get('/projects?status=completed')
            ->assertSee('Beta Archive')->assertDontSee('Alpha Portal');

        $this->actingAs($this->member)->get('/projects?search=portal')
            ->assertSee('Alpha Portal')->assertDontSee('Delta Design');

        $this->assertSame(['planning' => 0, 'active' => 2, 'on_hold' => 0, 'completed' => 1],
            Project::statusCountsFor($this->member));
    }

    public function test_lead_can_edit_own_project_but_not_change_lead_or_delete(): void
    {
        $project = Project::factory()->for($this->team)->create(['lead_id' => $this->member->id]);
        $data = $this->validData();
        unset($data['team_id'], $data['lead_id']);

        $this->actingAs($this->member)->put("/projects/{$project->id}", $data)->assertRedirect("/projects/{$project->id}");
        $this->assertSame('Customer Portal', $project->fresh()->name);

        $this->actingAs($this->member)
            ->put("/projects/{$project->id}", $data + ['lead_id' => $this->manager->id])
            ->assertSessionHasErrors('lead_id');
        $this->actingAs($this->member)->delete("/projects/{$project->id}")->assertForbidden();
    }

    public function test_other_members_cannot_edit_and_managers_can_delete(): void
    {
        $project = Project::factory()->for($this->team)->create();
        $data = $this->validData();
        unset($data['team_id']);

        $this->actingAs($this->member)->put("/projects/{$project->id}", $data)->assertForbidden();

        $this->actingAs($this->manager)->delete("/projects/{$project->id}")
            ->assertRedirect("/teams/{$this->team->id}");
        $this->assertModelMissing($project);
    }

    public function test_project_cannot_be_moved_to_another_team(): void
    {
        $project = Project::factory()->for($this->team)->create();
        $otherTeam = Team::factory()->ownedBy($this->owner)->create();

        $this->actingAs($this->owner)->put("/projects/{$project->id}", $this->validData(['team_id' => $otherTeam->id]));

        $this->assertSame($this->team->id, $project->fresh()->team_id);
    }

    public function test_overdue_projects_are_flagged(): void
    {
        $overdue = Project::factory()->create(['due_date' => today()->subDay(), 'status' => ProjectStatus::Active]);
        $finished = Project::factory()->create(['due_date' => today()->subDay(), 'status' => ProjectStatus::Completed]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($finished->isOverdue());
    }

    public function test_role_cannot_be_set_through_registration(): void
    {
        $this->post('/register', [
            'name' => 'Eve', 'email' => 'eve@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame('member', User::firstWhere('email', 'eve@example.com')->role->value);
    }
}
