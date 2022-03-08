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

    public function getModules(?Request $request = null): JsonResponse|Collection
    {
        $packages = collect(Json::decode(File::get(base_path('vendor/composer/installed.json')))->packages);

        $modules = $packages->filter(fn($package) => $package->type === 'openstamanager-module');

        $modules->transform(function ($module) {
            $osm_modules = collect($module->extra->{'osm-modules'});

            $module->config = $osm_modules
                ->mapWithKeys(
                    fn($item, $name) => config($name) ?? include base_path("vendor/$module->name/config/$name.php")
                )
                ->reject(null)
                ->all();

            // Modules
            $module->modules = $osm_modules->map(function ($item, $key) use ($module) {
                $split = explode('/', $module->name, 2);
                $item->moduleFullName = $module->name;
                $item->moduleVendor = $split[0];
                $item->moduleName = $key;

                return $item;
            });

            return $module;
        });

        $filtered = $modules->only($request?->input('filter'));

        return ($request && $request->wantsJson()) ? response()->json($filtered) : $filtered;
    }
}
