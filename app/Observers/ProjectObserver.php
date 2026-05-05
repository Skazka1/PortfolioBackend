<?php

namespace App\Observers;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

class ProjectObserver
{
    public function deleting(Project $project): void
    {
        $disk = Project::projectMediaDisk();
        if ($project->preview_image_path) {
            Storage::disk($disk)->delete($project->preview_image_path);
        }
        foreach ($project->gallery_paths ?? [] as $path) {
            if ($path) {
                Storage::disk($disk)->delete($path);
            }
        }
        $inlineDir = 'project-inline/'.$project->id;
        if (Storage::disk($disk)->exists($inlineDir)) {
            Storage::disk($disk)->deleteDirectory($inlineDir);
        }
    }
}
