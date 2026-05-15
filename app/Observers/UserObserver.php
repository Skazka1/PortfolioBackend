<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->isPrimaryAdmin()) {
            throw new \LogicException('Primary admin account cannot be deleted.');
        }

        if ($user->avatar_path) {
            Storage::disk((string) config('filesystems.avatar_disk', 'public'))->delete($user->avatar_path);
        }

        $projectIds = $user->projects()->pluck('projects.id');
        $user->projects()->detach();

        foreach (Project::query()->whereIn('id', $projectIds)->get() as $project) {
            if ($project->students()->count() === 0) {
                if ($project->preview_image_path) {
                    Storage::disk(Project::projectMediaDisk())->delete($project->preview_image_path);
                }
                $project->delete();
            }
        }
    }
}
