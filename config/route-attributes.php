<?php

return [
    /*
     *  Automatic registration of routes will only happen if this setting is `true`
     */
    'enabled' => true,

    /*
     * Controllers in these directories that have routing attributes
     * will automatically be registered.
     *
     * Optionally, you can specify group configuration by using key/values
     */
    'directories' => [
        realpath(__DIR__ . '/../src/Controllers'),
    ],

    /*
     * This middleware will be applied to all routes.
     */
    'middleware' => [
        \Middlewares\OSMAuthMiddleware::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'auth:sanctum'
    ],

    /*
     * When enabled, implicitly scoped bindings will be enabled by default.
     * You can override this behaviour by using the `ScopeBindings` attribute, and passing `false` to it.
     *
     * Possible values:
     *  - null: use the default behaviour
     *  - true: enable implicitly scoped bindings for all routes
     *  - false: disable implicitly scoped bindings for all routes
     */
    'scope-bindings' => null,
];