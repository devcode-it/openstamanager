<?php

namespace Controllers;

use AJAX;
use Modules;
use Util\Query;

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
        $result = '{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "id_plugin": "'.$id_plugin.'" )}';
        $response = $response->write($result);

        return $response;
    }

    public function activeUsers($request, $response, $args)
    {
        $posizione = get('id_module');
        if (isset($id_record)) {
            $posizione .= ', '.get('id_record');
        }

        $user = Auth::user();
        $interval = setting('Timeout notifica di presenza (minuti)') * 60 * 2;

        $this->database->query('UPDATE zz_semaphores SET updated = NOW() WHERE id_utente = :user_id AND posizione = :position', [
            ':user_id' => $user['id'],
            ':position' => $posizione,
        ]);

        // Rimozione record scaduti
        $this->database->query('DELETE FROM zz_semaphores WHERE DATE_ADD(updated, INTERVAL :interval SECOND) <= NOW()', [
            ':interval' => $interval,
        ]);

        $datas = $this->database->fetchArray('SELECT DISTINCT username FROM zz_semaphores INNER JOIN zz_users ON zz_semaphores.id_utente=zz_users.id WHERE zz_semaphores.id_utente != :user_id AND posizione = :position', [
            ':user_id' => $user['id'],
            ':position' => $posizione,
        ]);

        $response = $response->write(json_encode($datas));

        return $response;
    }

    public function sessionSet($request, $response, $args)
    {
        $array = explode(',', get('session'));
        $value = get('value');
        $clear = get('clear');

        if ($clear == 1 || $value == '') {
            unset($_SESSION[$array[0]][$array[1]]);
        } else {
            $_SESSION[$array[0]][$array[1]] = $value;
        }

        return $response;
    }

    public function sessionSetArray($request, $response, $args)
    {
        $array = explode(',', get('session'));
        $value = "'".get('value')."'";
        $inversed = get('inversed');

        $found = false;

        // Ricerca valore nell'array
        foreach ($_SESSION[$array[0]][$array[1]] as $idx => $val) {
            // Se il valore esiste lo tolgo
            if ($val == $value) {
                $found = true;

                if ((int) $inversed == 1) {
                    unset($_SESSION[$array[0]][$array[1]][$idx]);
                }
            }
        }

        if (!$found) {
            array_push($_SESSION[$array[0]][$array[1]], $value);
        }

        return $response;
    }

    public function dataLoad($request, $response, $args)
    {
        $args['module_id'] = filter('id_module');
        $args['plugin_id'] = filter('id_plugin');

        $args = ModuleController::argsPrepare($args);
        extract($args);

        // Informazioni fondamentali
        $columns = filter('columns');
        $order = filter('order')[0];

        $order['column'] = $order['column'] - 1;
        array_shift($columns);

        $total = Query::readQuery($structure);

        // Ricerca
        $search = [];
        for ($i = 0; $i < count($columns); ++$i) {
            if (!empty($columns[$i]['search']['value'])) {
                $search[$total['fields'][$i]] = $columns[$i]['search']['value'];
            }
        }

        $limit = [
            'start' => filter('start'),
            'length' => filter('length'),
        ];

        // Predisposizione della risposta
        $results = [
            'data' => [],
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'summable' => [],
        ];

        $query = Query::getQuery($structure);
        if (!empty($query)) {
            // CONTEGGIO TOTALE
            $results['recordsTotal'] = $this->database->fetchNum($query);

            // RISULTATI VISIBILI
            $query = Query::getQuery($structure, $search, $order, $limit);

            // Filtri derivanti dai permessi (eventuali)
            if (empty($id_plugin)) {
                $query = Modules::replaceAdditionals($id_module, $query);
            }

            // Conteggio dei record filtrati
            $data = Query::executeAndCount($query);
            $rows = $data['results'];
            $results['recordsFiltered'] = $data['count'];

            // SOMME
            $results['summable'] = Query::getSums($structure, $search);

            // Allineamento delle righe
            $align = [];
            $row = $rows[0] ?: [];
            foreach ($row as $field => $value) {
                $value = trim($value);

                // Allineamento a destra se il valore della prima riga risulta numerica
                if (formatter()->isStandardNumber($value)) {
                    $align[$field] = 'text-right';
                }

                // Allineamento al centro se il valore della prima riga risulta relativo a date o icone
                elseif (formatter()->isStandardDate($value) || preg_match('/^icon_(.+?)$/', $field)) {
                    $align[$field] = 'text-center';
                }
            }

            // Creazione della tabella
            foreach ($rows as $i => $r) {
                $result = [
                    '<span class="hide" data-id="'.$r['id'].'"></span>', // Colonna ID
                ];

                foreach ($total['fields'] as $pos => $field) {
                    $column = [];

                    if (!empty($r['_bg_'])) {
                        $column['data-background'] = $r['_bg_'];
                    }

                    // Allineamento
                    if (!empty($align[$field])) {
                        $column['class'] = $align[$field];
                    }

                    $value = trim($r[$field]);

                    // Formattazione automatica
                    if (!empty($total['format'][$pos]) && !empty($value)) {
                        if (formatter()->isStandardDate($value)) {
                            $value = Translator::dateToLocale($value);
                        } elseif (formatter()->isStandardTime($value)) {
                            $value = Translator::timeToLocale($value);
                        } elseif (formatter()->isStandardTimestamp($value)) {
                            $value = Translator::timestampToLocale($value);
                        } elseif (formatter()->isStandardNumber($value)) {
                            $value = Translator::numberToLocale($value);
                        }
                    }

                    // Icona
                    if (preg_match('/^color_(.+?)$/', $field, $m)) {
                        $value = isset($r['color_title_'.$m[1]]) ? $r['color_title_'.$m[1]] : '';

                        $column['class'] = 'text-center small';
                        $column['data-background'] = $r[$field];
                    }

                    // Icona di stampa
                    elseif ($field == '_print_') {
                        $print = $r['_print_'];

                        $print_url = Prints::getHref($print, $r['id']);

                        $value = '<a href="'.$print_url.'" target="_blank"><i class="fa fa-2x fa-print"></i></a>';
                    }

                    // Icona
                    elseif (preg_match('/^icon_(.+?)$/', trim($field), $m)) {
                        $value = '<span class=\'label text-black\' style=\'font-weight:normal;\'  ><i class="'.$r[$field].'" title="'.$r['icon_title_'.$m[1]].'" ></i> <span>'.$r['icon_title_'.$m[1]].'</span></span>';
                    }

                    // Colore del testo
                    if (!empty($column['data-background'])) {
                        $column['data-color'] = isset($column['data-color']) ? $column['data-color'] : color_inverse($column['data-background']);
                    }

                    // Link della colonna
                    if ($field != '_print_') {
                        $id_record = $r['id'];
                        $hash = '';
                        if (!empty($r['_link_record_'])) {
                            $id_module = $r['_link_module_'];
                            $id_record = $r['_link_record_'];
                            $hash = !empty($r['_link_hash_']) ? '#'.$r['_link_hash_'] : '';
                            unset($id_plugin);
                        }

                        // Link per i moduli
                        if (empty($id_plugin)) {
                            $column['data-link'] = pathFor('module-record', [
                                'module_id' => $id_module,
                                'record_id' => $id_record,
                            ]).$hash;
                        }
                        // Link per i plugin
                        else {
                            $column['data-link'] = $rootdir.'/add.php?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&id_parent='.$id_parent.'&edit=1'.$hash;

                            $column['data-type'] = 'dialog';
                        }
                    }

                    $attributes = [];
                    foreach ($column as $key => $val) {
                        $val = is_array($val) ? implode(' ', $val) : $val;
                        $attributes[] = $key.'="'.$val.'"';
                    }

                    // Replace rootdir per le query
                    $value = str_replace('ROOTDIR', ROOTDIR, $value);
                    $result[] = str_replace('|attr|', implode(' ', $attributes), '<div |attr|>'.$value.'</div>');
                }

                $results['data'][] = $result;
            }
        }

        $response = $response->write(json_encode($results));

        return $response;
    }
}
