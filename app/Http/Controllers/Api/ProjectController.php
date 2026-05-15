<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\PreviewImageUploadRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UploadProjectGalleryRequest;
use App\Http\Requests\Project\UploadProjectInlineImageRequest;
use App\Http\Resources\ProjectResource;
use App\Models\CampusEvent;
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
    /**
     * Лента последних опубликованных проектов (доступна без авторизации).
     */
    public function publishedFeed(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $q = Project::query()
            ->where('projects.is_published', true)
            ->with(['students', 'supervisor', 'createdBy', 'campusEvent'])
            ->withCount('likes');

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('q')).'%';
            $driver = $q->getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                $q->where('projects.title', 'ilike', $term);
            } else {
                $q->whereRaw('LOWER(projects.title) LIKE LOWER(?)', [$term]);
            }
        }

        if ($request->filled('course') || $request->filled('group')) {
            $q->whereHas('students', function ($students) use ($request) {
                if ($request->filled('course')) {
                    $students->where('course', $request->query('course'));
                }
                if ($request->filled('group')) {
                    $students->where('group', $request->query('group'));
                }
            });
        }

        if ($genre = $this->normalizedGenreForPublishedFeed($request)) {
            $q->where(function ($sub) use ($genre) {
                $sub->whereJsonContains('projects.technologies', $genre)
                    ->orWhereHas('campusEvent', function ($campus) use ($genre) {
                        $campus->whereJsonContains($campus->qualifyColumn('genres'), $genre);
                    });
            });
        }

        $paginator = $q->orderByDesc('projects.updated_at')->paginate($perPage)->withQueryString();

        $viewer = $request->user();
        if ($viewer && $paginator->isNotEmpty()) {
            $liked = Like::query()
                ->where('user_id', $viewer->id)
                ->whereIn('project_id', $paginator->getCollection()->pluck('id'))
                ->pluck('project_id');
            $paginator->getCollection()->each(function (Project $p) use ($liked) {
                $p->setAttribute('liked_by_me', $liked->contains($p->id));
            });
        } else {
            $paginator->getCollection()->each(fn (Project $p) => $p->setAttribute('liked_by_me', false));
        }

        return ProjectResource::collection($paginator);
    }

    /**
     * Варианты фильтров для ленты проектов (курс/группа из студентов с опубликованными работами; жанры из конфига).
     */
    public function publishedFeedFilters(): JsonResponse
    {
        $courses = User::query()
            ->where('role', UserRole::Student)
            ->where('is_active', true)
            ->whereNotNull('course')
            ->where('course', '!=', '')
            ->whereHas('projects', fn ($p) => $p->where('is_published', true))
            ->distinct()
            ->orderBy('course')
            ->pluck('course')
            ->values();

        $groups = User::query()
            ->where('role', UserRole::Student)
            ->where('is_active', true)
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->whereHas('projects', fn ($p) => $p->where('is_published', true))
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->values();

        return response()->json([
            'courses' => $courses->all(),
            'groups' => $groups->all(),
            'genres' => array_values(config('portfolio.event_genres', [])),
        ]);
    }

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
            ->with(['students', 'supervisor', 'createdBy', 'campusEvent'])
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
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
            'technologies' => $this->technologiesWithMergedEventGenre($d['technologies'] ?? [], $d['campus_event_id'] ?? null),
            'is_published' => $d['is_published'],
            'supervisor_user_id' => $d['supervisor_user_id'] ?? null,
            'created_by_user_id' => $user->id,
            'campus_event_id' => $d['campus_event_id'] ?? null,
        ]);
        $project->forceFill([
            'description' => ProjectDescriptionHtml::sanitize($d['description'], $project->id),
        ])->save();
        $collab = $d['collaborator_ids'] ?? [$user->id];
        if (! is_array($collab)) {
            $collab = [$user->id];
        }
        if (! $user->isAdmin() && $user->isStudent() && $collab === []) {
            $collab = [$user->id];
        }
        $project->syncStudents(array_map('intval', $collab), $isAdmin, $user);
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
            $newHtml = ProjectDescriptionHtml::sanitize($d['description'], $project->id);
            $project->description = $newHtml;
            $this->pruneUnusedInlineImages($project, $newHtml);
        }
        if (array_key_exists('github_url', $d)) {
            $project->github_url = $d['github_url'];
        }
        if (array_key_exists('technologies', $d)) {
            $project->technologies = $d['technologies'] ?? [];
        }
        if (array_key_exists('supervisor_user_id', $d)) {
            $project->supervisor_user_id = $d['supervisor_user_id'];
        }
        if (array_key_exists('campus_event_id', $d)) {
            $project->campus_event_id = $d['campus_event_id'];
        }
        $project->technologies = $this->technologiesWithMergedEventGenre(
            $project->technologies ?? [],
            $project->campus_event_id
        );
        $project->save();
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
     * Удаляет файлы в project-inline/{id}, которых нет в HTML описания (в т.ч. загруженные, но не вставленные).
     */
    private function pruneUnusedInlineImages(Project $project, string $descriptionHtml): void
    {
        $disk = Project::projectMediaDisk();
        $dir = 'project-inline/'.$project->id;
        if (! Storage::disk($disk)->exists($dir)) {
            return;
        }
        $referenced = array_flip(ProjectDescriptionHtml::collectInlineStoragePaths($descriptionHtml));
        foreach (Storage::disk($disk)->files($dir) as $path) {
            if (! isset($referenced[$path])) {
                Storage::disk($disk)->delete($path);
            }
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
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
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
        $project->load(['students', 'supervisor', 'createdBy', 'campusEvent']);
        $project->loadCount('likes');
        $project->setAttribute('liked_by_me', Like::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->exists());

        return new ProjectResource($project);
    }

    /**
     * Приводит query-параметр genre к каноническому значению из config/portfolio.php (trim, регистр).
     */
    private function normalizedGenreForPublishedFeed(Request $request): ?string
    {
        $raw = $request->query('genre');
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_array($raw)) {
            $raw = reset($raw);
            if ($raw === false) {
                return null;
            }
        }
        if (! is_string($raw)) {
            return null;
        }

        return $this->canonicalGenreLabel($raw);
    }

    /** Строка жанра как в config `portfolio.event_genres`, или null если не из списка. */
    private function canonicalGenreLabel(string $raw): ?string
    {
        $candidate = trim($raw);
        if ($candidate === '') {
            return null;
        }
        $allowed = config('portfolio.event_genres', []);
        foreach ($allowed as $g) {
            if ($g === $candidate) {
                return $g;
            }
        }
        foreach ($allowed as $g) {
            if (mb_strtolower($g, 'UTF-8') === mb_strtolower($candidate, 'UTF-8')) {
                return $g;
            }
        }

        return null;
    }

    /**
     * Добавляет жанр связанного события в technologies (если его там ещё нет), чтобы фильтр ленты совпадал с карточкой «по событию».
     *
     * @param  array<int, string>  $technologies
     * @return array<int, string>
     */
    private function technologiesWithMergedEventGenre(array $technologies, ?int $campusEventId): array
    {
        $technologies = array_values(array_filter($technologies, static fn ($t) => is_string($t) && $t !== ''));
        if (! $campusEventId) {
            return array_values(array_unique($technologies));
        }
        $event = CampusEvent::query()->find($campusEventId);
        if (! $event) {
            return array_values(array_unique($technologies));
        }
        $fromEvent = $event->genres ?? [];
        if (! is_array($fromEvent)) {
            return array_values(array_unique($technologies));
        }
        foreach ($fromEvent as $label) {
            $canonical = $this->canonicalGenreLabel((string) $label);
            if ($canonical === null) {
                continue;
            }
            $already = false;
            foreach ($technologies as $t) {
                if (mb_strtolower((string) $t, 'UTF-8') === mb_strtolower($canonical, 'UTF-8')) {
                    $already = true;
                    break;
                }
            }
            if (! $already) {
                $technologies[] = $canonical;
            }
        }

        return array_values(array_unique($technologies));
    }
}
