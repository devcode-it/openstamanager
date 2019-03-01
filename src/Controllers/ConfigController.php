<?php

namespace Controllers;

class ConfigController extends Controller
{
    public function info($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }
}
