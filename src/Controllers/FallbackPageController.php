<?php

namespace Controllers;

use Spatie\RouteAttributes\Attributes\Any;
use Illuminate\Http\Request;

// Disabilitato in quanto è meglio gestire le nuove pagine con Laravel usando le strutture Laravel
class FallbackPageController extends PageController
{
    //#[Any('/')]
    public function index(Request $request)
    {
        return $this->__invoke($request, 'index.php');
    }

    //#[Any('{any}')]
    public function __invoke(Request $request, string $override = null)
    {
        $path = $override ?? $request->path();
        
        $root = realpath(__DIR__ . '/../../');
        $filepath = realpath($root .'/'. $path);

        if (str_starts_with($filepath, $root) && file_exists($filepath)) {
                ob_start();
            require_once $filepath;
            $s = ob_get_clean();
            var_dump($filepath);
            var_dump($s);
            return $s;
        }

        return 'Not Found';
    }
}