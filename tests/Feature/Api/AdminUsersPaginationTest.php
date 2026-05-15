<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUsersPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_index_respects_page_and_per_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        User::factory(25)->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);
        $res = $this->getJson('/api/admin/users?page=2&per_page=10');

        $res->assertOk();
        $res->assertJsonPath('meta.current_page', 2);
        $res->assertJsonPath('meta.per_page', 10);
        $res->assertJsonPath('meta.last_page', 3);
        $this->assertCount(10, $res->json('data'));
    }

    public function test_admin_users_per_page_is_clamped(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        User::factory(60)->create([
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);
        $high = $this->getJson('/api/admin/users?per_page=999');
        $high->assertOk();
        $high->assertJsonPath('meta.per_page', 50);

        $low = $this->getJson('/api/admin/users?per_page=0');
        $low->assertOk();
        $low->assertJsonPath('meta.per_page', 1);
    }
}
