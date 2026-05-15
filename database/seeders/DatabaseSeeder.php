<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CampusEvent;
use App\Models\Like;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * На сервере после деплоя обычно выполняют только `php artisan migrate`.
     * База не очищается — аккаунты и проекты остаются как есть.
     *
     * Сидер запускают вручную при первой установке или после `migrate:fresh`.
     * Демо-пользователей и случайные проекты создаём только локально или если в .env задано SEED_DEMO_DATA=true.
     */
    public function run(): void
    {
        $this->seedAdminUser();

        if (! $this->shouldSeedDemoData()) {
            return;
        }

        $commonPassword = Hash::make('password');

        $teachers = User::factory(2)
            ->state([
                'role' => UserRole::Teacher,
                'password' => $commonPassword,
                'course' => null,
                'group' => null,
            ])
            ->create();

        $students = User::factory(20)
            ->state([
                'role' => UserRole::Student,
                'password' => $commonPassword,
            ])
            ->create();

        $allUsers = $teachers->merge($students);
        $admin = User::query()->where('email', 'admin@example.com')->first();

        foreach ($students as $student) {
            for ($j = 0; $j < 2; $j++) {
                $project = Project::factory()->create([
                    'created_by_user_id' => $student->id,
                ]);
                if (random_int(0, 1) === 1) {
                    $other = $students->random();
                    if ((int) $other->id !== (int) $student->id) {
                        $project->students()->sync([$student->id, $other->id]);
                    } else {
                        $project->students()->sync([$student->id]);
                    }
                } else {
                    $project->students()->sync([$student->id]);
                }
            }
        }

        $projects = Project::query()->get();
        foreach ($projects as $p) {
            if (Like::query()->count() > 500) {
                break;
            }
            $likers = $allUsers->random(min(3, $allUsers->count()));
            foreach ($likers as $u) {
                if ($p->isParticipantOf($u)) {
                    continue;
                }
                Like::query()->firstOrCreate(
                    ['user_id' => $u->id, 'project_id' => $p->id],
                );
            }
        }

        $creator = $teachers->first() ?? $admin;
        if ($creator) {
            CampusEvent::factory(8)->state(['created_by_user_id' => $creator->id])->create();
        }

        $this->call(PastCampusEventsSeeder::class);
    }

    /** Один админ — безопасно вызывать при каждом `db:seed` (идемпотентно). */
    private function seedAdminUser(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Главный админ',
                'role' => UserRole::Admin,
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'password')),
                'is_active' => true,
            ]
        );
    }

    /**
     * Локально и при явном флаге — полный демо-набор. На проде по умолчанию выключено.
     */
    private function shouldSeedDemoData(): bool
    {
        if (app()->environment('local')) {
            return filter_var(env('SEED_DEMO_DATA', true), FILTER_VALIDATE_BOOLEAN);
        }

        return filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN);
    }
}
