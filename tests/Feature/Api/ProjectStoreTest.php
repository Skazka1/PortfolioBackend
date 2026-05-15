<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.project_media_disk' => 's3']);
        Storage::fake('s3');
    }

    public function test_student_can_create_project_with_preview_on_s3(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );
        $file = UploadedFile::fake()->createWithContent('preview.png', $png, 'image/png');

        $res = $this->postJson('/api/projects', [
            'title' => 'Новый проект',
            'description' => '<p>Описание проекта</p>',
            'preview_image' => $file,
        ]);

        $res->assertCreated();
        $res->assertJsonPath('data.title', 'Новый проект');

        $project = Project::query()->first();
        $this->assertNotNull($project);
        $this->assertNotNull($project->preview_image_path);
        Storage::disk('s3')->assertExists($project->preview_image_path);
        $this->assertTrue($project->isParticipantOf($student));
    }

    public function test_teacher_cannot_create_project(): void
    {
        $teacher = User::factory()->create([
            'role' => UserRole::Teacher,
            'is_active' => true,
        ]);

        Sanctum::actingAs($teacher);

        $this->postJson('/api/projects', [
            'title' => 'Запрещено',
            'description' => '<p>Текст</p>',
        ])->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_store_validates_required_fields(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $this->postJson('/api/projects', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description']);
    }
}
