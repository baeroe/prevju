<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->summary(),
            ...$this->visitStats(),
            'sites' => SiteResource::collection($this->whenLoaded('sites')),
        ];
    }
}
