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
            'description' => fake('ru_RU')->realTextBetween(80, 280),
            'date_time' => now()->addDays(rand(1, 120)),
            'location' => fake('ru_RU')->optional(0.75)->city(),
            'genres' => function (): array {
                $pool = array_values(config('portfolio.event_genres', ['научное']));
                shuffle($pool);
                $n = random_int(1, min(3, max(1, count($pool))));

                return array_values(array_slice($pool, 0, $n));
            },
            'created_by_user_id' => User::factory(),
        ];
    }
}
