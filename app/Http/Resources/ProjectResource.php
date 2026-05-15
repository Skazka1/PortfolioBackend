<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $p = $this->resource;

        return [
            'id' => $p->id,
            'title' => $p->title,
            'description' => $p->description,
            'github_url' => $p->github_url,
            'preview_image_url' => $p->preview_image_path ? $p->preview_image_url : null,
            'gallery_urls' => collect($p->gallery_paths ?? [])
                ->filter()
                ->map(fn (string $path) => Storage::disk(Project::projectMediaDisk())->url($path))
                ->values()
                ->all(),
            'technologies' => $p->technologies,
            'is_published' => (bool) $p->is_published,
            'likes_count' => (int) ($p->likes_count ?? $p->likes()->count()),
            'liked_by_me' => (bool) ($p->liked_by_me ?? false),
            'created_at' => $p->created_at?->toIso8601String(),
            'students' => StudentBriefResource::collection(
                $this->whenLoaded('students', fn () => $p->students)
            ),
            'supervisor' => $this->whenLoaded('supervisor', function () use ($p, $request) {
                return $p->supervisor
                    ? (new TeacherBriefResource($p->supervisor))->toArray($request)
                    : null;
            }),
            'created_by' => $this->whenLoaded('createdBy', function () use ($p, $request) {
                if (! $p->createdBy) {
                    return null;
                }
                $u = $p->createdBy;

                return array_merge((new StudentBriefResource($u))->toArray($request), [
                    'role' => $u->role->value,
                ]);
            }),
            'past_event' => $this->whenLoaded('campusEvent', function () use ($p) {
                if (! $p->campusEvent) {
                    return null;
                }
                $e = $p->campusEvent;

                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'date_time' => $e->date_time->toIso8601String(),
                    'genres' => $e->genres ?? [],
                    'location' => $e->location,
                ];
            }),
        ];
    }
}
