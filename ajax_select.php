<?php

include_once __DIR__.'/core.php';

$op = empty($op) ? filter('op') : $op;
$search = filter('q');

if (!isset($elements)) {
    $elements = [];
}
$elements = (!is_array($elements)) ? explode(',', $elements) : $elements;

$superselect = !empty($_SESSION['superselect']) ? $_SESSION['superselect'] : [];

$where = [];
$filter = [];
$search_fields = [];

$custom = [
    'id' => 'id',
    'text' => 'descrizione',
];

if (!function_exists('completeResults')) {
    function completeResults($query, $where, $filter = [], $search = [], $custom = [])
    {
        $dbo = Database::getConnection();

        if (str_contains($query, '|filter|')) {
            $query = str_replace('|filter|', !empty($filter) ? 'WHERE '.implode(' OR ', $filter) : '', $query);
        } elseif (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        if (!empty($search)) {
            $where[] = '('.implode(' OR ', $search).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

        $rs = $dbo->fetchArray($query);

        $results = [];
        foreach ($rs as $r) {
            $result = [];
            foreach ($custom as $key => $value) {
                $result[$key] = $r[$value];
            }

            $results[] = $result;
        }

        return $results;
    }
}

switch ($op) {
    case 'clienti':
        if (Modules::get('Anagrafiche')['permessi'] != '-') {
            //$citta_cliente = ", IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))";

            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale $citta_cliente) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Cliente'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento_default';
        }

        break;

    case 'fornitori':
        if (Modules::get('Anagrafiche')['permessi'] != '-') {
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Fornitore'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento_default';
        }
        break;

    case 'agenti':
        if (Modules::get('Anagrafiche')['permessi'] != '-') {
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Agente'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $results = completeResults($query, $where, $filter, $search, $custom);

            // Evidenzia l'agente di default
            if ($superselect['idanagrafica']) {
                $rsa = $dbo->fetchArray('SELECT idagente FROM an_anagrafiche WHERE idanagrafica='.prepare($superselect['idanagrafica']));
                $idagente_default = $rsa[0]['idagente'];
            } else {
                $idagente_default = 0;
            }

            $ids = array_column($results, $id);
            $pos = array_search($idagente_default, $ids);
            if ($pos !== false) {
                $results[$pos]['_bgcolor_'] = '#ff0';
            }
        }
        break;

    case 'tecnici':
        if (Modules::get('Anagrafiche')['permessi'] != '-') {
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Tecnico'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            // $custom['idtipointervento'] = 'idtipointervento_default';
        }
        break;

    // Nota Bene: nel campo id viene specificato idtipoanagrafica-idanagrafica -> modulo Utenti e permessi, creazione nuovo utente
    case 'anagrafiche':
        if (Modules::get('Anagrafiche')['permessi'] != '-') {
            $query = "SELECT CONCAT(an_tipianagrafiche.idtipoanagrafica, '-', an_anagrafiche.idanagrafica) AS id, CONCAT_WS('', ragione_sociale, ' (', citta, ' ', provincia, ')') AS descrizione idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            // $custom['idtipointervento'] = 'idtipointervento_default';
        }
        break;

    case 'sedi':
        if (Modules::get('Anagrafiche')['permessi'] != '-' && isset($superselect['idanagrafica'])) {
            $query = "SELECT * FROM (SELECT 0 AS id, 'Sede legale' AS descrizione UNION SELECT id, CONCAT_WS(' - ', nomesede, citta) FROM an_sedi |where|) AS tab |filter| ORDER BY id";

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'nomesede LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'referenti':
        if (Modules::get('Anagrafiche')['permessi'] != '-' && isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, nome AS descrizione FROM an_referenti |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'articoli':
        $query = 'SELECT mg_articoli.*, co_iva.descrizione AS iva_vendita FROM mg_articoli LEFT OUTER JOIN co_iva ON mg_articoli.idiva_vendita=co_iva.id |where| ORDER BY mg_articoli.id_categoria ASC, mg_articoli.id_sottocategoria ASC';

        $idiva_predefinita = get_var('Iva predefinita');
        $rs = $dbo->fetchArray("SELECT descrizione FROM co_iva WHERE id='".$idiva_predefinita."'");
        $iva_predefinita = $rs[0]['descrizione'];

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

        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $wh = '';
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

    case 'conti':
        if (Modules::get('Piano dei conti')['permessi'] != '-') {
            $query = 'SELECT * FROM co_pianodeiconti2';

            $rs = $dbo->fetchArray($query);
            foreach ($rs as $r) {
                $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

                $subquery = 'SELECT * FROM co_pianodeiconti3 |where|';

                $where = [];
                $filter = [];
                $search_fields = [];

                foreach ($elements as $element) {
                    $filter[] = 'id='.prepare($element);
                }
                if (!empty($filter)) {
                    $where[] = '('.implode(' OR ', $filter).')';
                }

                $where[] = 'idpianodeiconti2='.prepare($r['id']);

                if (!empty($search)) {
                    $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
                }
                if (!empty($search_fields)) {
                    $where[] = '('.implode(' OR ', $search_fields).')';
                }

                $wh = '';
                if (count($where) != 0) {
                    $wh = 'WHERE '.implode(' AND ', $where);
                }
                $subquery = str_replace('|where|', $wh, $subquery);

                $rs2 = $dbo->fetchArray($subquery);
                foreach ($rs2 as $r2) {
                    $results[count($results) - 1]['children'][] = ['id' => $r2['id'], 'text' => $r2['descrizione']];
                }
            }
        }
        break;

    case 'conti-vendite':
        if (Modules::get('Piano dei conti')['permessi'] != '-') {
            $query = "SELECT co_pianodeiconti3.id, CONCAT_WS( ' ', co_pianodeiconti3.numero, co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti3.numero ASC";

            foreach ($elements as $element) {
                $filter[] = 'co_pianodeiconti3.id='.prepare($element);
            }

            $where[] = "co_pianodeiconti1.descrizione='Economico'";
            $where[] = "co_pianodeiconti3.dir='entrata'";

            if (!empty($search)) {
                $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'conti-acquisti':
        if (Modules::get('Piano dei conti')['permessi'] != '-') {
            $query = "SELECT co_pianodeiconti3.id, CONCAT_WS( ' ', co_pianodeiconti3.numero, co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| ORDER BY co_pianodeiconti3.numero ASC";

            foreach ($elements as $element) {
                $filter[] = 'co_pianodeiconti3.id='.prepare($element);
            }

            $where[] = "co_pianodeiconti1.descrizione='Economico'";
            $where[] = "co_pianodeiconti3.dir='uscita'";

            if (!empty($search)) {
                $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'impianti':
        if (Modules::get('MyImpianti')['permessi'] != '-' && isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = 'idsede='.prepare($superselect['idsede']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'componenti':
        if (Modules::get('Gestione componenti')['permessi'] != '-' && isset($superselect['marticola'])) {
            $query = 'SELECT id, nome AS descrizione, contenuto FROM my_impianto_componenti |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'idimpianto='.prepare($element);
            }

            $temp = [];
            $impianti = explode(',', $superselect['marticola']);
            foreach ($impianti as $key => $idimpianto) {
                $temp[] = 'idimpianto='.prepare($idimpianto);
            }
            $where[] = '('.implode(' OR ', $temp).')';

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $custom['contenuto'] = 'contenuto';

            $results = completeResults($query, $where, $filter, $search, $custom);
            foreach ($results as $key => $value) {
                $matricola = \Util\Ini::getValue($r['contenuto'], 'Matricola');

                $results[$key]['text'] = (empty($matricola) ? '' : $matricola.' - ').$results[$key]['text'];

                unset($results[$key]['content']);
            }
        }

        break;

    case 'categorie':
        if (Modules::get('Magazzino')['permessi'] != '-') {
            $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = '`parent` IS NULL';

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'sottocategorie':
        if (Modules::get('Magazzino')['permessi'] != '-' && isset($superselect['id_categoria'])) {
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

    case 'preventivi':
        if (Modules::get('Preventivi')['permessi'] != '-' && isset($superselect['idanagrafica'])) {
            $query = 'SELECT co_preventivi.id AS id, an_anagrafiche.idanagrafica, CONCAT(numero, " ", nome) AS descrizione, co_preventivi.idtipointervento, (SELECT descrizione descrizione FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento = co_preventivi.idtipointervento) AS idtipointervento_descrizione FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = "idstato NOT IN (SELECT `id` FROM co_statipreventivi WHERE descrizione='Bozza' OR descrizione='Rifiutato' OR descrizione='Pagato')";

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento';
            $custom['idtipointervento_descrizione'] = 'idtipointervento_descrizione';
        }
        break;

    case 'preventivi_aperti':
        if (Modules::get('Preventivi')['permessi'] != '-') {
            $query = 'SELECT co_preventivi.id AS id, CONCAT(numero, " ", nome, " (", ragione_sociale, ")") AS descrizione FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'idpreventivo='.prepare($element);
            }
            $where[] = 'idstato IN (1)';
            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'contratti':
        if (Modules::get('Contratti')['permessi'] != '-') {
            $query = 'SELECT co_contratti.id AS id, CONCAT(numero, " ", nome) AS descrizione FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = 'idstato IN (SELECT `id` FROM co_staticontratti WHERE pianificabile = 1)';

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'tipiintervento':
        if (Modules::get('Interventi')['permessi'] != '-') {
            $query = 'SELECT idtipointervento AS id, descrizione FROM in_tipiintervento |where| ORDER BY idtipointervento';

            foreach ($elements as $element) {
                $filter[] = 'idtipointervento='.prepare($element);
            }
            if (!empty($search)) {
                $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'misure':
        if (Modules::get('Magazzino')['permessi'] != '-') {
            $query = 'SELECT valore AS id, valore AS descrizione FROM mg_unitamisura |where| ORDER BY valore';

            foreach ($elements as $element) {
                $filter[] = 'valore='.prepare($element).'';
            }
            if (!empty($search)) {
                $search_fields[] = 'valore LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'prodotti_lotti':
        if (Modules::get('Magazzino')['permessi'] != '-') {
            $query = 'SELECT DISTINCT lotto AS descrizione FROM mg_prodotti |where|';

            $where[] = 'idarticolo='.prepare($superselect['idarticolo']);

            foreach ($elements as $element) {
                $filter[] = 'lotto='.prepare($element).'';
            }

            if (!empty($search)) {
                $search_fields[] = 'lotto LIKE '.prepare('%'.$search.'%');
            }

            $custom['id'] = 'descrizione';
        }
        break;

    case 'prodotti_serial':
        if (Modules::get('Magazzino')['permessi'] != '-') {
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
        }
        break;

    case 'prodotti_altro':
        if (Modules::get('Magazzino')['permessi'] != '-') {
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
        }
        break;
}

if (!isset($results) && !empty($query)) {
    $results = completeResults($query, $where, $filter, $search_fields, $custom);
}

echo json_encode($results);
