<?php

include_once __DIR__.'/../../core.php';

use Models\Module;

switch (filter('op')) {
    case 'update':
        $options2 = htmlspecialchars_decode(post('options2'), ENT_QUOTES);

        if (check_query($options2)) {
            $dbo->query('UPDATE `zz_modules` SET `title`='.prepare(post('title')).', `options2`='.prepare($options2).' WHERE `id`='.prepare($id_record));

            $rs = true;
        } else {
            $rs = false;
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'fields':
        $rs = true;

        // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
        $queries = (array) $_POST['query'];
        foreach ($queries as $c => $query) {
            if (check_query($query)) {
                $array = [
                    'name' => post('name')[$c],
                    'query' => $query,
                    'visible' => post('visible')[$c],
                    'search' => post('search')[$c],
                    'slow' => post('slow')[$c],
                    'format' => post('format')[$c],
                    'summable' => post('sum')[$c],
                    'search_inside' => post('search_inside')[$c],
                    'order_by' => post('order_by')[$c],
                    'id_module' => $id_record,
                ];

                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];

                    $dbo->update('zz_views', $array, ['id' => $id]);
                } elseif (!empty($query)) {
                    $array['order'] = orderValue('zz_views', 'id_module', $id_record);

                    $dbo->insert('zz_views', $array);

                    $id = $dbo->lastInsertedID();
                }

                // Aggiornamento dei permessi relativi
                $dbo->sync('zz_group_view', ['id_vista' => $id], ['id_gruppo' => (array) post('gruppi')[$c]]);
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'filters':
        $rs = true;

        // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
        $queries = (array) $_POST['query'];
        foreach ($queries as $c => $query) {
            $query = $_POST['query'][$c];

            if (check_query($query)) {
                $array = [
                    'name' => post('name')[$c],
                    'idgruppo' => post('gruppo')[$c],
                    'idmodule' => $id_record,
                    'clause' => $query,
                    'position' => !empty(post('position')[$c]) ? 'HVN' : 'WHR',
                ];

                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];

                    $dbo->update('zz_group_module', $array, ['id' => $id]);
                } elseif (!empty($query)) {
                    $dbo->insert('zz_group_module', $array);

                    $id = $dbo->lastInsertedID();
                }
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'change':
        $id = filter('id');

        $rs = $dbo->fetchArray('SELECT enabled FROM zz_group_module WHERE id='.prepare($id));

        $dbo->update('zz_group_module', [
            'enabled' => !empty($rs[0]['enabled']) ? 0 : 1,
        ], ['id' => $id]);

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'test':
        $module_query = Util\Query::getQuery(Module::find($id_record));

        $dbo->fetchArray($module_query.' LIMIT 1');

        break;

    case 'delete':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_views` WHERE `id`='.prepare($id));
        $dbo->query('DELETE FROM `zz_group_view` WHERE `id_vista`='.prepare($id));

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'delete_filter':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_group_module` WHERE `id`='.prepare($id));

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'update_position':

        $orders = explode(',', $_POST['order']);
        $order = 0;

        foreach ($orders as $idriga) {
            $dbo->query('UPDATE `zz_views` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
            ++$order;
        }

        break;
}
