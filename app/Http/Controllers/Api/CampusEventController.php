<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\CampusEventResource;
use App\Models\CampusEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CampusEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CampusEvent::class);
        $onlyUpcoming = $request->boolean('upcoming', true);
        $q = CampusEvent::query()->with('createdBy')->orderBy('date_time');
        if ($onlyUpcoming) {
            $q->where('date_time', '>=', now());
        }

        return CampusEventResource::collection($q->paginate((int) $request->query('per_page', 20)));
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
            'created_by_user_id' => $user->id,
        ]);
        $event->load('createdBy');

        return new CampusEventResource($event);
    }

    public function update(UpdateEventRequest $request, CampusEvent $event): CampusEventResource
    {
        $this->authorize('update', $event);
        $event->fill($request->validated());
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
