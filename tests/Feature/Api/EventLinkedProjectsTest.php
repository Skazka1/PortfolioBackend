<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\CampusEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventLinkedProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_event_and_see_linked_published_projects(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $student = User::factory()->create(['role' => UserRole::Student]);
        $event = CampusEvent::factory()->create([
            'created_by_user_id' => $teacher->id,
            'date_time' => now()->subDay(),
        ]);
        $published = Project::factory()->create([
            'is_published' => true,
            'campus_event_id' => $event->id,
            'created_by_user_id' => $student->id,
        ]);
        $draft = Project::factory()->create([
            'is_published' => false,
            'campus_event_id' => $event->id,
            'created_by_user_id' => $student->id,
        ]);
        $student->projects()->attach([$published->id, $draft->id]);

        $this->getJson("/api/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $event->id);

        $list = $this->getJson("/api/events/{$event->id}/projects");
        $list->assertOk();
        $list->assertJsonPath('data.0.id', $published->id);
        $this->assertCount(1, $list->json('data'));
    }
}
