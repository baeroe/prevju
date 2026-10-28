<?php

namespace App\Http\Resources;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Site */
class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [...$this->summary(), ...$this->visitStats()];
    }
}
