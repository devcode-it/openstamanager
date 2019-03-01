<?php

namespace Controllers;

class AjaxController extends Controller
{
    public function select($request, $response, $args)
    {
        $op = empty($op) ? filter('op') : $op;
        $search = filter('search');
        $page = filter('page') ?: 0;
        $length = filter('length') ?: 100;
        $options = filter('superselect');

        if (!isset($elements)) {
            $elements = [];
        }
        $elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

        $results = AJAX::select($op, $elements, $search, $page, $length, $options);
        $response = $response->write(json_encode($results));

        return $response;
    }

    public function complete($request, $response, $args)
    {
        $module = get('module');
        $op = get('op');

        $result = AJAX::complete($op);
        $response = $response->write($result);

        return $response;
    }

    public function search($request, $response, $args)
    {
        $term = get('term');
        $term = str_replace('/', '\\/', $term);

        $results = AJAX::search($term);
        $response = $response->write(json_encode($results));

        return $response;
    }

    public function listAttachments($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }

    public function activeUsers($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }

    public function sessionSet($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }

    public function sessionSetArray($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }

    public function dataLoad($request, $response, $args)
    {
        $response = $this->view->render($response, 'resources\views\info.php', $args);

        return $response;
    }
}
