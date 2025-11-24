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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Export\CSV;
use Modules\Anagrafiche\Tipo;
use Modules\ListeNewsletter\Lista;
use Util\Query;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'delete_bulk':
        $id_tipo_azienda = Tipo::where('name', 'Azienda')->first()->id;

        foreach ($id_records as $id) {
            $anagrafica = $dbo->fetchArray('SELECT `an_tipianagrafiche`.`id` FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` WHERE `idanagrafica`='.prepare($id));
            $tipi = array_column($anagrafica, 'id');

            // Se l'anagrafica non è di tipo Azienda
            if (!in_array($id_tipo_azienda, $tipi)) {
                $dbo->query('UPDATE `an_anagrafiche` SET `deleted_at` = NOW() WHERE `idanagrafica` = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                ++$eliminate;
            }
        }

        if ($eliminate > 1) {
            flash()->info(tr('Sono state eliminate _NUM_ anagrafiche', ['_NUM_' => $eliminate]));
        } elseif ($eliminate == 1) {
            flash()->info(tr('E\' stata eliminata una anagrafica'));
        } else {
            flash()->warning(tr('Non è stato possibile eliminare le anagrafiche selezionate.'));
        }

        break;

    case 'search_coordinates':
        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);
            $anagrafica->save();
        }

        break;

    case 'export_csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $anagrafiche = Anagrafica::whereIn('idanagrafica', $id_records)->get();
        $exporter->setRecords($anagrafiche);

        $count = $exporter->exportRecords();

        download($file, 'anagrafiche.csv');
        exit;

    case 'change_relation':
        $idrelazione = post('idrelazione');

        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);

            $anagrafica->idrelazione = $idrelazione;

            $anagrafica->save();
        }
        break;

    case 'update_price_list':
        $id_listino = post('id_listino') ?: 0;
        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);
            if ($anagrafica->isTipo('Cliente')) {
                $anagrafica->id_listino = $id_listino;
                $anagrafica->save();
            }
        }

        flash()->info(tr('Listino aggiornato correttamente!'));

        break;

    case 'export_newsletter_csv':
        $tipo_esportazione = post('tipo_esportazione');
        $id_records_list = implode(',', array_map(intval(...), $id_records));

        if ($tipo_esportazione == 'anagrafiche') {
            // Esportazione email e ragione sociale dalle anagrafiche selezionate
            $query = "SELECT DISTINCT email, ragione_sociale, 'Anagrafica' as fonte, '' as sede_referente FROM an_anagrafiche
                WHERE idanagrafica IN ($id_records_list)
                AND email != ''
                AND email IS NOT NULL
                AND enable_newsletter = 1
                AND deleted_at IS NULL";
        } elseif ($tipo_esportazione == 'sedi') {
            // Esportazione email, ragione sociale e nome sede dalle sedi delle anagrafiche selezionate
            $query = "SELECT DISTINCT s.email, a.ragione_sociale, 'Sede' as fonte, s.nomesede as sede_referente
                FROM an_sedi s
                LEFT JOIN an_anagrafiche a ON s.idanagrafica = a.idanagrafica
                WHERE s.idanagrafica IN ($id_records_list)
                AND s.email != ''
                AND s.email IS NOT NULL
                AND s.enable_newsletter = 1";
        } elseif ($tipo_esportazione == 'referenti') {
            // Esportazione email, ragione sociale e nome referente dai referenti delle anagrafiche selezionate
            $query = "SELECT DISTINCT r.email, a.ragione_sociale, 'Referente' as fonte, r.nome as sede_referente
                FROM an_referenti r
                LEFT JOIN an_anagrafiche a ON r.idanagrafica = a.idanagrafica
                WHERE r.idanagrafica IN ($id_records_list)
                AND r.email != ''
                AND r.email IS NOT NULL
                AND r.enable_newsletter = 1";
        } else {
            // Esportazione email, ragione sociale e nomi da tutte e tre le fonti delle anagrafiche selezionate
            $query = "SELECT DISTINCT email, ragione_sociale, fonte, sede_referente FROM (
                SELECT email, ragione_sociale, 'Anagrafica' as fonte, '' as sede_referente FROM an_anagrafiche
                WHERE idanagrafica IN ($id_records_list)
                AND email != ''
                AND email IS NOT NULL
                AND enable_newsletter = 1
                AND deleted_at IS NULL
                UNION
                SELECT s.email, a.ragione_sociale, 'Sede' as fonte, s.nomesede as sede_referente
                FROM an_sedi s
                LEFT JOIN an_anagrafiche a ON s.idanagrafica = a.idanagrafica
                WHERE s.idanagrafica IN ($id_records_list)
                AND s.email != ''
                AND s.email IS NOT NULL
                AND s.enable_newsletter = 1
                UNION
                SELECT r.email, a.ragione_sociale, 'Referente' as fonte, r.nome as sede_referente
                FROM an_referenti r
                LEFT JOIN an_anagrafiche a ON r.idanagrafica = a.idanagrafica
                WHERE r.idanagrafica IN ($id_records_list)
                AND r.email != ''
                AND r.email IS NOT NULL
                AND r.enable_newsletter = 1
            ) AS all_emails";
        }
        $results = $dbo->fetchArray($query);

        // Creazione del file CSV
        $file = temp_file();
        $handle = fopen($file, 'w');

        // Scrittura dell'intestazione
        fputcsv($handle, ['email', 'ragione_sociale', 'fonte', 'sede_referente'], ';');

        // Scrittura dei dati
        foreach ($results as $row) {
            fputcsv($handle, [
                $row['email'],
                $row['ragione_sociale'],
                $row['fonte'],
                $row['sede_referente'],
            ], ';');
        }
        fclose($handle);

        // Nome del file basato sul tipo di esportazione
        $filename_map = [
            'anagrafiche' => 'newsletter_anagrafiche.csv',
            'sedi' => 'newsletter_sedi.csv',
            'referenti' => 'newsletter_referenti.csv',
            'tutti' => 'newsletter_tutti.csv',
        ];
        $filename = $filename_map[$tipo_esportazione] ?? 'newsletter_export.csv';

        // Download del file
        download($file, $filename);

        break;

    case 'crea-lista':
        $lista = Lista::build(post('nome_lista'));
        $lista->setTranslation('title', post('nome_lista'));
        $modalita_dinamica = post('modalita_dinamica');
        $includi_disiscritti = post('includi_disiscritti');

        // Se modalità dinamica è attiva, salvo la query, altrimenti la lista sarà statica
        if ($modalita_dinamica) {
            // Aggiungo i filtri di ricerca applicati nel modulo
            $where = [];
            if (count(getSearchValues($id_module)) > 0) {
                foreach (getSearchValues($id_module) as $key => $value) {
                    $where[$key] = $value;
                }
            }
            $query = Query::getQuery($structure, $where);
            $pos = strpos((string) $query, 'SELECT');
            if ($pos !== false) {
                $query = substr_replace($query, "SELECT 'Modules\\\\\\Anagrafiche\\\\\\Anagrafica' AS tipo_lista, ", $pos, 6);
            }

            // Se non includo i disiscritti, aggiungo il filtro per enable_newsletter = 1
            if (!$includi_disiscritti) {
                $query = str_replace('1=1', '1=1 AND an_anagrafiche.enable_newsletter = 1', $query);
            }

            if (check_query($query)) {
                $lista->query = html_entity_decode((string) $query);
            }
        } else {
            foreach ($id_records as $id) {
                $anagrafica = Anagrafica::find($id);
                if (!$includi_disiscritti && !$anagrafica->enable_newsletter) {
                    continue;
                }
                $dbo->insert('em_list_receiver', [
                    'id_list' => $lista->id,
                    'record_type' => Anagrafica::class,
                    'record_id' => $id,
                ]);
            }
        }
        $lista->save();

        flash()->info(tr('Lista creata correttamente!'));

        break;
}

$operations = [];

$operations['change_relation'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Cambia relazione').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero cambiare la relazione delle anagrafiche selezionate?').'<br><br>{[ "type": "select", "label": "'.tr('Relazione con il cliente').'", "name": "idrelazione", "required": 1, "ajax-source": "relazioni"]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['delete_bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare le anagrafiche selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

$operations['export_csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con le anagrafiche selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

$operations['update_price_list'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Imposta listino').'</span>',
    'data' => [
        'msg' => tr('Vuoi impostare il listino cliente selezionato a queste anagrafiche?').'<br><br>{[ "type": "select", "label": "'.tr('Listino cliente').'", "name": "id_listino", "required": 0, "ajax-source": "listini", "placeholder": "'.tr('Nessun listino').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['search_coordinates'] = [
    'text' => '<span><i class="fa fa-map"></i> '.tr('Ricerca coordinate').'</span>',
    'data' => [
        'msg' => tr('Ricercare le coordinate per le anagrafiche selezionate senza latitudine e longitudine?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['export_newsletter_csv'] = [
    'text' => '<span><i class="fa fa-envelope"></i> '.tr('Esporta email newsletter').'</span>',
    'data' => [
        'msg' => tr('Seleziona il tipo di email da esportare per la newsletter:').'<br><br>{[ "type": "select", "label": "'.tr('Tipo esportazione').'", "name": "tipo_esportazione", "required": 1, "values": "list=\"anagrafiche\":\"Anagrafiche\",\"sedi\":\"Sedi\",\"referenti\":\"Referenti\",\"tutti\":\"Tutti (Anagrafiche, Sedi, Referenti)\"", "value": "tutti" ]}',
        'button' => tr('Esporta CSV'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['crea-lista'] = [
    'text' => '<span><i class="fa fa-envelope"></i> '.tr('Crea lista').'</span>',
    'data' => [
        'msg' => tr('Vuoi creare una nuova lista?').'<br><br>{[ "type": "text", "label": "'.tr('Nome lista').'", "name": "nome_lista", "required": 1 ]}
        {[ "type": "checkbox", "label": "'.tr('Modalità').'", "name": "modalita_dinamica", "help": "'.tr('Se Dinamica prende in considerazione tutte le righe della tabella con i filtri applicati, mentre Statica esporta solo le righe selezionate.').'", "values": "Dinamica,Statica" ]}
        {[ "type": "checkbox", "label": "'.tr('Includi disiscritti').'", "name": "includi_disiscritti", "value": 0 ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => false,
    ],
];

return $operations;
