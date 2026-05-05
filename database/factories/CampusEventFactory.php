<?php

namespace Database\Factories;

use App\Models\CampusEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampusEvent>
 */
class CampusEventFactory extends Factory
{
    protected $model = CampusEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake('ru_RU')->realTextBetween(20, 60),
            'description' => fake('ru_RU')->optional(0.85)->realTextBetween(80, 280),
            'date_time' => now()->addDays(rand(1, 120)),
            'location' => fake('ru_RU')->optional(0.75)->city(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
