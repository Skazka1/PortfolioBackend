<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectPreviewResource extends JsonResource
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
            'preview_image_url' => $p->preview_image_path ? $p->preview_image_url : null,
            'technologies' => $p->technologies,
        ];
    }
}
