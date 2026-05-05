<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $u->email,
            'role' => $u->role?->value,
            'course' => $u->course,
            'group' => $u->group,
            'year_of_graduation' => $u->year_of_graduation,
            'avatar_url' => $u->avatar_path ? $u->avatar_url : null,
            'bio' => $u->bio,
            'is_active' => (bool) $u->is_active,
            'email_verified_at' => $u->email_verified_at?->toIso8601String(),
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
