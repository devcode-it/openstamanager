<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteVendorInModulesMirror extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-vendor-in-modules-mirror';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete vendor in modules mirror during development (this speeds up indexing)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modules = app(Controller::class)->getModules();

        foreach ($modules as $module) {
            $this->info("Deleting {$module['slug']} vendor directory...");
            $result = File::deleteDirectory($module['modulePath'].'/vendor');
            if ($result) {
                $this->info("Deleted {$module['slug']} vendor directory.");
            } elseif (File::exists($module['modulePath'].'/vendor')) {
                $this->error("Failed to delete {$module['slug']} vendor directory.");
            } else {
                $this->info("{$module['slug']} vendor directory already deleted.");
            }
        }

        $this->info('Done');
    }
}
