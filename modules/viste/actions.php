<?php

include_once __DIR__.'/../../core.php';

function check_query($query)
{
    $query = mb_strtoupper($query);

    $blacklist = ['INSERT', 'UPDATE', 'TRUNCATE', 'DELETE', 'DROP', 'GRANT', 'CREATE', 'REVOKE'];
    foreach ($blacklist as $value) {
        if (preg_match("/\b".preg_quote($value)."\b/", $query)) {
            return false;
        }
    }

    return true;
}

switch (filter('op')) {
    case 'update':
        $post['options2'] = htmlspecialchars_decode($post['options2'], ENT_QUOTES);

        if (check_query($post['options2'])) {
            $dbo->query('UPDATE `zz_modules` SET `title`='.prepare($post['title']).', `options2`='.prepare($post['options2']).' WHERE `id`='.prepare($id_record));

            $rs = true;
        } else {
            $rs = false;
        }

        if ($rs) {
            $_SESSION['infos'][] = tr('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'fields':
        $rs = true;

        foreach ((array) $post['query'] as $c => $k) {
            // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
            $post['query'][$c] = $_POST['query'][$c];

            if (check_query($post['query'][$c])) {
                $array = [
                    'name' => $post['name'][$c],
                    'query' => $post['query'][$c],
                    'enabled' => $post['enabled'][$c],
                    'search' => $post['search'][$c],
                    'slow' => $post['slow'][$c],
                    'format' => $post['format'][$c],
                    'summable' => $post['sum'][$c],
                    'search_inside' => $post['search_inside'][$c],
                    'order_by' => $post['order_by'][$c],
                    'id_module' => $id_record,
                ];

                if (!empty($post['id'][$c]) && !empty($post['query'][$c])) {
                    $id = $post['id'][$c];

                    $dbo->update('zz_views', $array, ['id' => $id]);
                } elseif (!empty($post['query'][$c])) {
                    $array['#order'] = '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM zz_views AS t WHERE id_module='.prepare($id_record).')';

                    $dbo->insert('zz_views', $array);

                    $id = $dbo->lastInsertedID();
                }

                // Aggiornamento dei permessi relativi
                $dbo->sync('zz_group_view', ['id_vista' => $id], ['id_gruppo' => (array) $post['gruppi'][$c]]);
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            $_SESSION['infos'][] = tr('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'filters':
        $rs = true;

        foreach ((array) $post['query'] as $c => $k) {
            // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
            $post['query'][$c] = $_POST['query'][$c];

            if (check_query($post['query'][$c])) {
                $array = [
                    'idgruppo' => $post['gruppo'][$c],
                    'idmodule' => $id_record,
                    'clause' => $post['query'][$c],
                    'position' => !empty($post['position'][$c]) ? 'HVN' : 'WHR',
                ];

                if (!empty($post['id'][$c]) && !empty($post['query'][$c])) {
                    $id = $post['id'][$c];

                    $dbo->update('zz_group_module', $array, ['id' => $id]);
                } elseif (!empty($post['query'][$c])) {
                    $dbo->insert('zz_group_module', $array);

                    $id = $dbo->lastInsertedID();
                }
            } else {
                $rs = false;
            }
        }

        if ($rs) {
            $_SESSION['infos'][] = tr('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = tr('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'change':
        $id = filter('id');

        $rs = $dbo->fetchArray('SELECT enabled FROM zz_group_module WHERE id='.prepare($id));

        $array = ['enabled' => !empty($rs[0]['enabled']) ? 0 : 1];

        $dbo->update('zz_group_module', $array, ['id' => $id]);

        $_SESSION['infos'][] = tr('Salvataggio completato!');

        break;

    case 'test':
        $total = Modules::getQuery($id_record);
        $module_query = $total['query'];

        $module_query = str_replace('|period_start|', $_SESSION['period_start'], $module_query);
        $module_query = str_replace('|period_end|', $_SESSION['period_end'], $module_query);
        $module_query = str_replace('|select|', $total['select'], $module_query);

        $dbo->fetchArray($module_query.' LIMIT 1');

        break;

    case 'delete':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_views` WHERE `id`='.prepare($id));
        $dbo->query('DELETE FROM `zz_group_view` WHERE `id_vista`='.prepare($id));

        $_SESSION['infos'][] = tr('Eliminazione completata!');

        break;

    case 'delete_filter':
        $id = filter('id');

        $dbo->query('DELETE FROM `zz_group_module` WHERE `id`='.prepare($id));

        $_SESSION['infos'][] = tr('Eliminazione completata!');

        break;

    case 'update_position':
        $start = filter('start') + 1;
        $end = filter('end') + 1;
        $id = filter('id');

        if ($start > $end) {
            $dbo->query('UPDATE `zz_views` SET `order`=`order` + 1 WHERE `order`>='.prepare($end).' AND `order`<'.prepare($start).' AND id_module='.prepare($id_record));
            $dbo->query('UPDATE `zz_views` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        } elseif ($end != $start) {
            $dbo->query('UPDATE `zz_views` SET `order`=`order` - 1 WHERE `order`>'.prepare($start).' AND `order`<='.prepare($end).' AND id_module='.prepare($id_record));
            $dbo->query('UPDATE `zz_views` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        }

        break;
}
