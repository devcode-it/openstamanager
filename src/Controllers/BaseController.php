<?php

namespace Controllers;

class BaseController extends Controller
{
    public function index($request, $response, $args)
    {
        $response = $this->view->render($response, 'index.php', $args);

        return $response;
    }

    public function info($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }
}
