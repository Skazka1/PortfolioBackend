<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBriefResource extends JsonResource
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
            'year_of_graduation' => $u->year_of_graduation,
            'avatar_url' => $u->avatar_path ? $u->avatar_url : null,
            'bio' => $u->bio,
        ];
    }
}
