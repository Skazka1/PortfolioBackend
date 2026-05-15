<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_admin_cannot_be_deleted(): void
    {
        $actingAdmin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $primaryAdmin = User::factory()->create([
            'email' => config('portfolio.primary_admin_email'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        app()->setLocale('ru');

        Sanctum::actingAs($actingAdmin);
        $this->deleteJson('/api/admin/users/'.$primaryAdmin->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', __('portfolio.cannot_delete_primary_admin'));

        $this->assertDatabaseHas('users', ['id' => $primaryAdmin->id]);
    }

    public function test_other_admin_can_be_deleted(): void
    {
        $actingAdmin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $otherAdmin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        Sanctum::actingAs($actingAdmin);
        $this->deleteJson('/api/admin/users/'.$otherAdmin->id)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('users', ['id' => $otherAdmin->id]);
    }
}
