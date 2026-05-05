<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_project(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($student, 'web');

        $res = $this->postJson('/api/projects', [
            'title' => 'Test project',
            'description' => 'Description text',
            'technologies' => ['Vue', 'Laravel'],
            'collaborator_ids' => [(int) $student->id],
        ]);

        $res->assertCreated();
        $this->assertDatabaseHas('projects', ['title' => 'Test project']);
    }

    public function test_student_can_download_own_project_pdf(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($student, 'web');
        $create = $this->postJson('/api/projects', [
            'title' => 'Отчёт по практике',
            'description' => 'Текст проекта для PDF.',
            'technologies' => ['PHP'],
            'collaborator_ids' => [(int) $student->id],
        ]);
        $create->assertCreated();
        $id = (int) $create->json('data.id');

        $res = $this->get("/api/projects/{$id}/pdf");
        $res->assertOk();
        $res->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent() ?: '');
    }

    public function test_student_can_upload_and_delete_gallery_images(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($student, 'web');
        $create = $this->postJson('/api/projects', [
            'title' => 'С фотографиями',
            'description' => 'Галерея',
            'technologies' => [],
            'collaborator_ids' => [(int) $student->id],
        ]);
        $create->assertCreated();
        $id = (int) $create->json('data.id');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $up = $this->post("/api/projects/{$id}/gallery", [
            'images' => [
                UploadedFile::fake()->createWithContent('a.png', $png),
                UploadedFile::fake()->createWithContent('b.png', $png),
            ],
        ]);
        $up->assertOk();
        $this->assertCount(2, $up->json('data.gallery_urls'));

        $del = $this->deleteJson("/api/projects/{$id}/gallery/0");
        $del->assertOk();
        $this->assertCount(1, $del->json('data.gallery_urls'));
    }

    public function test_student_can_upload_inline_image_for_description(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($student, 'web');
        $create = $this->postJson('/api/projects', [
            'title' => 'С HTML',
            'description' => '<p>Привет</p>',
            'technologies' => [],
            'collaborator_ids' => [(int) $student->id],
        ]);
        $create->assertCreated();
        $id = (int) $create->json('data.id');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $up = $this->post("/api/projects/{$id}/inline-image", [
            'image' => UploadedFile::fake()->createWithContent('x.png', $png),
        ]);
        $up->assertOk();
        $url = $up->json('data.url');
        $this->assertIsString($url);
        $this->assertStringContainsString('project-inline/'.$id.'/', $url);

        $patch = $this->patchJson("/api/projects/{$id}", [
            'description' => '<p>Текст</p><p><img src="'.$url.'" alt=""></p>',
        ]);
        $patch->assertOk();
        $this->assertStringContainsString('project-inline/', $patch->json('data.description'));
    }
}
