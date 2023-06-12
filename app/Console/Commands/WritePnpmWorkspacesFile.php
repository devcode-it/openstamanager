<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class WritePnpmWorkspacesFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osm:write-pnpm-workspaces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write PNPM workspaces file for additional modules dependencies installation';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modules = app(Controller::class)->getModules();
        $workspaces_file = base_path('pnpm-workspace.yaml');
        $workspaces = Yaml::parseFile($workspaces_file);

        $workspaces['packages'] = [];
        foreach ($modules as $module) {
            $workspaces['packages'][] = relative_path(base_path(), $module['modulePath']);
        }

        File::put($workspaces_file, Yaml::dump($workspaces));

        $this->info('Done');
    }
}
