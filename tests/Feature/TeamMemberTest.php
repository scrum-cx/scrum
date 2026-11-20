<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** F02: changing roles, removing members, leaving, transferring ownership. */
class TeamMemberTest extends TestCase
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

    private function url(User $user): string
    {
        return "/teams/{$this->team->id}/members/{$user->id}";
    }

    public function test_owner_can_change_roles(): void
    {
        $this->actingAs($this->owner)->put($this->url($this->member), ['role' => 'manager'])->assertRedirect();

        $this->assertSame(TeamRole::Manager, $this->team->roleOf($this->member));
    }

    public function test_managers_and_members_cannot_change_roles(): void
    {
        $this->actingAs($this->manager)->put($this->url($this->member), ['role' => 'manager'])->assertForbidden();
        $this->actingAs($this->member)->put($this->url($this->manager), ['role' => 'member'])->assertForbidden();
    }

    public function test_owner_cannot_change_own_role(): void
    {
        $this->actingAs($this->owner)->put($this->url($this->owner), ['role' => 'member'])->assertForbidden();
    }

    public function test_role_must_be_valid(): void
    {
        $this->actingAs($this->owner)->put($this->url($this->member), ['role' => 'emperor'])->assertSessionHasErrors('role');
    }

    public function test_making_someone_owner_transfers_ownership(): void
    {
        $this->actingAs($this->owner)->put($this->url($this->manager), ['role' => 'owner'])->assertRedirect();

        $this->assertSame(TeamRole::Owner, $this->team->roleOf($this->manager));
        $this->assertSame(TeamRole::Manager, $this->team->roleOf($this->owner));
        $this->assertSame(1, $this->team->members()->wherePivot('role', 'owner')->count());
    }

    public function test_manager_can_remove_members_but_not_managers_or_owner(): void
    {
        $other = User::factory()->create();
        $this->team->addMember($other, TeamRole::Manager);

        $this->actingAs($this->manager)->delete($this->url($other))->assertForbidden();
        $this->actingAs($this->manager)->delete($this->url($this->owner))->assertForbidden();
        $this->actingAs($this->manager)->delete($this->url($this->member))->assertRedirect();

        $this->assertNull($this->team->roleOf($this->member));
    }

    public function test_owner_can_remove_a_manager(): void
    {
        $this->actingAs($this->owner)->delete($this->url($this->manager))->assertRedirect();

        $this->assertFalse($this->team->hasMember($this->manager->id));
    }

    public function test_members_can_leave_but_owner_cannot(): void
    {
        $this->actingAs($this->member)->delete($this->url($this->member))->assertRedirect('/teams');
        $this->assertFalse($this->team->hasMember($this->member->id));

        $this->actingAs($this->owner)->delete($this->url($this->owner))->assertForbidden();
    }

    public function test_removed_member_stops_leading_the_teams_projects(): void
    {
        $project = Project::factory()->for($this->team)->create(['lead_id' => $this->member->id]);

        $this->actingAs($this->owner)->delete($this->url($this->member));

        $this->assertNull($project->fresh()->lead_id);
        $this->actingAs($this->member)->get("/projects/{$project->id}")->assertForbidden();
    }
}
