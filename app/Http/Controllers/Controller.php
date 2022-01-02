<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Nette\Utils\Json;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function getModules(Request $request, ?array $filter = null): JsonResponse|Collection
    {
        $packages = collect(Json::decode(File::get(base_path('vendor/composer/installed.json')))->packages);

        $modules = $packages->filter(fn ($package) => $package->type === 'openstamanager-module');

        $modules->transform(function ($module) {
            $drawer_entries = [];

            foreach ($module->extra->{'osm-modules'} as $id => $data) {
                $drawer_entries[] = config("$id.drawer_entries");
            }

            $module->drawer_entries = array_merge(...array_filter($drawer_entries, static fn ($entry) => $entry !== null));

            return $module;
        });

        $filtered = $modules->only($filter);

        return $request->wantsJson() ? response()->json($filtered) : $filtered;
    }
}
