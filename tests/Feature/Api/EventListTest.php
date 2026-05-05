<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\CampusEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventListTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_list_sorted(): void
    {
        $u = User::factory()->create(['role' => UserRole::Teacher]);
        CampusEvent::query()->create([
            'title' => 'A',
            'date_time' => now()->addDays(10),
            'created_by_user_id' => $u->id,
        ]);
        CampusEvent::query()->create([
            'title' => 'B',
            'date_time' => now()->addDays(1),
            'created_by_user_id' => $u->id,
        ]);
        $res = $this->getJson('/api/events?per_page=100');
        $res->assertOk();
        $data = $res->json('data');
        $this->assertNotEmpty($data);
        $dates = array_map(fn (array $row) => $row['date_time'], $data);
        $sorted = $dates;
        usort($sorted, fn (string $a, string $b) => $a <=> $b);
        $this->assertEquals($sorted, $dates, 'События должны сортироваться по дате (по возрастанию).');
    }
}
