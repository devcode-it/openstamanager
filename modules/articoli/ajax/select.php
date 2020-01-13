<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'articoli':
        // Se non ci sono sedi settate, carico tutti gli articoli
        if (!isset($superselect['idsede_partenza']) && (!isset($superselect['idsede_destinazione']))) {
            $query = 'SELECT 
                mg_articoli.id, 
                mg_articoli.codice, 
                mg_articoli.descrizione,
                round(mg_articoli.qta,'.setting('Cifre decimali per quantità').') AS qta, 
                mg_articoli.um, 
                mg_articoli.idiva_vendita, 
                mg_articoli.idconto_vendita, 
                mg_articoli.idconto_acquisto, 
                mg_articoli.prezzo_vendita, 
                mg_articoli.prezzo_acquisto,
                categoria.`nome` AS categoria,
                sottocategoria.`nome` AS sottocategoria,
                co_iva.descrizione AS iva_vendita,
                CONCAT(conto_vendita_categoria .numero, ".", conto_vendita_sottocategoria.numero, " ", conto_vendita_sottocategoria.descrizione) AS idconto_vendita_title, 
                CONCAT(conto_acquisto_categoria .numero, ".", conto_acquisto_sottocategoria.numero, " ", conto_acquisto_sottocategoria.descrizione) AS idconto_acquisto_title
            FROM mg_articoli
                LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
                LEFT JOIN `mg_categorie` AS categoria ON `categoria`.`id` = `mg_articoli`.`id_categoria`
                LEFT JOIN `mg_categorie` AS sottocategoria ON `sottocategoria`.`id` = `mg_articoli`.`id_sottocategoria` 
                LEFT JOIN co_pianodeiconti3 AS conto_vendita_sottocategoria ON conto_vendita_sottocategoria.id=mg_articoli.idconto_vendita
                    LEFT JOIN co_pianodeiconti2 AS conto_vendita_categoria ON conto_vendita_sottocategoria.idpianodeiconti2=conto_vendita_categoria.id
                LEFT JOIN co_pianodeiconti3 AS conto_acquisto_sottocategoria ON conto_acquisto_sottocategoria.id=mg_articoli.idconto_acquisto
                    LEFT JOIN co_pianodeiconti2 AS conto_acquisto_categoria ON conto_acquisto_sottocategoria.idpianodeiconti2=conto_acquisto_categoria.id
            |where|
            ORDER BY
                mg_articoli.id_categoria ASC,
                mg_articoli.id_sottocategoria ASC,
                mg_articoli.codice ASC,
                mg_articoli.descrizione ASC';
        }

        // Se c'è una sede settata, carico tutti gli articoli presenti in quella sede
        else {
            $query = 'SELECT 
                mg_articoli.id, 
                mg_articoli.codice, 
                mg_articoli.descrizione,	
                mg_articoli.um, 
                mg_articoli.idiva_vendita, 
                mg_articoli.idconto_vendita, 
                mg_articoli.idconto_acquisto, 
                mg_articoli.prezzo_vendita, 
                mg_articoli.prezzo_acquisto,
                categoria.`nome` AS categoria,
                sottocategoria.`nome` AS sottocategoria,
                co_iva.descrizione AS iva_vendita,
                CONCAT(conto_vendita_categoria .numero, ".", conto_vendita_sottocategoria.numero, " ", conto_vendita_sottocategoria.descrizione) AS idconto_vendita_title, 
                CONCAT(conto_acquisto_categoria .numero, ".", conto_acquisto_sottocategoria.numero, " ", conto_acquisto_sottocategoria.descrizione) AS idconto_acquisto_title
            FROM mg_articoli
                LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
                LEFT JOIN `mg_categorie` AS categoria ON `categoria`.`id` = `mg_articoli`.`id_categoria`
                LEFT JOIN `mg_categorie` AS sottocategoria ON `sottocategoria`.`id` = `mg_articoli`.`id_sottocategoria` 
                LEFT JOIN co_pianodeiconti3 AS conto_vendita_sottocategoria ON conto_vendita_sottocategoria.id=mg_articoli.idconto_vendita
                    LEFT JOIN co_pianodeiconti2 AS conto_vendita_categoria ON conto_vendita_sottocategoria.idpianodeiconti2=conto_vendita_categoria.id
                LEFT JOIN co_pianodeiconti3 AS conto_acquisto_sottocategoria ON conto_acquisto_sottocategoria.id=mg_articoli.idconto_acquisto
                    LEFT JOIN co_pianodeiconti2 AS conto_acquisto_categoria ON conto_acquisto_sottocategoria.idpianodeiconti2=conto_acquisto_categoria.id
                LEFT JOIN (SELECT idarticolo, idsede_azienda, idsede_controparte FROM mg_movimenti GROUP BY idarticolo) movimenti ON movimenti.idarticolo=mg_articoli.id
                LEFT JOIN an_sedi ON an_sedi.id = movimenti.idsede_azienda           
            |where|
            GROUP BY
                mg_articoli.id
            ORDER BY
                mg_articoli.id_categoria ASC,
                mg_articoli.id_sottocategoria ASC,
                mg_articoli.codice ASC,
                mg_articoli.descrizione ASC';
        }

        foreach ($elements as $element) {
            $filter[] = 'mg_articoli.id='.prepare($element);
        }

        $where[] = 'mg_articoli.attivo = 1';
        $where[] = 'mg_articoli.deleted_at IS NULL';

        // Filtro articolo solo per documenti di vendita
        if ($superselect['dir'] == 'entrata' && isset($superselect['idsede_partenza'])) {
            $where[] = '((idsede_azienda='.prepare($superselect['idsede_partenza']).' OR idsede_azienda IS NULL) OR (idsede_controparte='.prepare($superselect['idsede_partenza']).' OR idsede_controparte IS NULL))';
        }

        if (!empty($search)) {
            $search_fields[] = 'mg_articoli.descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.codice LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.barcode LIKE '.prepare('%'.$search.'%');
        }

        $custom = [
            'id' => 'id',
            'codice' => 'codice',
            'descrizione' => 'descrizione',
            'qta' => 'qta',
            'um' => 'um',
            'categoria' => 'categoria',
            'sottocategoria' => 'sottocategoria',
            'idiva_vendita' => 'idiva_vendita',
            'iva_vendita' => 'iva_vendita',
            'idconto_vendita' => 'idconto_vendita',
            'idconto_vendita_title' => 'idconto_vendita_title',
            'idconto_acquisto' => 'idconto_acquisto',
            'idconto_acquisto_title' => 'idconto_acquisto_title',
            'prezzo_acquisto' => 'prezzo_acquisto',
            'prezzo_vendita' => 'prezzo_vendita',
        ];

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        // Individuazione di eventuali listini
        if (!empty($superselect['dir']) && !empty($superselect['idanagrafica'])) {
            $listino = $dbo->fetchOne('SELECT prc_guadagno as percentuale FROM mg_listini WHERE id=(SELECT idlistino_'.($superselect['dir'] == 'uscita' ? 'acquisti' : 'vendite').' FROM an_anagrafiche WHERE idanagrafica='.prepare($superselect['idanagrafica']).')');
        }

        //per le vendite leggo iva predefinita da anagrafica, se settata
        if (!empty($superselect['dir']) && $superselect['dir'] == 'entrata' && !empty($superselect['idanagrafica'])) {
            $idiva_predefinita_anagrafica = $dbo->fetchOne('SELECT idiva_vendite FROM an_anagrafiche WHERE idanagrafica = '.prepare($superselect['idanagrafica']))['idiva_vendite'];
            $iva_predefinita_anagrafica = $dbo->fetchOne('SELECT descrizione FROM co_iva WHERE id = '.prepare($idiva_predefinita_anagrafica))['descrizione'];
        }

        // IVA da impostazioni
        $idiva_predefinita = setting('Iva predefinita');
        $iva_predefinita = $dbo->fetchOne('SELECT descrizione FROM co_iva WHERE id='.prepare($idiva_predefinita))['descrizione'];

        $previous_category = -1;
        $previous_subcategory = -1;
        foreach ($rs as $r) {
            // Lettura movimenti delle mie sedi
            $qta_azienda = $dbo->fetchOne("SELECT SUM(mg_movimenti.qta) AS qta, IF(mg_movimenti.idsede_azienda= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_azienda WHERE mg_movimenti.idarticolo=".prepare($r['id']).' AND idsede_azienda='.prepare($superselect['idsede_partenza']).' GROUP BY idsede_azienda');

            // Lettura eventuali movimenti ad una propria sede (nel caso di movimenti fra sedi della mia azienda) per il calcolo corretto delle quantità
            if ($superselect['idsede_partenza'] != 0) {
                $qta_controparte = $dbo->fetchOne("SELECT SUM(mg_movimenti.qta) AS qta, IF(mg_movimenti.idsede_controparte= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_controparte WHERE mg_movimenti.idarticolo=".prepare($r['id']).' AND idsede_controparte='.prepare($superselect['idsede_partenza']).' GROUP BY idsede_controparte');
            } else {
                $qta_controparte = $dbo->fetchOne("SELECT SUM(mg_movimenti.qta) AS qta, IF(mg_movimenti.idsede_controparte= 0,'Sede legale',(CONCAT_WS(' - ',an_sedi.nomesede,an_sedi.citta))) as sede FROM ((( mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_controparte ) LEFT JOIN dt_ddt ON mg_movimenti.idddt = dt_ddt.id ) LEFT JOIN co_documenti ON mg_movimenti.iddocumento = co_documenti.id ) WHERE mg_movimenti.idarticolo=".prepare($r['id']).' AND idsede_controparte='.prepare($superselect['idsede_partenza']).' AND IFNULL( dt_ddt.idanagrafica, co_documenti.idanagrafica ) = '.prepare(setting('Azienda predefinita')).' GROUP BY idsede_controparte');
            }

            $qta = $qta_azienda['qta'] - $qta_controparte['qta'];

            if ($previous_category != $r['categoria'] || $previous_subcategory != $r['sottocategoria']) {
                $previous_category = $r['categoria'];
                $previous_subcategory = $r['sottocategoria'];

                $text = '<i>'.tr('Nessuna categoria').'</i>';
                if (!empty($r['categoria'])) {
                    $text = $r['categoria'].' ('.(!empty($r['sottocategoria']) ? $r['sottocategoria'] : '-').')';
                }

                $results[] = [
                    'text' => $text,
                    'children' => [],
                ];
            }

            // Iva dell'articolo
            if (!empty($idiva_predefinita_anagrafica)) {
                $idiva = $idiva_predefinita_anagrafica;
                $iva = $iva_predefinita_anagrafica;
            } elseif (empty($r['idiva_vendita'])) {
                $idiva = $idiva_predefinita;
                $iva = $iva_predefinita;
            } else {
                $idiva = $r['idiva_vendita'];
                $iva = $r['iva_vendita'];
            }

            $prezzo_vendita = $r['prezzo_vendita'];

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['codice'].' - '.$r['descrizione'].' ('.Translator::numberToLocale($qta).(!empty($r['um']) ? ' '.$r['um'] : '').')',
                'codice' => $r['codice'],
                'descrizione' => $r['descrizione'],
                'qta' => $r['qta'],
                'um' => $r['um'],
                'idiva_vendita' => $idiva,
                'iva_vendita' => $iva,
                'idconto_vendita' => $r['idconto_vendita'],
                'idconto_vendita_title' => $r['idconto_vendita_title'],
                'idconto_acquisto' => $r['idconto_acquisto'],
                'idconto_acquisto_title' => $r['idconto_acquisto_title'],
                'prezzo_acquisto' => $r['prezzo_acquisto'],
                'prezzo_vendita' => $prezzo_vendita,
            ];
        }

        $results = [
            'results' => $results,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;

    case 'prodotti_serial':
        $query = 'SELECT DISTINCT serial AS descrizione FROM mg_prodotti |where|';

        $where[] = 'id_articolo='.prepare($superselect['idarticolo']);
        $where[] = 'lotto='.prepare($superselect['lotto']);

        foreach ($elements as $element) {
            $filter[] = 'serial='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'serial LIKE '.prepare('%'.$search.'%');
        }

        $custom['id'] = 'descrizione';

        break;

    case 'categorie':
        $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY nome';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        $where[] = '`parent` IS NULL';

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'sottocategorie':
        if (isset($superselect['id_categoria'])) {
            $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY nome';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_categoria']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'misure':
        $query = 'SELECT valore AS id, valore AS descrizione FROM mg_unitamisura |where| ORDER BY valore';

        foreach ($elements as $element) {
            $filter[] = 'valore='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'valore LIKE '.prepare('%'.$search.'%');
        }

        break;
}
