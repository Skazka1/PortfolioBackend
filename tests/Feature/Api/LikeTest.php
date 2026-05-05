<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_like_own_project(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student, 'password' => Hash::make('password')]);
        $p = Project::factory()->create();
        $p->students()->sync([$student->id]);
        $this->actingAs($student, 'web');
        $res = $this->postJson("/api/projects/{$p->id}/like");
        $res->assertStatus(403);
    }

    public function test_liker_toggles(): void
    {
        $a = User::factory()->create(['role' => UserRole::Student, 'password' => Hash::make('password')]);
        $b = User::factory()->create(['role' => UserRole::Student, 'password' => Hash::make('password')]);
        $p = Project::factory()->create();
        $p->students()->sync([$a->id]);
        $this->actingAs($b, 'web');
        $r1 = $this->postJson("/api/projects/{$p->id}/like");
        $r1->assertOk();
        $r1->assertJsonFragment(['likes_count' => 1, 'liked_by_me' => true]);
        $r2 = $this->postJson("/api/projects/{$p->id}/like");
        $r2->assertOk();
        $r2->assertJsonFragment(['likes_count' => 0, 'liked_by_me' => false]);
    }
}
