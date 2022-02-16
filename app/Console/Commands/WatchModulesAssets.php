<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Watcher\Watch;

class WatchModulesAssets extends Command
{
    protected $signature = 'osm:watch {--D : Watch in development mode (fix paths for use with Vite dev server)}';

    protected $description = 'Watch module(s) assets and automatically publish and fix their paths';

    public function __construct(private Controller $controller)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $modules = $this->controller->getModules()
            ->pluck('extra.osm-modules')
            ->map(fn($item) => reset($item))
            ->pluck('moduleName');

        $dev = $this->option('D');
        $publishes = collect(ServiceProvider::publishableGroups());

        $paths = [];
        $callbacks = [];
        foreach ($modules as $module) {
            $module_assets = $publishes->filter(fn($item) => Str::startsWith($item[0], $module));
            $paths[] = $module_assets->keys()->toArray();

            $callbacks[] = fn() => $this->call('osm:publish', [
                '--module' => $module,
                '--D' => $dev,
                '--force' => true,
            ]);
        }

        $watch = Watch::paths(...Arr::flatten($paths));
        foreach ($callbacks as $callback) {
            $watch->onAnyChange(function () use ($callback) {
                $this->info('Change detected! Publishing assets...');
                $callback();
                $this->call('osm:dev-server-fix');
            });
        }
        $watch->start();
    }
}
