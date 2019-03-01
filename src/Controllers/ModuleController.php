<?php

namespace Controllers;

class ModuleController extends Controller
{
    public function index($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }
}
