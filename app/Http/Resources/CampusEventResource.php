<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampusEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $e = $this->resource;

        return [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'date_time' => $e->date_time->toIso8601String(),
            'location' => $e->location,
            'created_by' => $this->when(
                $e->relationLoaded('createdBy') && $e->createdBy,
                fn () => new UserResource($e->createdBy)
            ),
        ];
    }
}
