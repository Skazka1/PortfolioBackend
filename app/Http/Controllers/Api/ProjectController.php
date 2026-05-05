<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\PreviewImageUploadRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UploadProjectGalleryRequest;
use App\Http\Requests\Project\UploadProjectInlineImageRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Like;
use App\Models\Project;
use App\Models\User;
use App\Support\ProjectDescriptionHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function my(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $ids = $user->projects()->pluck('projects.id');
        $projects = Project::query()
            ->whereIn('projects.id', $ids)
            ->with('students')
            ->withCount('likes')
            ->orderByDesc('projects.id')
            ->get();
        if ($user->id && $projects->isNotEmpty()) {
            $liked = Like::query()
                ->where('user_id', $user->id)
                ->whereIn('project_id', $projects->pluck('id'))
                ->pluck('project_id');
            $projects->each(function (Project $p) use ($liked) {
                $p->setAttribute('liked_by_me', $liked->contains($p->id));
            });
        } else {
            $projects->each(fn (Project $p) => $p->setAttribute('liked_by_me', false));
        }

        return ProjectResource::collection($projects);
    }

    public function show(Request $request, Project $project): ProjectResource
    {
        $this->authorize('view', $project);
        $project->load('students');
        $project->loadCount('likes');
        $u = $request->user();
        if ($u) {
            $project->setAttribute('liked_by_me', Like::query()
                ->where('project_id', $project->id)
                ->where('user_id', $u->id)
                ->exists());
        } else {
            $project->setAttribute('liked_by_me', false);
        }

        return new ProjectResource($project);
    }

    public function store(StoreProjectRequest $request): ProjectResource
    {
        $this->authorize('create', Project::class);
        $user = $request->user();
        $d = $request->validated();
        if (! $user) {
            abort(401);
        }
        $isAdmin = $user->isAdmin();
        if (! $isAdmin) {
            $d['is_published'] = $d['is_published'] ?? true;
        } else {
            $d['is_published'] = (bool) ($d['is_published'] ?? true);
        }
        $project = Project::query()->create([
            'title' => $d['title'],
            'description' => '',
            'github_url' => $d['github_url'] ?? null,
            'technologies' => $d['technologies'] ?? [],
            'is_published' => $d['is_published'],
        ]);
        $project->forceFill([
            'description' => ProjectDescriptionHtml::sanitize($d['description'], $project->id),
        ])->save();
        $collab = $d['collaborator_ids'] ?? [ $user->id ];
        if (! is_array($collab)) {
            $collab = [ $user->id ];
        }
        if (! $user->isAdmin() && $user->isStudent() && $collab === []) {
            $collab = [ $user->id ];
        }
        $project->syncStudents(array_map('intval', $collab), $isAdmin, $user);
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', false);

        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);
        $user = $request->user();
        $d = $request->validated();
        if (isset($d['collaborator_ids']) && is_array($d['collaborator_ids']) && $user) {
            $project->syncStudents(
                array_map('intval', $d['collaborator_ids']),
                (bool) $user->isAdmin(),
                $user
            );
        }
        if ($user?->isAdmin() && array_key_exists('is_published', $d)) {
            $project->is_published = (bool) $d['is_published'];
        } elseif (array_key_exists('is_published', $d) && $user?->isStudent() && $project->isParticipantOf($user)) {
            $project->is_published = (bool) $d['is_published'];
        }
        if (array_key_exists('title', $d)) {
            $project->title = $d['title'];
        }
        if (array_key_exists('description', $d)) {
            $oldHtml = $project->description;
            $newHtml = ProjectDescriptionHtml::sanitize($d['description'], $project->id);
            $this->deleteOrphanedInlineImages($project, $oldHtml, $newHtml);
            $project->description = $newHtml;
        }
        if (array_key_exists('github_url', $d)) {
            $project->github_url = $d['github_url'];
        }
        if (array_key_exists('technologies', $d)) {
            $project->technologies = $d['technologies'] ?? [];
        }
        $project->save();
        $project->load('students');
        $project->loadCount('likes');
        if ($user) {
            $project->setAttribute('liked_by_me', Like::query()
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists());
        }

        return new ProjectResource($project);
    }

    public function uploadInlineImage(UploadProjectInlineImageRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $disk = Project::projectMediaDisk();
        $file = $request->file('image');
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $dir = 'project-inline/'.$project->id;
        $path = $file->storeAs($dir, (string) Str::uuid().'.'.$ext, $disk);
        $url = Storage::disk($disk)->url($path);

        return response()->json(['data' => ['url' => $url]]);
    }

    /**
     * Удаляет файлы встроенных картинок, которые исчезли из HTML описания.
     */
    private function deleteOrphanedInlineImages(Project $project, string $oldHtml, string $newHtml): void
    {
        $disk = Project::projectMediaDisk();
        $prefix = 'project-inline/'.$project->id.'/';
        $oldPaths = ProjectDescriptionHtml::collectInlineStoragePaths($oldHtml);
        $newPaths = ProjectDescriptionHtml::collectInlineStoragePaths($newHtml);
        foreach (array_diff($oldPaths, $newPaths) as $path) {
            if (! str_starts_with($path, $prefix)) {
                continue;
            }
            Storage::disk($disk)->delete($path);
        }
    }

    public function destroy(Request $request, Project $project): Response
    {
        $this->authorize('delete', $project);
        $project->delete();

        return response()->noContent();
    }

    public function like(Request $request, Project $project): ProjectResource|JsonResponse
    {
        $this->authorize('toggleLike', $project);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $like = Like::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();
        if ($like) {
            $like->delete();
        } else {
            Like::query()->create([
                'user_id' => $user->id,
                'project_id' => $project->id,
            ]);
        }
        $project->refresh();
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', Like::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists());

        return new ProjectResource($project);
    }

    public function uploadPreview(PreviewImageUploadRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $disk = Project::projectMediaDisk();
        $file = $request->file('image');
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs(
            'project-previews',
            (string) Str::uuid().'.'.$ext,
            $disk
        );
        if ($project->preview_image_path) {
            Storage::disk($disk)->delete($project->preview_image_path);
        }
        $project->update(['preview_image_path' => $path]);
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', $user
            ? Like::query()
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists()
            : false);

        return new ProjectResource($project);
    }

    public function uploadGallery(UploadProjectGalleryRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $disk = Project::projectMediaDisk();
        $paths = $project->gallery_paths ?? [];
        foreach ($request->file('images', []) as $file) {
            if (count($paths) >= 24) {
                break;
            }
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $paths[] = $file->storeAs('project-gallery', (string) Str::uuid().'.'.$ext, $disk);
        }
        $project->gallery_paths = array_values($paths);
        $project->save();
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', Like::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists());

        return new ProjectResource($project);
    }

    public function deleteGalleryImage(Request $request, Project $project, int $index): ProjectResource
    {
        $this->authorize('update', $project);
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $paths = $project->gallery_paths ?? [];
        if ($index < 0 || $index >= count($paths)) {
            abort(422, 'Неверный индекс изображения.');
        }
        $disk = Project::projectMediaDisk();
        Storage::disk($disk)->delete($paths[$index]);
        array_splice($paths, $index, 1);
        $project->gallery_paths = array_values($paths);
        $project->save();
        $project->load('students');
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', Like::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists());

        return new ProjectResource($project);
    }
}
