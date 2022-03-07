<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

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
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    final public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'locale' => fn () => app()->getLocale(),
        ]);
    }

    final public function rootView(Request $request): string
    {
        /** @var array{external?: bool}|null $route_props */
        $route_props = $request->route('props');

        if (Arr::get($route_props, 'external', false)) {
            return 'external';
        }

        return $this->rootView;
    }
}
