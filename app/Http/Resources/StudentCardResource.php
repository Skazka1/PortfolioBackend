<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $u = $this->resource;

        return [
            'id' => $u->id,
            'name' => $u->name,
            'course' => $u->course,
            'group' => $u->group,
            'avatar_url' => $u->avatar_path ? $u->avatar_url : null,
            'last_projects' => ProjectPreviewResource::collection(
                $this->whenLoaded('lastProjects', fn () => $u->lastProjects)
            ),
        ];
    }
}
