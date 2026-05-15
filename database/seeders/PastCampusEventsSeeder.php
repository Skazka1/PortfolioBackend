<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CampusEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class PastCampusEventsSeeder extends Seeder
{
    /**
     * Добавляет прошедшие события (date_time в прошлом) для вкладки «Прошедшие».
     */
    public function run(): void
    {
        $creator = User::query()
            ->whereIn('role', [UserRole::Teacher, UserRole::Admin])
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if (! $creator) {
            return;
        }

        foreach ([5, 14, 28, 45, 75, 110, 180, 300] as $daysAgo) {
            CampusEvent::factory()->create([
                'created_by_user_id' => $creator->id,
                'date_time' => now()->subDays($daysAgo),
            ]);
        }
    }
}
