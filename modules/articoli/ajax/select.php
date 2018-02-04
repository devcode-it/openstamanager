<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'articoli':
        $query = 'SELECT mg_articoli.*, co_iva.descrizione AS iva_vendita FROM mg_articoli LEFT OUTER JOIN co_iva ON mg_articoli.idiva_vendita=co_iva.id |where| ORDER BY mg_articoli.id_categoria ASC, mg_articoli.id_sottocategoria ASC';

        $idiva_predefinita = get_var('Iva predefinita');
        $rs = $dbo->fetchArray("SELECT descrizione FROM co_iva WHERE id='".$idiva_predefinita."'");
        $iva_predefinita = $rs[0]['descrizione'];

        foreach ($elements as $element) {
            $filter[] = 'mg_articoli.id='.prepare($element);
        }

        $where[] = 'attivo=1';
        if (!empty($superselect['dir']) && $superselect['dir'] == 'entrata') {
            //$where[] = 'qta>0';
        }

        if (!empty($search)) {
            $search_fields[] = 'mg_articoli.descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.codice LIKE '.prepare('%'.$search.'%');
        }

        $wh = '';
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        if (count($where) != 0) {
            $wh = 'WHERE '.implode(' AND ', $where);
        }
        $query = str_replace('|where|', $wh, $query);

        $prev = -1;
        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['id_sottocategoria']) {
                $categoria = $dbo->fetchArray('SELECT `nome` FROM `mg_categorie` WHERE `id`='.prepare($r['id_categoria']))[0]['nome'];

                $sottocategoria = $dbo->fetchArray('SELECT `nome` FROM `mg_categorie` WHERE `id`='.prepare($r['id_sottocategoria']))[0]['nome'];

                $prev = $r['id_sottocategoria'];
                $results[] = ['text' => $categoria.' ('.(!empty($r['id_sottocategoria']) ? $sottocategoria : '-').')', 'children' => []];
            }

            if (empty($r['idiva_vendita'])) {
                $idiva = $idiva_predefinita;
                $iva = $iva_predefinita;
            } else {
                $idiva = $r['idiva_vendita'];
                $iva = $r['iva_vendita'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['codice'].' - '.$r['descrizione'],
                'descrizione' => $r['descrizione'],
                'um' => $r['um'],
                'idiva_vendita' => $idiva,
                'iva_vendita' => $iva,
                'prezzo_acquisto' => Translator::numberToLocale($r['prezzo_acquisto']),
                'prezzo_vendita' => Translator::numberToLocale($r['prezzo_vendita']),
            ];
        }

        break;

    case 'prodotti_lotti':
        $query = 'SELECT DISTINCT lotto AS descrizione FROM mg_prodotti |where|';

        $where[] = 'idarticolo='.prepare($superselect['idarticolo']);

        foreach ($elements as $element) {
            $filter[] = 'lotto='.prepare($element).'';
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
            $filter[] = 'serial='.prepare($element).'';
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
            $filter[] = 'altro='.prepare($element).'';
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
            $filter[] = 'valore='.prepare($element).'';
        }
        if (!empty($search)) {
            $search_fields[] = 'valore LIKE '.prepare('%'.$search.'%');
        }

        break;
}
