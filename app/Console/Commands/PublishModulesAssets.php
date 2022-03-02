<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\SplFileInfo;

class PublishModulesAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osm:publish {--force : Overwrite any existing files}
                    {--all : Publish assets for all modules without prompt}
                    {--module=* : One or many modules slug that have assets you want to publish}
                    {--dev : Publish assets in development mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish OSM modules assets';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(private readonly Controller $controller)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modules = $this->controller->getModules()
            ->pluck('extra.osm-modules')
            ->map(fn($item) => reset($item))
            ->pluck('moduleName');

        $selected = $this->option('module');
        if ($selected) {
            $modules = $modules->filter(fn(string $slug) => in_array($slug, $selected, true));
            if ($modules->isEmpty()) {
                $this->error('No modules found with the given slug');

                return Command::FAILURE;
            }
        }

        if (!$selected && !$this->option('all')) {
            $modules = $this->choice(
                'Which modules do you want to publish assets? (separate by comma for multiple values)',
                $modules->toArray(),
                null,
                null,
                true
            );
        }

        // Check boolean options
        $dev = $this->option('dev');
        $force = $this->option('force');

        $replacement = "from '../../../build/index.js'";
        if ($dev) {
            $url = config('vite.configs.default.dev_server.url');
            $replacement = "from '$url/resources/js/index.ts'";
        }

        foreach ($modules as $module) {
            $tag = "$module:assets";

            $this->call('vendor:publish', [
                '--tag' => $tag,
                '--force' => $force,
            ]);

            $patch_failures = new Collection();

            $dirs = ServiceProvider::pathsToPublish(null, $tag);
            collect($dirs)
                ->flatMap(fn($dir) => File::allFiles($dir))
                ->filter(fn(SplFileInfo $file) => $file->getExtension() === 'js')
                ->each(function (SplFileInfo $file) use ($replacement, $patch_failures) {
                    $content = str($file->getContents())
                        ->replaceMatches("/from [\"']openstamanager[\"']/", $replacement)
                        ->replaceMatches(
                            "/from [\"']@(?<vendor>[\w.-]+)\/(?<module>[\w.-]+)[\"']/",
                            "from '../../$1/$2/index.js'"
                        );

                    if (!File::put($file->getPathname(), $content)) {
                        $patch_failures->push($file->getPathname());
                    }
                });

            if ($patch_failures->isNotEmpty()) {
                $this->error("Failed to patch the following assets of '$module' module:");
                $this->error($patch_failures->implode("\n"));
            } else {
                $this->info("Successfully patched the assets of '$module' module.");
            }
        }

        return Command::SUCCESS;
    }
}
