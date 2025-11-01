<?php

namespace Controllers;

use Spatie\RouteAttributes\Attributes\Get;

class ExamplePageController extends PageController
{
    #[Get('/info')]
    public function about()
    {
        return 'This is the About Page!';
    }
}