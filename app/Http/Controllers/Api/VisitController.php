<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\VisitResource;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VisitController extends Controller
{
    public function __invoke(Request $request)
    {
        $visits = QueryBuilder::for(Visit::class)
            ->allowedFilters(
                AllowedFilter::exact('site_id'),
                // the project page plus all its sites, like the admin shows it
                AllowedFilter::callback('project_id', fn (Builder $q, $id) => $q->forProject((int) $id)),
                AllowedFilter::exact('kind'),
                AllowedFilter::exact('visitor'),
                AllowedFilter::callback('since', fn (Builder $q, $date) => $q->where('created_at', '>=', self::date('since', $date))),
                AllowedFilter::callback('until', fn (Builder $q, $date) => $q->where('created_at', '<', self::date('until', $date))),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return VisitResource::collection($visits);
    }

    private static function date(string $filter, mixed $value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages(["filter.{$filter}" => "filter[{$filter}] must be a date like 2026-10-28 or 2026-10-28T14:00:00Z."]);
        }
    }
}
