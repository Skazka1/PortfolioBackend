<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\CampusEventResource;
use App\Http\Resources\ProjectResource;
use App\Models\CampusEvent;
use App\Models\Like;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampusEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CampusEvent::class);

        $when = (string) $request->query('when', 'upcoming');
        if (! in_array($when, ['upcoming', 'past', 'all'], true)) {
            $when = 'upcoming';
        }

        $q = CampusEvent::query()->with('createdBy');

        match ($when) {
            'past' => $q->where('date_time', '<', now())->orderByDesc('date_time'),
            'all' => $q->orderBy('date_time'),
            default => $q->where('date_time', '>=', now())->orderBy('date_time'),
        };

        return CampusEventResource::collection($q->paginate((int) $request->query('per_page', 20)));
    }

    /**
     * Одно событие (для заголовка страницы связанных проектов).
     */
    public function show(Request $request, CampusEvent $event): CampusEventResource
    {
        $this->authorize('view', $event);
        $event->load('createdBy');

        return new CampusEventResource($event);
    }

    /**
     * Опубликованные проекты, привязанные к событию (каталог).
     */
    public function projects(Request $request, CampusEvent $event): AnonymousResourceCollection
    {
        $this->authorize('view', $event);
        $perPage = min(max((int) $request->query('per_page', 12), 1), 50);

        $q = Project::query()
            ->where('campus_event_id', $event->id)
            ->where('projects.is_published', true)
            ->with(['students', 'supervisor', 'createdBy', 'campusEvent'])
            ->withCount('likes');

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

    public function store(StoreEventRequest $request): CampusEventResource
    {
        $this->authorize('create', CampusEvent::class);
        $user = $request->user();
        $d = $request->validated();
        $event = CampusEvent::query()->create([
            'title' => $d['title'],
            'description' => $d['description'] ?? null,
            'date_time' => $d['date_time'],
            'location' => $d['location'] ?? null,
            'genres' => array_values(array_unique($d['genres'])),
            'created_by_user_id' => $user->id,
        ]);
        $event->load('createdBy');

        return new CampusEventResource($event);
    }

    public function update(UpdateEventRequest $request, CampusEvent $event): CampusEventResource
    {
        $this->authorize('update', $event);
        $validated = $request->validated();
        if (array_key_exists('genres', $validated) && is_array($validated['genres'])) {
            $validated['genres'] = array_values(array_unique($validated['genres']));
        }
        $event->fill($validated);
        $event->save();
        $event->load('createdBy');

        return new CampusEventResource($event);
    }

    public function destroy(CampusEvent $event): Response
    {
        $this->authorize('delete', $event);
        $event->delete();

        return response()->noContent();
    }
}
