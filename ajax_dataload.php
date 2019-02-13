<?php

include_once __DIR__.'/core.php';

// Informazioni fondamentali
$columns = filter('columns');
$order = filter('order')[0];

$order['column'] = $order['column'] - 1;
array_shift($columns);

$total = Util\Query::readQuery($structure);

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

$query = Util\Query::getQuery($structure);
if (!empty($query)) {
    // CONTEGGIO TOTALE
    $results['recordsTotal'] = $dbo->fetchNum($query);

    // RISULTATI VISIBILI
    $query = Util\Query::getQuery($structure, $search, $order, $limit);

    // Filtri derivanti dai permessi (eventuali)
    if (empty($id_plugin)) {
        $query = Modules::replaceAdditionals($id_module, $query);
    }

    $query = str_replace_once('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $query);
    $rows = $dbo->fetchArray($query);

    // Conteggio dei record filtrati
    $count = $dbo->fetchArray('SELECT FOUND_ROWS()');
    if (!empty($count)) {
        $results['recordsFiltered'] = $count[0]['FOUND_ROWS()'];
    }

    // SOMME
    $results['summable'] = Util\Query::getSums($structure, $search);

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
                    $column['data-link'] = $rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.$hash;
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

$json = json_encode($results);
echo $json;
