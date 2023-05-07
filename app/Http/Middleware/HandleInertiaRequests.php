<?php

/** @noinspection PropertyInitializationFlawsInspection */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use JetBrains\PhpStorm\ArrayShape;

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
    #[ArrayShape([0 => 'array|\Closure[]', 'locale' => Closure::class])]
    final public function share(Request $request): array
    {
        return [...parent::share($request), 'locale' => static fn () => app()->getLocale()];
    }
}
