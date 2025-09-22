<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/core.php';

use Models\Module;
use Util\Query;

// Informazioni fondamentali
$columns = (array) filter('columns', null, true);
$order = filter('order') ? filter('order')[0] : [];
$draw_numer = intval(filter('draw'));

if (!empty(filter('order'))) {
    $order['column'] = $order['column'] - 1;
}
$_SESSION['module_'.$id_module]['order'] = $order;
array_shift($columns);

$total = Query::readQuery($structure);

// Ricerca
$search = [];
for ($i = 0; $i < count($columns); ++$i) {
    if (!empty($columns[$i]['search']['value']) || $columns[$i]['search']['value'] == '0') {
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
    'avg' => [],
    'draw' => $draw_numer,
];

$query = Query::getQuery($structure, [], [], [], $total);
if (!empty($query)) {
    // CONTEGGIO TOTALE
    $results['recordsTotal'] = $dbo->fetchNum($query);

    // RISULTATI VISIBILI
    $query = Query::getQuery($structure, $search, $order, $limit, $total);

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

    // MEDIE
    $results['avg'] = Query::getAverages($structure, $search);

    // Allineamento delle righe
    $align = [];
    $row = isset($rows[0]) ? $rows[0] : [];
    foreach ($row as $field => $value) {
        if (!empty($value)) {
            $value = trim($value);
        }

        // Allineamento a destra se il valore della prima riga risulta numerica
        if (is_numeric($value) && formatter()->isStandardNumber($value)) {
            $align[$field] = 'text-right';
        }

        // Allineamento al centro se il valore della prima riga risulta relativo a date o icone
        elseif (formatter()->isStandardDate($value) || preg_match('/^icon_(.+?)$/', $field)) {
            $align[$field] = 'text-center';
        }
    }

    // Creazione della tabella
    foreach ($rows as $i => $r) {
        // Evitare risultati con ID a null
        // Codice non applicabile in ogni caso: sulla base dei permessi, ID può non essere impostato
        /*
        if (empty($r['id'])) {
            continue;
        }*/

        $result = [
            'id' => $r['id'],
            '<span class="hide" data-id="'.$r['id'].'"></span>', // Colonna ID
        ];

        foreach ($total['fields'] as $pos => $field) {
            $column = [];

            if (!empty($r['_bg_'])) {
                if (preg_match('/-light$/', $r['_bg_'])) {
                    $column['data-background'] = substr($r['_bg_'], 0, -6); // Remove the "-light" suffix from the word
                } else {
                    $column['data-background'] = $r['_bg_'];
                }
            }

            // Allineamento
            if (!empty($align[$field])) {
                $column['class'] = $align[$field];
            }

            $value = trim((string) $r[$field]);

            // Formattazione HTML
            if (empty($total['html_format'][$pos]) && !empty($value)) {
                $value = strip_tags($value ?: '');
            }

            // Formattazione automatica
            if (!empty($total['format'][$pos]) && !empty($value)) {
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
            if (preg_match('/^color_(.+?)$/', $field, $m)) {
                $value = isset($r['color_title_'.$m[1]]) ? $r['color_title_'.$m[1]] : '';

                // Formattazione automatica
                if (!empty($total['format'][$pos]) && !empty($value)) {
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
                $column['data-background'] = $r[$field];
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

                    $url = file_exists(base_dir().$image_thumbnail) ? base_path().$image_thumbnail : base_path().$image;

                    $value = '<img src="'.$url.'" style="max-height: 80px; max-width:120px">';
                }
            }

            // Icona
            elseif (preg_match('/^icon_(.+?)$/', trim($field), $m)) {
                $value = '<span class=\'badge text-black\' style=\'font-weight:normal;\'  ><i class="'.$r[$field].'" title="'.$r['icon_title_'.$m[1]].'" ></i> <span>'.$r['icon_title_'.$m[1]].'</span></span>';
            }

            // Colore del testo
            if (!empty($column['data-background'])) {
                $column['data-color'] = isset($column['data-color']) ? $column['data-color'] : color_inverse(trim($column['data-background']));
            } elseif (preg_match('/^mailto_(.+?)$/', trim($field), $m)) {
                $column['class'] = '';
                $value = ($r[$field] ? '<a class="btn btn-default btn-sm btn-block" style="font-weight:normal;" href="mailto:'.$r[$field].'" target="_blank"><i class="fa fa-envelope text-primary"></i> '.$r[$field].'</a>' : '');
            } elseif (preg_match('/^tel_(.+?)$/', trim($field), $m)) {
                $column['class'] = '';
                $value = ($r[$field] ? '<a class="btn btn-default btn-sm btn-block"  href="tel:'.$r[$field].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$r[$field].'</a>' : '');
            }

            // Link della colonna
            if ($field != '_print_' && !preg_match('/^tel_(.+?)$/', trim($field), $m) && !preg_match('/^mailto_(.+?)$/', trim($field), $m)) {
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
                    $column['data-link'] = base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash;
                }
                // Link per i plugin
                else {
                    $column['data-link'] = base_path().'/add.php?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&id_parent='.$id_parent.'&edit=1'.$hash;

                    $column['data-type'] = 'dialog';
                }
            }

            $attributes = [];
            foreach ($column as $key => $val) {
                $val = is_array($val) ? implode(' ', $val) : $val;
                $attributes[] = $key.'="'.$val.'"';
            }

            // Replace base_link() per le query
            $value = str_replace('base_link()', base_path(), $value);
            $result[] = str_replace('|attr|', implode(' ', $attributes), '<div |attr|>'.$value.'</div>');
        }

        $results['data'][] = $result;
    }
}

$json = json_encode($results);
echo $json;
