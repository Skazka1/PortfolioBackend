<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_published_projects_on_student_profile(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);
        $project = Project::factory()->create(['is_published' => true]);
        $student->projects()->attach($project->id);

        $res = $this->getJson("/api/students/{$student->id}");

        $res->assertOk();
        $res->assertJsonPath('projects.data.0.id', $project->id);
        $this->assertCount(1, $res->json('projects.data'));
    }

    public function test_guest_does_not_see_draft_projects(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);
        $draft = Project::factory()->create(['is_published' => false]);
        $student->projects()->attach($draft->id);

        $res = $this->getJson("/api/students/{$student->id}");

        $res->assertOk();
        $this->assertSame([], $res->json('projects.data'));
    }
}
