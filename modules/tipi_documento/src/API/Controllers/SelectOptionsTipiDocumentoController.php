<?php

namespace Modules\TipiDocumento\API\Controllers;

use API\Controllers\BaseController;
use DTO\SelectOptionsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Models\Locale;
use Symfony\Component\HttpKernel\Exception\InvalidMetadataException;

final class SelectOptionsTipiDocumentoRequest extends SelectOptionsRequest
{
    public string $dir;
}

final class SelectOptionsTipiDocumentoController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $body = $this->init($request, SelectOptionsTipiDocumentoRequest::class);
        $dir = $body->dir;

        if (empty($dir)) {
            throw new InvalidMetadataException('Missing dir option');
        }

        $query = 'SELECT `co_tipi_documento`.`id`, `co_tipi_documento_lang`.`title` AS descrizione FROM `co_tipi_documento` LEFT JOIN `co_tipi_documento_lang` ON (`co_tipi_documento`.`id` = `co_tipi_documento_lang`.`id_record` AND `co_tipi_documento_lang`.`id_lang` = '.prepare(Locale::getDefault()->id).') |where| ORDER BY `title` ASC';

        $where = ['`co_tipi_documento`.`enabled` = 1',  '`co_tipi_documento`.`dir`='.prepare($dir)];
        $filter = [];
        $search_fields = [];
        foreach ((array) $body->retrieve_only_for as $element) {
            $filter[] = '`co_tipi_documento`.`id`='.prepare($element);
        }

        if (!empty($body->search)) {
            $search_fields[] = '`co_tipi_documento_lang`.`title` LIKE '.prepare('%'.$body->search.'%');
        }

        $custom = [
            'id' => 'id',
            'text' => 'descrizione',
        ];
        $length = 200;
        $query_results = \AJAX::selectResults($query, $where, $filter, $search_fields, [
            'offset' => $body->page * $length,
            'length' => $length,
        ], $custom);

        $results = $query_results['results'];

        // Applicazione della trasformazione dei link se specificata nelle opzioni
        $link = 'module:Tipi documento';
        if (!empty($link) && !empty($results)) {
            $results = \AJAX::applyLinkTransformation($results, $link);
        }

        return new JsonResponse([
            'results' => $results ?: [],
            'recordsFiltered' => $query_results['recordsFiltered'],
        ]);
    }

    protected function hasAccess($request): bool
    {
        return $this->hasModuleWriteAccess('Tipi documento');
    }
}
