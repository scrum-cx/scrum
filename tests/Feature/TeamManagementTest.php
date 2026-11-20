<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** F02: creating, viewing, editing and deleting teams. */
class TeamManagementTest extends TestCase
{
    use RefreshDatabase;


    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/teams')->assertRedirect('/login');
    }

    public function test_any_user_can_create_a_team_and_becomes_its_owner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/teams', ['name' => '  Platform Team ', 'description' => 'Web platform'])
            ->assertRedirect();

        $team = Team::firstWhere('name', 'Platform Team');
        $this->assertNotNull($team);
        $this->assertSame(TeamRole::Owner, $team->roleOf($user));
        $this->assertSame($user->id, $team->created_by);
    }

    public function test_team_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/teams', ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_users_only_see_teams_they_belong_to(): void
    {
        $user = User::factory()->create();
        $mine = Team::factory()->withOwner()->create(['name' => 'Alpha Team']);
        $mine->addMember($user, TeamRole::Member);
        $other = Team::factory()->withOwner()->create(['name' => 'Beta Team']);

        $this->actingAs($user)->get('/teams')->assertSee('Alpha Team')->assertDontSee('Beta Team');
        $this->actingAs($user)->get("/teams/{$mine->id}")->assertOk();
        $this->actingAs($user)->get("/teams/{$other->id}")->assertForbidden();
    }

    public function test_admin_sees_every_team(): void
    {
        $team = Team::factory()->withOwner()->create(['name' => 'Hidden Team']);

        $this->actingAs(User::factory()->admin()->create())->get('/teams')->assertSee('Hidden Team');
        $this->actingAs(User::factory()->admin()->create())->get("/teams/{$team->id}")->assertOk();
    }

    public function test_owner_and_manager_can_edit_but_member_cannot(): void
    {
        $team = Team::factory()->withOwner()->create();
        $manager = User::factory()->create();
        $member = User::factory()->create();
        $team->addMember($manager, TeamRole::Manager);
        $team->addMember($member, TeamRole::Member);

        $this->actingAs($manager)->put("/teams/{$team->id}", ['name' => 'Renamed'])->assertRedirect("/teams/{$team->id}");
        $this->assertSame('Renamed', $team->fresh()->name);

        $this->actingAs($member)->get("/teams/{$team->id}/edit")->assertForbidden();
        $this->actingAs($member)->put("/teams/{$team->id}", ['name' => 'Nope'])->assertForbidden();
    }

    public function test_only_owner_can_delete_and_only_without_projects(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->ownedBy($owner)->create();
        $manager = User::factory()->create();
        $team->addMember($manager, TeamRole::Manager);
        $project = Project::factory()->for($team)->create();

        $this->actingAs($manager)->delete("/teams/{$team->id}")->assertForbidden();
        $this->actingAs($owner)->delete("/teams/{$team->id}")->assertForbidden();   // still has a project

        $project->delete();
        $this->actingAs($owner)->delete("/teams/{$team->id}")->assertRedirect('/teams');
        $this->assertModelMissing($team);
    }

    public function test_team_page_shows_members_and_projects(): void
    {
        $owner = User::factory()->create(['name' => 'Olivia Owner']);
        $team = Team::factory()->ownedBy($owner)->create();
        Project::factory()->for($team)->create(['name' => 'Rocket Launch']);

        $this->actingAs($owner)->get("/teams/{$team->id}")
            ->assertSee('Olivia Owner')
            ->assertSee('Rocket Launch')
            ->assertSee('Create invite link');
    }

    public function test_plain_members_do_not_see_invite_tools(): void
    {
        $team = Team::factory()->withOwner()->create();
        $member = User::factory()->create();
        $team->addMember($member, TeamRole::Member);

        $this->actingAs($member)->get("/teams/{$team->id}")->assertOk()->assertDontSee('Create invite link');
    }
}
