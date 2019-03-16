<?php

namespace Controllers;

class ModuleController extends Controller
{
    public function index($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\controller.php', $args);

        return $response;
    }

    public function edit($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\editor.php', $args);

        return $response;
    }

    public function saveRecord($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\actions.php', $args);

        return $response;
    }

    public function add($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\add.php', $args);

        return $response;
    }

    public function addRecord($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\actions.php', $args);

        $response = $response->withRedirect($this->router->pathFor('module-record'));

        return $response;
    }
}
