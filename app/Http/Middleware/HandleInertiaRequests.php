<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Nette\Utils\Json;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'locale' => fn () => app()->getLocale(),
            'translations' => function () {
                $json = resource_path('lang/'.app()->getLocale().'.json');
                if (!is_file($json)) {
                    return [];
                }

                return Json::decode(file_get_contents($json));
            },
        ]);
    }

    public function rootView(Request $request): string
    {
        if (in_array($request->route()?->uri(), ['setup', 'login'], true)) {
            return 'external';
        }
        return $this->rootView;
    }
}
