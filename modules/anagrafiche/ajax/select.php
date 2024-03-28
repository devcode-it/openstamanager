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

include_once __DIR__.'/../../../core.php';
use Modules\Anagrafiche\Tipo;

$filter_agente = Auth::user()['gruppo'] == 'Agenti';

switch ($resource) {
    case 'clienti':
        $id_azienda = setting('Azienda predefinita');
        $tipologia = Tipo::find((new Tipo())->getByField('name', 'Cliente', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT
                `an_anagrafiche`.`idanagrafica` AS id,
                `an_anagrafiche`.`lat`,
                `an_anagrafiche`.`lng`,
                `is_bloccata`,
                CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'), IF(`is_bloccata` = 1, CONCAT(' (', `an_relazioni_lang`.`name`, ')'), ''),' - ', `an_anagrafiche`.`codice` ) AS descrizione,
                `idtipointervento_default` AS idtipointervento,
                `in_tipiintervento_lang`.`name` AS idtipointervento_descrizione,
                `an_anagrafiche`.`idzona`,
                `contratto`.`id` AS id_contratto,
                `contratto`.`descrizione` AS descrizione_contratto,
                `co_pagamenti`.`id` AS id_pagamento,
                `co_pagamenti_lang`.`name` AS desc_pagamento,
                `banca_vendite`.`id` AS id_banca_vendite,
                CONCAT(`banca_vendite`.`nome`, ' - ', `banca_vendite`.`iban`) AS descrizione_banca_vendite
            FROM
                `an_anagrafiche`
                INNER JOIN (`an_tipianagrafiche_anagrafiche`
                    INNER JOIN `an_tipianagrafiche`ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id`
                    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).'))
                        ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` LEFT JOIN `in_tipiintervento` ON `an_anagrafiche`.`idtipointervento_default`=`in_tipiintervento`.`id` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione`=`an_relazioni`.`id`
                LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id`=`an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).")
                LEFT JOIN
                    (SELECT `co_contratti`.`id`, `idanagrafica`, CONCAT('Contratto ', `numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y'), ' - ', `co_contratti`.`nome`, ' [', `co_staticontratti_lang`.`name` , ']') AS descrizione FROM `co_contratti` LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato`=`co_staticontratti`.`id` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id`=`co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang`= ".prepare(Models\Locale::getDefault()->id).') WHERE `co_contratti`.`predefined`=1 AND `is_pianificabile`=1) AS contratto
                        ON `an_anagrafiche`.`idanagrafica`=`contratto`.`idanagrafica`
                LEFT JOIN `co_pagamenti` ON `an_anagrafiche`.`idpagamento_vendite`=`co_pagamenti`.`id`
                LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id`=`co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).')
                LEFT JOIN
                    `co_banche` banca_vendite
                        ON `co_pagamenti`.`idconto_vendite` = `banca_vendite`.`id_pianodeiconti3` AND `banca_vendite`.`id_anagrafica` = '.prepare($id_azienda).' AND `banca_vendite`.`deleted_at` IS NULL AND `banca_vendite`.`predefined` = 1
                |where|
            ORDER BY
                `ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        $where[] = '`an_tipianagrafiche`.`id`= '.prepare($tipologia);
        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';

            if (!empty($filter_agente)) {
                $where[] = '`idagente` = '.Auth::user()['idanagrafica'];
            }
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        foreach ($rs as $k => $r) {
            $rs[$k] = array_merge($r, [
                'text' => $r['descrizione'],
                'disabled' => $r['is_bloccata'],
            ]);
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;

    case 'fornitori':
        $id_azienda = setting('Azienda predefinita');
        $tipologia = Tipo::find((new Tipo())->getByField('name', 'Fornitore', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT 
            `an_anagrafiche`.`idanagrafica` AS id, 
            CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, 
            `idtipointervento_default` AS `idtipointervento`, 
            `co_pagamenti`.`id` AS id_pagamento, 
            `co_pagamenti_lang`.`name` AS desc_pagamento, 
            `banca_acquisti`.`id` AS id_banca_acquisti, 
            CONCAT(`banca_acquisti`.`nome`, ' - ', `banca_acquisti`.`iban`) AS descrizione_banca_acquisti 
        FROM 
            `an_anagrafiche` 
            INNER JOIN (
                `an_tipianagrafiche_anagrafiche` 
                INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` 
                LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` 
                LEFT JOIN `co_pagamenti` ON `an_anagrafiche`.`idpagamento_acquisti`=`co_pagamenti`.`id`
                LEFT JOIN `co_pagamenti_lang` co_pagamenti_lang ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") 
                LEFT JOIN `co_banche` banca_acquisti ON `co_pagamenti`.`idconto_acquisti` = `banca_acquisti`.`id_pianodeiconti3` AND `banca_acquisti`.`id_anagrafica` = '.prepare($id_azienda).' AND `banca_acquisti`.`deleted_at` IS NULL AND `banca_acquisti`.`predefined` = 1 
            |where| 
            ORDER BY 
                `ragione_sociale`";

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`an_tipianagrafiche`.`id`= '.prepare($tipologia);
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'vettori':
        $tipologia = Tipo::find((new Tipo())->getByField('name', 'Vettore', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `idtipointervento_default` AS idtipointervento FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        $where[] = '`an_tipianagrafiche`.`id`= '.prepare($tipologia);
        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - idanagrafica
         */
    case 'agenti':
        $tipologia = Tipo::find((new Tipo())->getByField('name', 'Agente', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `idtipointervento_default` FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        $where[] = '`an_tipianagrafiche`.`id`= '.prepare($tipologia);
        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        $results = AJAX::selectResults($query, $where, $filter, $search, $limit, $custom);

        // Evidenzia l'agente di default
        if ($superselect['idanagrafica']) {
            $rsa = $dbo->fetchArray('SELECT `idagente` FROM `an_anagrafiche` WHERE `idanagrafica`='.prepare($superselect['idanagrafica']));
            $idagente_default = $rsa[0]['idagente'];
        } else {
            $idagente_default = 0;
        }

        $ids = array_column($results['results'], 'id');
        $pos = array_search($idagente_default, $ids);
        if ($pos !== false) {
            $results['results'][$pos]['_bgcolor_'] = '#ffff00';
        }
        break;

    case 'tecnici':
        $tipologia = Tipo::find((new Tipo())->getByField('name', 'Tecnico', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `idtipointervento_default` FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        $where[] = '`an_tipianagrafiche`.`id`= '.prepare($tipologia);
        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';

            if (setting('Permetti inserimento sessioni degli altri tecnici')) {
            } else {
                // come tecnico posso aprire attività solo a mio nome
                $user = Auth::user();
                if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
                    $where[] = '`an_anagrafiche`.`idanagrafica`='.$user['idanagrafica'];
                }
            }
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'clienti_fornitori':
        $id_cliente = Tipo::find((new Tipo())->getByField('name', 'Cliente', Models\Locale::getPredefined()->id))->id;
        $id_fornitore = Tipo::find((new Tipo())->getByField('name', 'Fornitore', Models\Locale::getPredefined()->id))->id;
        $id_azienda = Tipo::find((new Tipo())->getByField('name', 'Fornitore', Models\Locale::getPredefined()->id))->id;

        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT_WS('', `ragione_sociale`, IF(`citta` !='' OR `provincia` != '', CONCAT(' (', `citta`, IF(`provincia`!='', CONCAT(' ', `provincia`), ''), ')'), ''), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `an_tipianagrafiche_lang`.`name` AS optgroup, `idtipointervento_default`, `an_tipianagrafiche`.`id` as id_tipo FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `optgroup` ASC, `ragione_sociale` ASC';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        $where = [];
        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';
            $where[] = '`an_tipianagrafiche`.`id` IN ('.prepare($id_azienda).', '.prepare($id_fornitore).', '.prepare($id_cliente).')';
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        break;

        // Nota Bene: nel campo id viene specificato idtipoanagrafica-idanagrafica -> modulo Utenti e permessi, creazione nuovo utente
    case 'anagrafiche':
        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT_WS('', `ragione_sociale`, IF(`citta` !='' OR `provincia` != '', CONCAT(' (', `citta`, IF(`provincia`!='', CONCAT(' ', `provincia`), ''), ')'), ''), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `an_tipianagrafiche_lang`.`name` AS optgroup FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `optgroup` ASC, `ragione_sociale` ASC';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }
        break;

        /*
         * Opzioni utilizzate:
         * - idanagrafica
         */
    case 'sedi':
        if (isset($superselect['idanagrafica'])) {
            $query = "
            SELECT
                *
            FROM
                (SELECT '0' AS id, (SELECT `lat` FROM `an_anagrafiche` |where|) AS lat, (SELECT `lng` FROM `an_anagrafiche` |where|) AS lng, (SELECT `idzona` FROM `an_anagrafiche` |where|) AS idzona, CONCAT_WS(' - ', \"".tr('Sede legale')."\" , (SELECT CONCAT (`citta`, IF(`indirizzo`!='',CONCAT(' (', `indirizzo`, ')'), ''), ' (',`ragione_sociale`,')') FROM `an_anagrafiche` |where|)) AS descrizione
            
            UNION
                
            SELECT
                `id`,
                `lat`,
                `lng`,
                `idzona`,
                CONCAT_WS(' - ', `nomesede`, CONCAT(`citta`, IF(indirizzo!='',CONCAT(' (', `indirizzo`, ')'), '')) ) FROM `an_sedi` |where|) AS tab
            HAVING
                `descrizione` LIKE ".prepare('%'.$search.'%').'
            ORDER BY
                `descrizione`';

            foreach ($elements as $element) {
                $filter[] = '`id`='.prepare($element);
            }

            $where[] = '`idanagrafica`='.prepare($superselect['idanagrafica']);

            /*
            if (!empty($search)) {
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            }
            */
        }
        break;

    case 'sedi_azienda':
        $user = Auth::user();
        $id_azienda = setting('Azienda predefinita');

        $query = "SELECT * FROM (SELECT '0' AS id, 'Sede legale' AS `nomesede`, CONCAT_WS(' - ', \"".tr('Sede legale')."\" , (SELECT CONCAT (`citta`, IF(`indirizzo`!='',CONCAT(' (', `indirizzo`, ')'), ''),' (', `ragione_sociale`,')') FROM `an_anagrafiche` |where|)) AS descrizione UNION SELECT `id`, `nomesede`, CONCAT_WS(' - ', `nomesede`, CONCAT(`citta`, IF(`indirizzo`!='',CONCAT(' (', `indirizzo`, ')'), '')) ) FROM `an_sedi` |where|) AS tab |filter| ORDER BY descrizione";

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }

        $where[] = '`idanagrafica`='.prepare($id_azienda);
        // admin o utente senza una sede prefissata, avrà accesso a tutte le sedi
        if (!empty($user->sedi) and !$user->is_admin) {
            $where[] = '`id` IN('.implode(',', $user->sedi).')';
        }

        if (!empty($search)) {
            $search_fields[] = '`nomesede` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - idanagrafica
         */
    case 'referenti':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT `an_referenti`.`id`, `an_referenti`.`nome` AS descrizione, `an_mansioni`.`nome` AS optgroup FROM `an_referenti` LEFT JOIN `an_mansioni` ON `an_referenti`.`idmansione`=`an_mansioni`.`id` |where| ORDER BY optgroup, `an_referenti`.`nome`';

            foreach ($elements as $element) {
                $filter[] = '`an_referenti`.`id`='.prepare($element);
            }

            if (isset($superselect['idclientefinale'])) {
                $where[] = '(`idanagrafica`='.prepare($superselect['idanagrafica']).' OR `idanagrafica`='.prepare($superselect['idclientefinale']).')';
            } else {
                $where[] = '`idanagrafica`='.prepare($superselect['idanagrafica']);
            }

            if (isset($superselect['idsede_destinazione'])) {
                $where[] = '`idsede`='.prepare($superselect['idsede_destinazione']);
            }

            if (!empty($search)) {
                $search_fields[] = '`an_referenti`.`nome` LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'nazioni':
        $query = 'SELECT `an_nazioni`.`id` AS id, `iso2`, CONCAT_WS(\' - \', `iso2`, `an_nazioni_lang`.`name`) AS descrizione FROM `an_nazioni` LEFT JOIN `an_nazioni_lang` ON (`an_nazioni`.`id` = `an_nazioni_lang`.`id_record` AND `an_nazioni_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY CASE WHEN `iso2`=\'IT\' THEN -1 ELSE `iso2` END';

        foreach ($elements as $element) {
            $filter[] = '`an_nazioni`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`an_nazioni_lang`.`name` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`iso2` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'CONCAT_WS(\' - \', `iso2`, `name`) LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'regioni':
        if (isset($superselect['id_nazione'])) {
            $query = 'SELECT `an_regioni`.`id` AS id, `an_regioni`.`iso2`, CONCAT(CONCAT_WS(\' - \', `an_regioni`.`iso2`, `an_regioni_lang`.`name`), \' (\', `an_nazioni`.`iso2`, \')\') AS descrizione FROM `an_regioni` LEFT JOIN `an_regioni_lang` ON (`an_regioni`.`id` = `an_regioni_lang`.`id_record` AND `an_regioni_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_nazioni` ON `an_regioni`.`id_nazione` = `an_nazioni`.`id` |where| ORDER BY `an_regioni_lang`.`name`';

            foreach ($elements as $element) {
                $filter[] = '`an_regioni`.`id`='.prepare($element);
            }

            $where[] = '`an_regioni`.`id_nazione`='.prepare($superselect['id_nazione']);

            if (!empty($search)) {
                $search_fields[] = '`an_regioni_lang`.`name` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = '`an_regioni`.`iso2` LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'CONCAT_WS(\' - \', `an_regioni`.`iso2`, `an_regioni_lang`.`name`) LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'relazioni':
        $query = "SELECT `an_relazioni`.`id`, CONCAT(`name`, IF(`an_relazioni`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, `colore` AS _bgcolor_ FROM `an_relazioni` LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id` = `an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).') |where| ORDER BY descrizione';

        foreach ($elements as $element) {
            $filter[] = '`an_relazioni`.`id`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`an_relazioni`.`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`an_relazioni_lang`.`name` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'provenienze':
        $query = 'SELECT `an_provenienze`.`id`, `an_provenienze_lang`.`name` as descrizione, `colore` AS bgcolor FROM `an_provenienze` LEFT JOIN `an_provenienze_lang` ON (`an_provenienze`.`id` = `an_provenienze_lang`.`id_record` AND `an_provenienze_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `descrizione`';

        foreach ($elements as $element) {
            $filter[] = '`an_provenienze`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`an_provenienze_lang`.`name` LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'settori':
        $query = 'SELECT `an_settori`.`id`, `an_settori_lang`.`name` as descrizione FROM `an_settori` LEFT JOIN `an_settori_lang` ON (`an_settori`.`id` = `an_settori_lang`.`id_record` AND `an_settori_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') |where| ORDER BY `name`';

        foreach ($elements as $element) {
            $filter[] = '`an_settori`.`id`='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - idanagrafica
         */

    case 'dichiarazioni_intento':
        if (isset($superselect['idanagrafica']) && isset($superselect['data'])) {
            // $query = "SELECT id, CONCAT('N. prot. ', numero_protocollo, ' (periodo dal ', DATE_FORMAT(data_inizio, '%d/%m/%Y'), ' al ' ,DATE_FORMAT(data_fine, '%d/%m/%Y'),') (utilizzati ',REPLACE(REPLACE(REPLACE(FORMAT(SUM(totale),2), ',', '#'), '.', ','), '#', '.'), ' su ' , REPLACE(REPLACE(REPLACE(FORMAT(SUM(massimale),2), ',', '#'), '.', ','), '#', '.'),  ' &euro;)' ) AS descrizione, data_inizio, data_fine FROM co_dichiarazioni_intento |where| ORDER BY `data`, `id`";

            $query = 'SELECT id, numero_protocollo, data_inizio, data_fine, massimale, totale FROM co_dichiarazioni_intento |where| ORDER BY data';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            // $where[] = '( '.prepare($superselect['data']).' BETWEEN data_inizio AND data_fine)';

            // $where[] = 'data_inizio < NOW()';
            // $where[] = 'data_fine > NOW()';

            if (empty($filter)) {
                $where[] = 'co_dichiarazioni_intento.deleted_at IS NULL';
            }

            $where[] = 'id_anagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'numero_protocollo LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'numero_progressivo LIKE '.prepare('%'.$search.'%');
            }

            $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
            $rs = $data['results'];

            foreach ($rs as $k => $r) {
                $currentDate = date('Y-m-d', strtotime($superselect['data']));
                $startDate = date('Y-m-d', strtotime($r['data_inizio']));
                $endDate = date('Y-m-d', strtotime($r['data_fine']));

                $rs[$k] = array_merge($r, [
                    'text' => tr('N. prot.').' '.$r['numero_protocollo'].' - '.Translator::numberToLocale($r['totale']).'/'.Translator::numberToLocale($r['massimale']).' &euro; ['.Translator::dateToLocale($r['data_fine']).']',
                    'disabled' => (($currentDate < $startDate) || ($currentDate > $endDate)),
                ]);
            }

            $results = [
                'results' => $rs,
                'recordsFiltered' => $data['recordsFiltered'],
            ];
        }

        break;
}
