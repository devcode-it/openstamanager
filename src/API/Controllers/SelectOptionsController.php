<?php

namespace API\Controllers;

use DTO\SelectOptionsRequest;

abstract class SelectOptionsController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $body = $this->init($request, SelectOptionsRequest::class);
        
        $where = [];
        $filter = [];
        $search_fields = [];

        $custom = [
            'id' => 'id',
            'text' => 'descrizione',
        ];

        require $file;

        if (!isset($results) && !empty($query)) {
            $results = \AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        }

        return new JsonResponse($results ?? null);
    }


    protected function hasAccess($request): bool
    {
        return true;
    }
}