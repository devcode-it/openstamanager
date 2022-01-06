<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DevServerFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osm:dev-server-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix modules before starting the dev server';

    /**
     * Execute the console command.
     */
    final public function handle(): int
    {
        foreach (glob(resource_path('static/vendor') . '/*/*/index.js', GLOB_NOSORT) as $file) {
            $content = File::get($file);
            File::put($file, str_replace('../../../index.js', '/resources/js/index.ts', $content));
        }

        return Command::SUCCESS;
    }
}
