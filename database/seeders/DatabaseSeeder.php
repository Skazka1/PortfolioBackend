<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Like;
use App\Models\Project;
use App\Models\CampusEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $commonPassword = Hash::make('password');

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Главный админ',
                'role' => UserRole::Admin,
                'password' => $commonPassword,
                'is_active' => true,
            ]
        );

        $admins = User::factory(5)
            ->state([
                'role' => UserRole::Admin,
                'password' => $commonPassword,
                'course' => null,
                'group' => null,
                'year_of_graduation' => null,
            ])
            ->create();

        $teachers = User::factory(2)
            ->state([
                'role' => UserRole::Teacher,
                'password' => $commonPassword,
                'course' => null,
                'group' => null,
                'year_of_graduation' => null,
            ])
            ->create();

        $students = User::factory(20)
            ->state([
                'role' => UserRole::Student,
                'password' => $commonPassword,
            ])
            ->create();

        $allUsers = $admins->merge($teachers)->merge($students);

        foreach ($students as $i => $student) {
            for ($j = 0; $j < 2; $j++) {
                $project = Project::factory()->create();
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

        $creator = $teachers->first() ?? $admins->first();
        if ($creator) {
            CampusEvent::factory(8)->state(['created_by_user_id' => $creator->id])->create();
        }
    }
}
