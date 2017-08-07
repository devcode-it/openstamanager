<?php

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'short'),
    ))
    ->setFinder(
		PhpCsFixer\Finder::create()
			->files()
			->in(__DIR__)
			->exclude('vendor')
			->exclude('resources/views')
			->exclude('storage')
			->exclude('public')
			->notName("*.txt")
			->ignoreDotFiles(true)
			->ignoreVCS(true)
	)
;
