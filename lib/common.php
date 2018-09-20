<?php

/**
 * Funzioni globali utilizzate per il funzionamento dei componenti indipendenti del progetto (moduli, plugin, stampe, ...).
 *
 * @since 2.4.2
 */

/**
 * Esegue una somma precisa tra due interi/array.
 *
 * @param array|float $first
 * @param array|float $second
 * @param int         $decimals
 *
 * @since 2.3
 *
 * @return float
 */
function sum($first, $second = null, $decimals = 4)
{
    $first = (array) $first;
    $second = (array) $second;

    $array = array_merge($first, $second);

    $result = 0;

    $decimals = is_numeric($decimals) ? $decimals : formatter()->getPrecision();

    $bcadd = function_exists('bcadd');

    foreach ($array as $value) {
        $value = round($value, $decimals);

        if ($bcadd) {
            $result = bcadd($result, $value, $decimals);
        } else {
            $result += $value;
        }
    }

    return floatval($result);
}

function aggiorna_sconto($tables, $fields, $id_record, $options = [])
{
    $dbo = database();

    $descrizione = tr('Sconto', [], ['upper' => true]);

    // Rimozione dello sconto precedente
    $dbo->query('DELETE FROM '.$tables['row'].' WHERE sconto_globale = 1 AND '.$fields['row'].'='.prepare($id_record));

    // Individuazione del nuovo sconto
    $sconto = $dbo->select($tables['parent'], ['sconto_globale', 'tipo_sconto_globale'], [$fields['parent'] => $id_record]);
    $sconto[0]['sconto_globale'] = floatval($sconto[0]['sconto_globale']);

    // Aggiorno l'eventuale sconto gestendolo con le righe in fattura
    $iva = 0;

    if (!empty($sconto[0]['sconto_globale'])) {
        if ($sconto[0]['tipo_sconto_globale'] == 'PRC') {
            $rs = $dbo->fetchArray('SELECT SUM(subtotale - sconto) AS imponibile, SUM(iva) AS iva FROM (SELECT '.$tables['row'].'.subtotale, '.$tables['row'].'.sconto, '.$tables['row'].'.iva FROM '.$tables['row'].' WHERE '.$fields['row'].'='.prepare($id_record).') AS t');
            $subtotale = $rs[0]['imponibile'];
            $iva += $rs[0]['iva'] / 100 * $sconto[0]['sconto_globale'];
            $subtotale = -$subtotale / 100 * $sconto[0]['sconto_globale'];

            $descrizione = $descrizione.' '.Translator::numberToLocale($sconto[0]['sconto_globale']).'%';
        } else {
            $rs = $dbo->fetchArray('SELECT SUM(subtotale - sconto) AS imponibile, SUM(iva) AS iva FROM (SELECT '.$tables['row'].'.subtotale, '.$tables['row'].'.sconto, '.$tables['row'].'.iva FROM '.$tables['row'].' WHERE '.$fields['row'].'='.prepare($id_record).') AS t');
            $subtotale = $rs[0]['imponibile'];
            $iva += $sconto[0]['sconto_globale'] * $rs[0]['iva'] / $subtotale;

            $subtotale = -$sconto[0]['sconto_globale'];
        }

        // Calcolo dell'IVA da scontare
        $idiva = setting('Iva predefinita');
        $rsi = $dbo->select('co_iva', ['descrizione', 'percentuale'], ['id' => $idiva]);

        $values = [
            $fields['row'] => $id_record,
            'descrizione' => $descrizione,
            'subtotale' => $subtotale,
            'qta' => 1,
            'idiva' => $idiva,
            'desc_iva' => $rsi[0]['descrizione'],
            'iva' => -$iva,
            'sconto_globale' => 1,
            '#order' => '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM '.$tables['row'].' AS t WHERE '.$fields['row'].'='.prepare($id_record).')',
        ];

        $dbo->insert($tables['row'], $values);
    }
}

function controlla_seriali($field, $id_riga, $old_qta, $new_qta, $dir)
{
    $dbo = database();

    $new_qta = abs($new_qta);
    $old_qta = abs($old_qta);

    if ($old_qta >= $new_qta) {
        // Controllo sulla possibilità di rimuovere i seriali (se non utilizzati da documenti di vendita)
        if ($dir == 'uscita' && $new_qta < count(seriali_non_rimuovibili($field, $id_riga, $dir))) {
            return false;
        } else {
            // Controllo sul numero di seriali effettivi da rimuovere
            $count = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM mg_prodotti WHERE '.$field.'='.prepare($id_riga))[0]['tot'];
            if ($new_qta < $count) {
                $deletes = $dbo->fetchArray("SELECT id FROM mg_prodotti WHERE serial NOT IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata' AND ".$field.'!='.prepare($id_riga).') AND '.$field.'='.prepare($id_riga).' ORDER BY serial DESC LIMIT '.abs($count - $new_qta));

                // Rimozione
                foreach ($deletes as $delete) {
                    $dbo->query('DELETE FROM mg_prodotti WHERE id = '.prepare($delete['id']));
                }
            }
        }
    }

    return true;
}

/**
 * Individua i seriali non rimuovibili poichè utilizzati in documenti rilasciati.
 *
 * @param string $field
 * @param int    $id_riga
 * @param string $dir
 *
 * @return array
 */
function seriali_non_rimuovibili($field, $id_riga, $dir)
{
    $dbo = database();

    $results = [];

    if ($dir == 'uscita') {
        $results = $dbo->fetchArray("SELECT serial FROM mg_prodotti WHERE serial IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata') AND ".$field.'='.prepare($id_riga));
    }

    return $results;
}

/**
 * Calcola gli sconti in modo automatico.
 *
 * @param array $data
 *
 * @return float
 */
function calcola_sconto($data)
{
    if ($data['tipo'] == 'PRC') {
        $result = 0;

        $price = floatval($data['prezzo']);

        $percentages = explode('+', $data['sconto']);
        foreach ($percentages as $percentage) {
            $discount = $price / 100 * floatval($percentage);

            $result += $discount;
            $price -= $discount;
        }
    } else {
        $result = floatval($data['sconto']);
    }

    if (!empty($data['qta'])) {
        $result = $result * $data['qta'];
    }

    return $result;
}

/**
 * Restistuisce le informazioni sull'eventuale riferimento ai documenti.
 *
 * @param array  $data
 * @param string $dir
 *
 * @return array
 */
function doc_references($info, $dir, $ignore = [])
{
    $dbo = database();

    // Rimozione valori da non controllare
    foreach ($ignore as $field) {
        if (isset($info[$field])) {
            unset($info[$field]);
        }
    }

    $module = null;
    $id = null;

    // Ordine
    if (!empty($info['idordine'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM or_ordini WHERE id=".prepare($info['idordine']));

        $module = ($dir == 'entrata') ? 'Ordini cliente' : 'Ordini fornitore';
        $id = $info['idordine'];

        $document = tr('Ordine');
    }

    // DDT
    elseif (!empty($info['idddt'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM dt_ddt WHERE id=".prepare($info['idddt']));

        $module = ($dir == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
        $id = $info['idddt'];

        $document = tr('Ddt');
    }

    // Preventivo
    elseif (!empty($info['idpreventivo'])) {
        $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_preventivi WHERE id='.prepare($info['idpreventivo']));

        $module = 'Preventivi';
        $id = $info['idpreventivo'];

        $document = tr('Preventivo');
    }

    // Contratto
    elseif (!empty($info['idcontratto'])) {
        $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_contratti WHERE id='.prepare($info['idcontratto']));

        $module = 'Contratti';
        $id = $info['idcontratto'];

        $document = tr('Contratto');
    }

    // Intervento
    elseif (!empty($info['idintervento'])) {
        $data = $dbo->fetchArray('SELECT codice AS numero, IFNULL( (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta) AS data FROM in_interventi WHERE id='.prepare($info['idintervento']));

        $module = 'Interventi';
        $id = $info['idintervento'];

        $document = tr('Intervento');
    }

    // Testo relativo
    if (!empty($module) && !empty($id)) {
        $document = Stringy\Stringy::create($document)->toLowerCase();

        if (!empty($data)) {
            $description = tr('Rif. _DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $document,
                '_NUM_' => $data[0]['numero'],
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]);
        } else {
            $description = tr('_DOC_ di riferimento _ID_ eliminato', [
                '_DOC_' => $document->upperCaseFirst(),
                '_ID_' => $id,
            ]);
        }

        return [
            'module' => $module,
            'id' => $id,
            'description' => $description,
        ];
    }

    return [];
}
