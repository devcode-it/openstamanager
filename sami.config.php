<?php

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;
use Sami\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('.couscous')
    ->exclude('node_modules')
    ->exclude('vendor')
    ->exclude('tests')
    ->in(__DIR__)
;

$sami = new Sami($iterator, array(
    'theme'                => 'default',
    'title'                => 'OpenSTAManager',
	'build_dir'            => __DIR__.'/.couscous/generated/docs',
    'cache_dir'            => __DIR__.'/.couscous/cache',
    'default_opened_level' => 2,
));

$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;
