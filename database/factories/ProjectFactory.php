<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'title' => fake('ru_RU')->realTextBetween(28, 72),
            'description' => fake('ru_RU')->realTextBetween(120, 400),
            'github_url' => fake()->optional(0.5)->url(),
            'preview_image_path' => null,
            'technologies' => [fake()->randomElement(['PHP', 'Vue', 'Laravel', 'PostgreSQL', 'Docker', 'TypeScript', 'Node'])],
            'is_published' => true,
        ];
    }
}
