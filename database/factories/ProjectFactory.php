<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 week');

        return [
            'team_id' => Team::factory()->withOwner(),
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->paragraph(),
            'status' => ProjectStatus::Active,
            'start_date' => $start,
            'due_date' => fake()->dateTimeBetween($start, '+2 months'),
            'lead_id' => null,
        ];
    }
}
