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

include_once __DIR__.'/../../core.php';

use Models\Clause;
use Models\Module;
use Models\View;

switch (filter('op')) {
    case 'export_module':
        // Esportazione del modulo in formato JSON
        $module = Module::find($id_record);

        if (!$module) {
            echo json_encode([
                'success' => false,
                'message' => tr('Modulo non trovato'),
            ]);
            break;
        }

        // Recupera i dati del modulo
        $module_data = [
            'name' => $module->name,
            'directory' => $module->directory,
            'options' => $module->options,
            'options2' => $module->options2,
            'icon' => $module->icon,
            'version' => $module->version,
            'compatibility' => $module->compatibility,
            'order' => $module->order,
            'parent' => null,
            'default' => $module->default,
            'enabled' => $module->enabled,
            'use_notes' => $module->hasFlag('use_notes'),
            'use_checklists' => $module->hasFlag('use_checklists'),
        ];

        // Se c'è un modulo parent, usa il nome come riferimento
        if ($module->parent) {
            $parent = Module::find($module->parent);
            if ($parent) {
                $module_data['parent_name'] = $parent->name;
            }
        }

        // Recupera le traduzioni del modulo
        $module_langs = database()->table('zz_modules_lang')->where('id_record', $id_record)->get(['id_lang', 'title'])->toArray();
        $module_data['translations'] = [];
        foreach ($module_langs as $lang) {
            $module_data['translations'][$lang['id_lang']] = [
                'title' => $lang['title'],
            ];
        }

        // Recupera le viste del modulo
        $views = View::where('id_module', $id_record)->get();
        $module_data['views'] = [];

        foreach ($views as $view) {
            $view_data = [
                'name' => $view->name,
                'query' => $view->query,
                'order' => $view->order,
                'search' => $view->search,
                'slow' => $view->slow,
                'format' => $view->format,
                'html_format' => $view->html_format,
                'search_inside' => $view->search_inside,
                'order_by' => $view->order_by,
                'visible' => $view->visible,
                'summable' => $view->summable,
                'avg' => $view->avg,
            ];

            // Recupera le traduzioni della vista
            $view_langs = database()->table('zz_views_lang')->where('id_record', $view->id)->get(['id_lang', 'title'])->toArray();
            $view_data['translations'] = [];
            foreach ($view_langs as $lang) {
                $view_data['translations'][$lang['id_lang']] = [
                    'title' => $lang['title'],
                ];
            }

            // Recupera i gruppi associati alla vista
            $view_groups = database()->table('zz_group_view')
                ->join('zz_groups', 'zz_groups.id', '=', 'zz_group_view.id_gruppo')
                ->where('id_vista', $view->id)
                ->pluck('zz_groups.nome')
                ->toArray();
            $view_data['groups'] = $view_groups;

            $module_data['views'][] = $view_data;
        }

        // Recupera i filtri del modulo
        $clauses = Clause::where('id_module', $id_record)->get();
        $module_data['clauses'] = [];

        foreach ($clauses as $clause) {
            $clause_data = [
                'name' => $clause->name,
                'clause' => $clause->clause,
                'position' => $clause->position,
                'enabled' => $clause->enabled,
                'default' => $clause->default,
            ];

            // Recupera il gruppo associato al filtro
            $group_info = $dbo->fetchArray('SELECT `nome` FROM `zz_groups` WHERE `id` = '.prepare($clause->id_gruppo));
            if (!empty($group_info)) {
                $clause_data['group'] = $group_info[0]['nome'];
            }

            // Recupera le traduzioni del filtro
            $clause_langs = $dbo->fetchArray('SELECT `id_lang`, `title` FROM `zz_group_module_lang` WHERE `id_record` = '.prepare($clause->id));
            $clause_data['translations'] = [];
            foreach ($clause_langs as $lang) {
                $clause_data['translations'][$lang['id_lang']] = [
                    'title' => $lang['title'],
                ];
            }

            $module_data['clauses'][] = $clause_data;
        }

        // Prepara il nome del file
        $filename = 'module_'.strtolower(str_replace(' ', '_', $module->name)).'.json';

        // Restituisci i dati in formato JSON
        echo json_encode([
            'success' => true,
            'data' => $module_data,
            'filename' => $filename,
        ]);
        break;

    case 'import_module':
        // Importazione del modulo da un file JSON
        $response = [
            'success' => false,
            'message' => '',
        ];

        // Verifica che sia stato caricato un file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = tr('Errore durante il caricamento del file');
            echo json_encode($response);
            break;
        }

        // Leggi il contenuto del file
        $file_content = file_get_contents($_FILES['file']['tmp_name']);
        $module_data = json_decode($file_content, true);

        // Verifica che il JSON sia valido
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response['message'] = tr('Il file non contiene un JSON valido');
            echo json_encode($response);
            break;
        }

        // Verifica che il JSON contenga i dati necessari
        if (!isset($module_data['name'])) {
            $response['message'] = tr('Il file non contiene i dati necessari per importare il modulo');
            echo json_encode($response);
            break;
        }

        // Verifica se il modulo esiste già
        $existing_module = Module::where('name', $module_data['name'])->first();

        // Inizia una transazione per garantire l'integrità dei dati
        $dbo->beginTransaction();

        try {
            // Se il modulo esiste, aggiornalo, altrimenti creane uno nuovo
            if ($existing_module) {
                $module_id = $existing_module->id;

                // Aggiorna i dati del modulo
                Module::where('id', $module_id)->update([
                    'directory' => $module_data['directory'],
                    'options' => $module_data['options'],
                    'options2' => $module_data['options2'],
                    'icon' => $module_data['icon'],
                    'version' => $module_data['version'],
                    'compatibility' => $module_data['compatibility'],
                    'order' => $module_data['order'],
                    'default' => $module_data['default'],
                    'enabled' => $module_data['enabled'],
                ]);

                // Gestione dei flag use_notes e use_checklists nel nuovo sistema
                if ($module_data['use_notes']) {
                    database()->table('zz_modules_flags')->insertOrIgnore(['id_module' => $module_id, 'name' => 'use_notes']);
                } else {
                    database()->table('zz_modules_flags')->where('id_module', $module_id)->where('name', 'use_notes')->delete();
                }

                if ($module_data['use_checklists']) {
                    database()->table('zz_modules_flags')->insertOrIgnore(['id_module' => $module_id, 'name' => 'use_checklists']);
                } else {
                    database()->table('zz_modules_flags')->where('id_module', $module_id)->where('name', 'use_checklists')->delete();
                }
            } else {
                // Crea un nuovo modulo
                $dbo->insert('zz_modules', [
                    'name' => $module_data['name'],
                    'directory' => $module_data['directory'],
                    'options' => $module_data['options'],
                    'options2' => $module_data['options2'],
                    'icon' => $module_data['icon'],
                    'version' => $module_data['version'],
                    'compatibility' => $module_data['compatibility'],
                    'order' => $module_data['order'],
                    'default' => $module_data['default'],
                    'enabled' => $module_data['enabled'],
                ]);

                $module_id = $dbo->lastInsertedID();

                // Gestione dei flag use_notes e use_checklists nel nuovo sistema
                if ($module_data['use_notes']) {
                    $dbo->query('INSERT INTO `zz_modules_flags` (`id_module`, `name`) VALUES ('.prepare($module_id).', \'use_notes\')');
                }

                if ($module_data['use_checklists']) {
                    $dbo->query('INSERT INTO `zz_modules_flags` (`id_module`, `name`) VALUES ('.prepare($module_id).', \'use_checklists\')');
                }
            }

            // Aggiorna il parent se specificato
            if (isset($module_data['parent_name'])) {
                $parent = Module::where('name', $module_data['parent_name'])->first();
                if ($parent) {
                    $dbo->update('zz_modules', ['parent' => $parent->id], ['id' => $module_id]);
                }
            }

            // Aggiorna le traduzioni del modulo
            if (isset($module_data['translations'])) {
                foreach ($module_data['translations'] as $id_lang => $translation) {
                    $existing_translation = database()->table('zz_modules_lang')->where('id_record', $module_id)->where('id_lang', $id_lang)->value('id');

                    if (!empty($existing_translation)) {
                        database()->table('zz_modules_lang')->where('id', $existing_translation)->update([
                            'title' => $translation['title'],
                        ]);
                    } else {
                        database()->table('zz_modules_lang')->insert([
                            'id_record' => $module_id,
                            'id_lang' => $id_lang,
                            'title' => $translation['title'],
                        ]);
                    }
                }
            }

            // Gestisci le viste
            if (isset($module_data['views'])) {
                // Elimina tutte le viste esistenti per il modulo
                $existing_views = $dbo->fetchArray('SELECT `id` FROM `zz_views` WHERE `id_module` = '.prepare($module_id));

                // Elimina prima le associazioni con i gruppi
                foreach ($existing_views as $view) {
                    $dbo->delete('zz_group_view', ['id_vista' => $view['id']]);
                }

                // Elimina tutte le viste
                $dbo->delete('zz_views', ['id_module' => $module_id]);

                // Crea tutte le nuove viste dal file JSON
                foreach ($module_data['views'] as $index => $view_data) {
                    $view_array = [
                        'name' => $view_data['name'],
                        'query' => $view_data['query'],
                        'order' => $view_data['order'] ?? $index, // Usa l'indice come ordine se non specificato
                        'search' => $view_data['search'],
                        'slow' => $view_data['slow'],
                        'format' => $view_data['format'],
                        'html_format' => $view_data['html_format'],
                        'search_inside' => $view_data['search_inside'],
                        'order_by' => $view_data['order_by'],
                        'visible' => $view_data['visible'],
                        'summable' => $view_data['summable'],
                        'avg' => $view_data['avg'],
                        'id_module' => $module_id,
                    ];

                    // Crea la nuova vista
                    $dbo->insert('zz_views', $view_array);
                    $view_id = $dbo->lastInsertedID();

                    // Crea le traduzioni della vista
                    if (isset($view_data['translations'])) {
                        foreach ($view_data['translations'] as $id_lang => $translation) {
                            $dbo->insert('zz_views_lang', [
                                'id_record' => $view_id,
                                'id_lang' => $id_lang,
                                'title' => $translation['title'],
                            ]);
                        }
                    }

                    // Gestisci i gruppi associati alla vista
                    if (isset($view_data['groups'])) {
                        foreach ($view_data['groups'] as $group_name) {
                            $group = Models\Group::where('nome', $group_name)->value('id');
                            if (!empty($group)) {
                                database()->table('zz_group_view')->insert([
                                    'id_vista' => $view_id,
                                    'id_gruppo' => $group,
                                ]);
                            }
                        }
                    }
                }
            }

            // Gestisci i filtri
            if (isset($module_data['clauses'])) {
                // Elimina tutti i filtri esistenti per il modulo
                $existing_clauses = Models\Permissions::where('id_module', $module_id)->pluck('id')->toArray();

                // Elimina tutti i filtri
                Models\Permissions::where('id_module', $module_id)->delete();

                // Crea tutti i nuovi filtri dal file JSON
                foreach ($module_data['clauses'] as $clause_data) {
                    // Trova l'ID del gruppo
                    $group_id = null;
                    if (isset($clause_data['group'])) {
                        $group_id = Models\Group::where('nome', $clause_data['group'])->value('id');
                    }

                    // Salta se non è stato trovato il gruppo
                    if (!$group_id) {
                        continue;
                    }

                    $clause_array = [
                        'name' => $clause_data['name'],
                        'id_gruppo' => $group_id,
                        'id_module' => $module_id,
                        'clause' => $clause_data['clause'],
                        'position' => $clause_data['position'],
                        'enabled' => $clause_data['enabled'],
                        'default' => $clause_data['default'],
                    ];

                    // Crea il nuovo filtro
                    database()->table('zz_group_module')->insert($clause_array);
                    $clause_id = database()->getPdo()->lastInsertId();

                    // Crea le traduzioni del filtro
                    if (isset($clause_data['translations'])) {
                        foreach ($clause_data['translations'] as $id_lang => $translation) {
                        database()->table('zz_group_module_lang')->insert([
                            'id_record' => $clause_id,
                            'id_lang' => $id_lang,
                            'title' => $translation['title'],
                        ]);
                        }
                    }
                }
            }

            // Commit della transazione
            $dbo->commitTransaction();

            $response['success'] = true;
            $response['message'] = tr('Modulo importato con successo');
        } catch (Exception $e) {
            // Rollback in caso di errore
            $dbo->rollbackTransaction();

            $response['message'] = tr('Errore durante l\'importazione del modulo').': '.$e->getMessage();
        }

        echo json_encode($response);
        break;

    case 'update':
        $options2 = htmlspecialchars_decode(post('options2'), ENT_QUOTES);

        if (check_query($options2)) {
            $dbo->query('UPDATE `zz_modules` SET `options2`='.prepare($options2).' WHERE `id`='.prepare($id_record));
            $dbo->query('UPDATE `zz_modules_lang` SET `title`='.prepare(post('title')).' WHERE (`id_record`='.prepare($id_record).' AND `id_lang`='.prepare(Models\Locale::getDefault()->id).')');
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
                    'html_format' => post('html_format')[$c],
                    'summable' => post('sum-avg')[$c] === 'sum' ? 1 : 0,
                    'avg' => post('sum-avg')[$c] === 'avg' ? 1 : 0,
                    'search_inside' => post('search_inside')[$c],
                    'order_by' => post('order_by')[$c],
                    'id_module' => $id_record,
                ];

                $title = post('name')[$c];

                $id = null;
                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];

                    $dbo->update('zz_views', $array, ['id' => $id]);
                } elseif (!empty($query)) {
                    $array['order'] = orderValue('zz_views', 'id_module', $id_record);
                    $dbo->insert('zz_views', $array);
                    $id = $dbo->lastInsertedID();

                    // Se è una nuova vista, aggiungi automaticamente tutti i gruppi che hanno accesso al modulo
                    if (empty(post('gruppi')[$c])) {
                        // Ottieni tutti i gruppi che hanno accesso al modulo (permessi 'r' o 'rw')
                        $gruppi_con_accesso = Models\Permissions::where('id_module', $id_record)->whereIn('permessi', ['r', 'rw'])->pluck('id_gruppo')->toArray();

                        // Assicurati che il gruppo Amministratori (ID 1) sia incluso
                        $id_gruppo_admin = 1; // ID del gruppo Amministratori
                        $gruppi_ids = array_column($gruppi_con_accesso, 'id_gruppo');
                        if (!in_array($id_gruppo_admin, $gruppi_ids)) {
                            $gruppi_con_accesso[] = ['id_gruppo' => $id_gruppo_admin];
                        }

                        // Aggiungi i permessi per tutti i gruppi con accesso
                        foreach ($gruppi_con_accesso as $gruppo) {
                            database()->table('zz_group_view')->insert([
                                'id_vista' => $id,
                                'id_gruppo' => $gruppo,
                            ]);
                        }

                        // Aggiorna l'array dei gruppi per la sincronizzazione successiva
                        $_POST['gruppi'][$c] = array_column($gruppi_con_accesso, 'id_gruppo');
                    }
                }

                // Aggiornamento traduzione nome campo
                if ($id) {
                    $vista = View::find($id);
                    $vista->setTranslation('title', $title);

                    // Aggiornamento dei permessi relativi
                    $dbo->sync('zz_group_view', ['id_vista' => $id], ['id_gruppo' => (array) post('gruppi')[$c]]);
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

    case 'filters':
        $rs = true;

        // Fix per la protezone contro XSS, che interpreta la sequenza "<testo" come un tag HTML
        $queries = (array) $_POST['query'];
        foreach ($queries as $c => $query) {
            $query = $_POST['query'][$c];

            if (check_query($query)) {
                $array = [
                    'name' => post('name')[$c],
                    'id_gruppo' => post('gruppo')[$c],
                    'id_module' => $id_record,
                    'clause' => $query,
                    'position' => !empty(post('position')[$c]) ? 'HVN' : 'WHR',
                ];

                if (!empty(post('id')[$c]) && !empty($query)) {
                    $id = post('id')[$c];

                    $dbo->update('zz_group_module', $array, ['id' => $id]);
                    $dbo->update('zz_group_module_lang', ['title' => $array['name']], ['id_record' => $id, 'id_lang' => Models\Locale::getDefault()->id]);
                } elseif (!empty($query)) {
                    $dbo->insert('zz_group_module', $array);
                    $dbo->insert('zz_group_module_lang', ['id_record' => $dbo->lastInsertedID(), 'id_lang' => Models\Locale::getDefault()->id, 'title' => $array['name']]);

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

        $clause = Clause::find($id);
        $clause->enabled = !empty($clause->enabled) ? 0 : 1;
        $clause->save();

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'test':
        $module_query = Util\Query::getQuery(Module::find(get('id_record')));

        try {
            $dbo->fetchArray($module_query.' LIMIT 1');
            echo 'ok';
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        break;

    case 'delete':
        $id = filter('id');

        $view = View::find($id);
        $view->delete();
        database()->table('zz_group_view')->where('id_vista', $id)->delete();

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'delete_filter':
        $id = filter('id');

        $clause = Clause::find($id);
        $clause->delete();

        flash()->info(tr('Eliminazione completata!'));

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            View::where('id', $id_riga)->update(['order' => $i + 1]);
        }

        break;

    case 'update_visible':
        $visible = filter('visible');
        $id_riga = filter('id_vista');

        View::where('id', $id_riga)->update(['visible' => $visible]);

        break;
}
