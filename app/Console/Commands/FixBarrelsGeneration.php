<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixBarrelsGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osm:barrels-generation-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix barrels generation by automatically removing /index at the end of an export';

    /**
     * Execute the console command.
     */
    final public function handle(): int
    {
        foreach ($this->glob(resource_path('js/**/index.ts'), GLOB_NOSORT) as $file) {
            $content = File::get($file);
            File::put($file, str_replace('/index', '', $content));
        }

        return Command::SUCCESS;
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array|false
     *
     * @source https://gist.github.com/funkjedi/3feee27d873ae2297b8e2370a7082aad
     */
    private function glob(string $pattern, int $flags = 0): bool|array
    {
        if (!str_contains($pattern, '**')) {
            $files = File::glob($pattern, $flags);
        } else {
            $position = strpos($pattern, '**');
            $rootPattern = substr($pattern, 0, $position - 1);
            $restPattern = substr($pattern, $position + 2);

            $patterns = [$rootPattern . $restPattern];
            $rootPattern .= '/*';
            while ($dirs = File::glob($rootPattern, GLOB_ONLYDIR)) {
                $rootPattern .= '/*';
                foreach ($dirs as $dir) {
                    $patterns[] = $dir . $restPattern;
                }
            }
            $files = [];
            foreach ($patterns as $pat) {
                $files[] = $this->glob($pat, $flags);
            }
            $files = array_merge(...$files);
        }
        return array_unique($files);
    }
}
