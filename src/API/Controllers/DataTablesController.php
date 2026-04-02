<?php

namespace API\Controllers;

use ApiPlatform\Metadata\Post;
use DTO\DataTablesLoadRequest\Column;
use DTO\DataTablesLoadRequest\DataTablesLoadRequest;
use DTO\DataTablesLoadResponse\DataTablesLoadResponse;
use Models\Module;
use Util\Query;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


#[Post(
    uriTemplate: '/datatables/list/{id_module}/{id_plugin}/{id_parent}',
    controller: DataTablesController::class,
    input: DataTablesLoadRequest::class,
    output: DataTablesLoadResponse::class,
)]
class DataTablesResource
{
}

final class DataTablesController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request_body = $this->_cast($request, DataTablesLoadRequest::class);

        $id_module = (int) $request_body->getIdModule();
        $id_plugin = (int) $request_body->getIdPlugin();
        $id_parent = (int) $request_body->getIdParent();

        $module = \Modules::get($id_module);
        \Modules::setCurrent($id_module);
        
        $plugin = null;
        if (!empty($id_plugin)) {
            \Plugins::setCurrent($id_plugin);
            $plugin = \Plugins::get($id_plugin);
        }

        $structure = $plugin ?? $module;

        return new JsonResponse($this->retrieveRecords($structure, $request_body, $id_module, $id_plugin, $id_parent));
    }

    /*
    public function process(mixed $request, Operation $operation, array $uriVariables = [], array $context = []): DataTablesLoadResponse
    {
        if (!$request instanceof DataTablesLoadRequest) {
            throw new \InvalidArgumentException();
        }

        $id_module = !empty($uriVariables['id_module']) ? $uriVariables['id_module'] : null;
        $id_plugin = !empty($uriVariables['id_plugin']) ? $uriVariables['id_plugin'] : null;
        $id_parent = !empty($uriVariables['id_parent']) ? $uriVariables['id_parent'] : null;

        $module = \Modules::get($id_module);
        \Modules::setCurrent($id_module);

        $plugin = null;
        if (!empty($id_plugin)) {
            \Plugins::setCurrent($id_plugin);
            $plugin = \Plugins::get($id_plugin);
        }

        $structure = $plugin ?? $module;

        return $this->retrieveRecords($structure, $request, $id_module, $id_plugin, $id_parent);
    }*/

    private function retrieveRecords($structure, DataTablesLoadRequest $request, $id_module, $id_plugin, $id_parent): DataTablesLoadResponse
    {
        // Informazioni fondamentali
        $order = $request->order ? $request->order[0] : [];

        if (!empty($order)) {
            $order->column = $order->column - 1;
        }

        $order_array = $order->toArray();

        $query_structure = Query::readQuery($structure);

        $response = new DataTablesLoadResponse($request->draw);

        $query = Query::getQuery($structure, [], [], [], $query_structure);
        if (empty($query)) {
            return $response;
        }

        // Ricerca
        $search = [];
        $columns = $request->columns;
        array_shift($columns);
        for ($i = 0; $i < count($columns); ++$i) {
            $col = Column::fromArray($columns[$i]);
            if (!empty($col->search->value) || $col->search->value == '0') {
                $search[$query_structure['fields'][$i]] = $col->search->value;
            }
        }

        // CONTEGGIO TOTALE
        $response->recordsTotal = database()->fetchNum($query);

        // CONTEGGIO RECORD FILTRATI (senza LIMIT)
        $query_filtered = Query::getQuery($structure, $search, $order_array, [], $query_structure);
        if (empty($id_plugin)) {
            $query_filtered = \Modules::replaceAdditionals($id_module, $query_filtered);
        }
        $response->recordsFiltered = database()->fetchNum($query_filtered);

        // SOMME
        $response->summable = Query::getSums($structure, $search);

        // MEDIE
        $response->avg = Query::getAverages($structure, $search);

        $limit = [
            'start' => $request->start,
            'length' => $request->length,
        ];
        // RISULTATI VISIBILI (con LIMIT)
        $query = Query::getQuery($structure, $search, $order_array, $limit, $query_structure);

        // Filtri derivanti dai permessi (eventuali)
        if (empty($id_plugin)) {
            $query = \Modules::replaceAdditionals($id_module, $query);
        }

        // Esecuzione query per ottenere i risultati
        $data = Query::executeAndCount($query);
        $rows = $data['results'];

        // Allineamento delle righe
        $align = $this->detectAlignment($rows);
        // Creazione della tabella
        foreach ($rows as $i => $r) {
            $row_data = $this->getSingleRow($r, $query_structure, $align, $id_module, $id_plugin, $id_parent);
            $response->data[] = $row_data;
        }

        return $response;
    }

    private function detectAlignment(array $rows): array
    {
        // Allineamento delle righe
        $align = [];
        $row = $rows[0] ?? [];
        foreach ($row as $field => $value) {
            if (!empty($value)) {
                $value = trim((string) $value);
            }

            // Allineamento a destra se il valore della prima riga risulta numerica
            if (is_numeric($value) && formatter()->isStandardNumber($value)) {
                $align[$field] = 'text-right';
            }

            // Allineamento al centro se il valore della prima riga risulta relativo a date o icone
            elseif (formatter()->isStandardDate($value) || preg_match('/^icon_(.+?)$/', (string) $field)) {
                $align[$field] = 'text-center';
            }
        }

        return $align;
    }

    private function getSingleRow($r, $query_structure, $align, $id_module, $id_plugin, $id_parent): array
    {
        // Evitare risultati con ID a null
        // Codice non applicabile in ogni caso: sulla base dei permessi, ID può non essere impostato
        /*
        if (empty($r['id'])) {
            continue;
        }*/

        $result = [
            'id' => $r['id'],
            '<span class="hide" request-id="'.$r['id'].'"></span>', // Colonna ID
        ];

        foreach ($query_structure['fields'] as $pos => $field) {
            $column = [];

            if (!empty($r['_bg_'])) {
                if (preg_match('/-light$/', (string) $r['_bg_'])) {
                    $column['request-background'] = substr((string) $r['_bg_'], 0, -6); // Remove the "-light" suffix from the word
                } else {
                    $column['request-background'] = $r['_bg_'];
                }
            }

            // Allineamento
            if (!empty($align[$field])) {
                $column['class'] = $align[$field];
            }

            $value = trim((string) $r[$field]);

            // Formattazione HTML
            if (empty($query_structure['html_format'][$pos]) && !empty($value)) {
                $value = strip_tags($value ?: '');
            }

            // Formattazione automatica
            if (!empty($query_structure['format'][$pos]) && !empty($value)) {
                if (formatter()->isStandardTimestamp($value)) {
                    $value = Translator::timestampToLocale($value);
                } elseif (formatter()->isStandardDate($value)) {
                    $value = Translator::dateToLocale($value);
                } elseif (formatter()->isStandardTime($value)) {
                    $value = Translator::timeToLocale($value);
                } elseif (formatter()->isStandardNumber($value)) {
                    $value = Translator::numberToLocale($value);
                }
            }

            // Icona
            if (preg_match('/^color_(.+?)$/', (string) $field, $m)) {
                $value = $r['color_title_'.$m[1]] ?? '';

                // Formattazione automatica
                if (!empty($query_structure['format'][$pos]) && !empty($value)) {
                    if (formatter()->isStandardTimestamp($value)) {
                        $value = Translator::timestampToLocale($value);
                    } elseif (formatter()->isStandardDate($value)) {
                        $value = Translator::dateToLocale($value);
                    } elseif (formatter()->isStandardTime($value)) {
                        $value = Translator::timeToLocale($value);
                    } elseif (formatter()->isStandardNumber($value)) {
                        $value = Translator::numberToLocale($value);
                    }
                }

                $column['class'] = 'text-center small';
                $column['request-background'] = $r[$field];
            }

            // Icona di stampa
            elseif ($field == '_print_') {
                $print = $r['_print_'];

                $print_url = Prints::getHref($print, $r['id']);

                $value = '<a href="'.$print_url.'" target="_blank"><i class="fa fa-2x fa-print"></i></a>';
            }

            // Immagine
            elseif ($field == '_img_') {
                $module = Module::where('id', $id_module)->first();
                if (!empty($r['_img_'])) {
                    $fileinfo = Uploads::fileInfo($r['_img_']);

                    $directory = '/'.$module->upload_directory.'/';
                    $image = $directory.$r['_img_'];
                    $image_thumbnail = $directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

                    $url = file_exists(base_dir().$image_thumbnail) ? base_path_osm().$image_thumbnail : base_path_osm().$image;

                    $value = '<img src="'.$url.'" style="max-height: 80px; max-width:120px">';
                }
            }

            // Icona
            elseif (preg_match('/^icon_(.+?)$/', trim((string) $field), $m)) {
                $value = '<span class=\'badge text-black\' style=\'font-weight:normal;\'  ><i class="'.$r[$field].'" title="'.$r['icon_title_'.$m[1]].'" ></i> <span>'.$r['icon_title_'.$m[1]].'</span></span>';
            }

            // Colore del testo
            if (!empty($column['request-background'])) {
                $column['request-color'] ??= color_inverse(trim((string) $column['request-background']));
            } elseif (preg_match('/^mailto_(.+?)$/', trim((string) $field), $m)) {
                $column['class'] = '';
                $value = ($r[$field] ? '<a class="btn btn-default btn-sm btn-block" style="font-weight:normal;" href="mailto:'.$r[$field].'" target="_blank"><i class="fa fa-envelope text-primary"></i> '.$r[$field].'</a>' : '');
            } elseif (preg_match('/^tel_(.+?)$/', trim((string) $field), $m)) {
                $column['class'] = '';
                $value = ($r[$field] ? '<a class="btn btn-default btn-sm btn-block"  href="tel:'.$r[$field].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$r[$field].'</a>' : '');
            }

            // Link della colonna
            if ($field != '_print_' && !preg_match('/^tel_(.+?)$/', trim((string) $field), $m) && !preg_match('/^mailto_(.+?)$/', trim((string) $field), $m)) {
                $id_record = $r['id'];
                $hash = '';

                $id_module = !empty($r['_link_module_']) ? $r['_link_module_'] : $id_module;
                if (!empty($r['_link_record_'])) {
                    $id_record = $r['_link_record_'];
                    $hash = !empty($r['_link_hash_']) ? '#'.$r['_link_hash_'] : '';
                    unset($id_plugin);
                }

                // Link per i moduli
                if (empty($id_plugin)) {
                    $column['request-link'] = base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash;
                }
                // Link per i plugin
                else {
                    $column['request-link'] = base_path_osm().'/add.php?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&id_parent='.$id_parent.'&edit=1'.$hash;

                    $column['request-type'] = 'dialog';
                }
            }

            $attributes = [];
            foreach ($column as $key => $val) {
                $val = is_array($val) ? implode(' ', $val) : $val;
                $attributes[] = $key.'="'.$val.'"';
            }

            // Replace base_link() per le query
            $value = str_replace('base_link()', base_path_osm(), $value);
            $result[] = str_replace('|attr|', implode(' ', $attributes), '<div |attr|>'.$value.'</div>');
        }

        return $result;
    }
}
