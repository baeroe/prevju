<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $sites = QueryBuilder::for(Site::withVisitStats())
            ->allowedFilters(
                AllowedFilter::exact('project_id')->nullable(),
                AllowedFilter::partial('name'),
            )
            ->allowedSorts('name', 'created_at', 'updated_at', 'last_viewed_at', 'view_count')
            ->defaultSort('-updated_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return SiteResource::collection($sites);
    }

    public function store(Request $request)
    {
        $site = (new Site)->applySettings($request->validate(Site::rules(creating: true)));
        $site->save();

        return SiteResource::make($site->refresh());
    }

    public function show(Site $site)
    {
        return SiteResource::make($site);
    }

    public function update(Request $request, Site $site)
    {
        $site->applySettings($request->validate(Site::rules()))->save();

        return SiteResource::make($site);
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return response()->noContent();
    }
}
