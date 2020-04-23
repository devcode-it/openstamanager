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
 * Individua il valore della colonna order per i nuovi elementi di una tabella.
 *
 * @param $table
 * @param $field
 * @param $id
 *
 * @return mixed
 */
function orderValue($table, $field, $id)
{
    return database()->fetchOne('SELECT IFNULL(MAX(`order`) + 1, 0) AS value FROM '.$table.' WHERE '.$field.' = '.prepare($id))['value'];
}

/**
 * Visualizza le informazioni relative allo sconto presente su una riga.
 *
 * @param bool $mostra_maggiorazione
 *
 * @return string|null
 */
function discountInfo(\Common\Components\Row $riga, $mostra_maggiorazione = true)
{
    if (empty($riga->sconto_unitario) || (!$mostra_maggiorazione && $riga->sconto_unitario < 0)) {
        return null;
    }

    $text = $riga->sconto_unitario > 0 ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT_ _TYPE_');

    return replace($text, [
        '_TOT_' => Translator::numberToLocale(!empty($riga->sconto_percentuale) ? $riga->sconto_percentuale : $riga->sconto_unitario),
        '_TYPE_' => (!empty($riga->sconto_percentuale) ? '%' : currency()),
    ]);
}

/**
 * Genera i riferimenti ai documenti del gestionale, attraverso l'interfaccia Common\ReferenceInterface.
 *
 * @param $document
 *
 * @return string
 */
function reference($document)
{
    if (!empty($document) && !($document instanceof \Common\ReferenceInterface)) {
        return;
    }

    $extra = '';
    $module_id = null;
    $document_id = null;

    if (empty($document)) {
        $description = tr('Documento di riferimento non disponibile');
        $extra = 'class="disabled"';
    } else {
        $module_id = $document->module;
        $document_id = $document->id;

        $description = $document->getReference();
    }

    return Modules::link($module_id, $document_id, $description, $description, $extra);
}

/**
 * Funzione che gestisce il parsing di uno sconto combinato e la relativa trasformazione in sconto fisso.
 * Esempio: (40 + 10) % = 44 %.
 *
 * @param $combinato
 *
 * @return float|int
 */
function parseScontoCombinato($combinato)
{
    $sign = substr($combinato, 0, 1);
    $original = $sign != '+' && $sign != '-' ? '+'.$combinato : $combinato;
    $pieces = preg_split('/[+,-]+/', $original);
    unset($pieces[0]);

    $result = 1;
    $text = $original;
    foreach ($pieces as $piece) {
        $sign = substr($text, 0, 1);
        $text = substr($text, 1 + strlen($piece));

        $result *= 1 - floatval($sign.$piece) / 100;
    }

    return (1 - $result) * 100;
}
