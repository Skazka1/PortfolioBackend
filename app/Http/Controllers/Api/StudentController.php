<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\StudentCardResource;
use App\Http\Resources\StudentBriefResource;
use App\Models\Like;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = User::query()
            ->where('role', UserRole::Student)
            ->where('is_active', true)
            ->when($request->query('course'), fn ($b, $course) => $b->where('course', (string) $course))
            ->when($request->query('group'), fn ($b, $group) => $b->where('group', (string) $group))
            ->when(
                $request->query('year_of_graduation') !== null && $request->query('year_of_graduation') !== '',
                fn ($b) => $b->where('year_of_graduation', (int) $request->query('year_of_graduation'))
            )
            ->when($request->filled('q'), function ($b) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('q')).'%';
                $b->where('name', 'ilike', $term);
            });

        if ($tech = $request->query('technology')) {
            $q->whereHas('projects', function ($p) use ($tech) {
                $p->where('is_published', true)
                    ->whereJsonContains('technologies', (string) $tech);
            });
        }

        $paginator = $q
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 12))
            ->withQueryString();

        $viewer = $request->user();
        $paginator->getCollection()->transform(function (User $u) use ($viewer) {
            $ids = $u->projects()->pluck('projects.id');
            if ($ids->isEmpty()) {
                $u->setRelation('lastProjects', collect());

                return $u;
            }
            $last = Project::query()
                ->whereIn('projects.id', $ids)
                ->visibleFor($viewer)
                ->orderByDesc('projects.id')
                ->limit(3)
                ->get();
            $u->setRelation('lastProjects', $last);

            return $u;
        });

        return StudentCardResource::collection($paginator);
    }

    public function show(Request $request, User $student): JsonResponse
    {
        if (! $student->isStudent() || ! $student->is_active) {
            abort(404);
        }
        $this->authorize('viewPortfolio', $student);

        $ids = $student->projects()->pluck('projects.id');
        $viewer = $request->user();
        $projects = Project::query()
            ->whereIn('projects.id', $ids)
            ->visibleFor($viewer)
            ->with('students')
            ->withCount('likes')
            ->orderByDesc('projects.id')
            ->get();

        $uid = $request->user()?->id;
        if ($uid && $projects->isNotEmpty()) {
            $likedIds = Like::query()
                ->where('user_id', $uid)
                ->whereIn('project_id', $projects->pluck('id'))
                ->pluck('project_id');
            $projects->each(function (Project $p) use ($likedIds) {
                $p->setAttribute('liked_by_me', $likedIds->contains($p->id));
            });
        } else {
            $projects->each(fn (Project $p) => $p->setAttribute('liked_by_me', false));
        }

        return response()->json([
            'data' => new StudentBriefResource($student->loadMissing([])),
            'projects' => [
                'data' => ProjectResource::collection($projects)->resolve($request),
            ],
        ]);
    }
}
