<?php

namespace App\Http\Resources;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Visit */
class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->summary();
    }
}
