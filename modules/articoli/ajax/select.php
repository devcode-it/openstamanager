<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'articoli':
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
        |where| ORDER BY mg_articoli.id_categoria ASC, mg_articoli.id_sottocategoria ASC';

        foreach ($elements as $element) {
            $filter[] = 'mg_articoli.id='.prepare($element);
        }

        $where[] = 'attivo = 1';
        if (!empty($superselect['dir']) && $superselect['dir'] == 'entrata') {
            //$where[] = '(qta > 0 OR servizio = 1)';
        }

        if (!empty($search)) {
            $search_fields[] = 'mg_articoli.descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.codice LIKE '.prepare('%'.$search.'%');
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
        $idiva_predefinita = get_var('Iva predefinita');
        $iva_predefinita = $dbo->fetchOne('SELECT descrizione FROM co_iva WHERE id='.prepare($idiva_predefinita))['descrizione'];

        $previous_category = -1;
        $previous_subcategory = -1;
        foreach ($rs as $r) {
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
                'text' => $r['codice'].' - '.$r['descrizione'].' ('.Translator::numberToLocale($r['qta']).(!empty($r['um']) ? ' '.$r['um'] : '').')',
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

    case 'prodotti_lotti':
        $query = 'SELECT DISTINCT lotto AS descrizione FROM mg_prodotti |where|';

        $where[] = 'idarticolo='.prepare($superselect['idarticolo']);

        foreach ($elements as $element) {
            $filter[] = 'lotto='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'lotto LIKE '.prepare('%'.$search.'%');
        }

        $custom['id'] = 'descrizione';

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

    case 'prodotti_altro':
        $query = 'SELECT DISTINCT altro AS descrizione FROM mg_prodotti |where|';

        $where[] = 'id_articolo='.prepare($superselect['idarticolo']);
        $where[] = 'lotto='.prepare($superselect['lotto']);
        $where[] = 'serial='.prepare($superselect['serial']);

        foreach ($elements as $element) {
            $filter[] = 'altro='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'altro LIKE '.prepare('%'.$search.'%');
        }

        $custom['id'] = 'descrizione';

        break;

    case 'categorie':
        $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY id';

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
            $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY id';

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
