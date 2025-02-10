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

/*
 * Funzioni globali utilizzate per il funzionamento dei componenti indipendenti del progetto (moduli, plugin, stampe, ...).
 *
 * @since 2.4.2
 */
use Common\Components\Accounting;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;

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

        $percentages = explode('+', (string) $data['sconto']);
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
 */
function orderValue($table, $field, $id)
{
    return database()->fetchOne('SELECT IFNULL(MAX(`order`) + 1, 1) AS value FROM '.$table.' WHERE '.$field.' = '.prepare($id))['value'];
}

/**
 * Ricalcola il riordinamento righe di una tabella.
 */
function reorderRows($table, $field, $id)
{
    $righe = database()->select($table, 'id', [], [$field => $id], ['order' => 'ASC']);
    $i = 1;

    foreach ($righe as $riga) {
        database()->query('UPDATE '.$table.' SET `order`='.$i.' WHERE id='.prepare($riga['id']));
        ++$i;
    }
}

/**
 * Visualizza le informazioni relative allo sconto presente su una riga.
 *
 * @param bool $mostra_maggiorazione
 *
 * @return string|null
 */
function discountInfo(Accounting $riga, $mostra_maggiorazione = true)
{
    if (empty($riga->sconto_unitario) || (!$mostra_maggiorazione && $riga->sconto_unitario < 0)) {
        return null;
    }

    $text = ($riga->prezzo_unitario >= 0 && $riga->sconto_unitario > 0) || ($riga->prezzo_unitario < 0 && $riga->sconto_unitario < 0) ? tr('sconto _TOT_ _TYPE_') : tr('maggiorazione _TOT__TYPE_');
    $totale = !empty($riga->sconto_percentuale) ? $riga->sconto_percentuale : $riga->sconto_unitario_corrente;

    return replace($text, [
        '_TOT_' => Translator::numberToLocale(abs($totale)),
        '_TYPE_' => !empty($riga->sconto_percentuale) ? '%' : currency(),
    ]);
}

/**
 * Visualizza le informazioni relative allo provvigione presente su una riga.
 *
 * @param bool $mostra_provigione
 *
 * @return string|null
 */
function provvigioneInfo(Accounting $riga, $mostra_provigione = true)
{
    if (empty($riga->provvigione_unitaria) || (!$mostra_provigione && $riga->provvigione_unitaria < 0)) {
        return null;
    }

    $text = $riga->provvigione_unitaria > 0 ? tr('provvigione _TOT_ _TYPE_') : tr('provvigione _TOT__TYPE_');
    $totale = !empty($riga->provvigione_percentuale) ? $riga->provvigione_percentuale : $riga->provvigione_unitaria;

    return replace($text, [
        '_TOT_' => Translator::numberToLocale(abs($totale)),
        '_TYPE_' => !empty($riga->provvigione_percentuale) ? '%' : currency(),
    ]);
}

/**
 * Genera i riferimenti ai documenti del gestionale, attraverso l'interfaccia Common\ReferenceInterface.
 *
 * @param string $text Formato "Contenuto descrittivo _DOCUMENT_"
 *
 * @return string
 */
function reference($document, $text = null)
{
    if (!empty($document) && !($document instanceof Common\ReferenceInterface)) {
        return null;
    }

    $extra = '';
    $module_id = null;
    $document_id = null;

    if (empty($document)) {
        $content = tr('non disponibile');
        $extra = 'class="disabled"';
    } else {
        $module_id = $document->module;
        $document_id = $document->id;

        $content = $document->getReference();
    }

    $description = $text ?: tr('Rif. _DOCUMENT_', [
        '_DOCUMENT_' => strtolower((string) $content),
    ]);

    return Modules::link($module_id, $document_id, $description, $description, $extra);
}

function getDestinationComponents($riga)
{
    $documents = [];

    $contratti = database()->table('co_righe_contratti')->where('original_id', $riga->id)->where('original_type', $riga::class)->get();
    foreach ($contratti as $contratto) {
        $documents['documento'][] = Contratto::find($contratto->idcontratto);
        $documents['qta'][] = $contratto->qta;
    }
    $fatture = database()->table('co_righe_documenti')->where('original_id', $riga->id)->where('original_type', $riga::class)->get();
    foreach ($fatture as $fattura) {
        $documents['documento'][] = Fattura::find($fattura->iddocumento);
        $documents['qta'][] = $fattura->qta;
    }
    $ddts = database()->table('dt_righe_ddt')->where('original_id', $riga->id)->where('original_type', $riga::class)->get();
    foreach ($ddts as $ddt) {
        $documents['documento'][] = DDT::find($ddt->idddt);
        $documents['qta'][] = $ddt->qta;
    }
    $interventi = database()->table('in_righe_interventi')->where('original_id', $riga->id)->where('original_type', $riga::class)->get();
    foreach ($interventi as $intervento) {
        $documents['documento'][] = Intervento::find($intervento->idintervento);
        $documents['qta'][] = $intervento->qta;
    }
    $ordini = database()->table('or_righe_ordini')->where('original_id', $riga->id)->where('original_type', $riga::class)->get();
    foreach ($ordini as $ordine) {
        $documents['documento'][] = Ordine::find($ordine->idordine);
        $documents['qta'][] = $ordine->qta;
    }

    return $documents;
}

/**
 * Funzione che gestisce il parsing di uno sconto combinato e la relativa trasformazione in sconto fisso.
 * Esempio: (40 + 10) % = 44 %.
 *
 * @return float|int
 */
function parseScontoCombinato($combinato)
{
    $sign = substr((string) $combinato, 0, 1);
    $original = $sign != '+' && $sign != '-' ? '+'.$combinato : $combinato;
    $pieces = preg_split('/[+,-]+/', (string) $original);
    unset($pieces[0]);

    $result = 1;
    $text = $original;
    foreach ($pieces as $piece) {
        $sign = substr((string) $text, 0, 1);
        $text = substr((string) $text, 1 + strlen($piece));

        $result *= 1 - floatval($sign.$piece) / 100;
    }

    return (1 - $result) * 100;
}

/**
 * Visualizza le informazioni del segmento.
 *
 * @return float|int
 */
function getSegmentPredefined($id_module)
{
    $id_segment = database()->selectOne('zz_segments', 'id', ['id_module' => $id_module, 'predefined' => 1])['id'];

    return $id_segment;
}

/**
 * Funzione che visualizza i prezzi degli articoli nei listini.
 *
 * @return array
 */
function getPrezzoConsigliato($id_anagrafica, $direzione, $id_articolo, $riga = null)
{
    if ($riga) {
        $qta = $riga->qta;
        $prezzo_unitario_corrente = $riga->prezzo_unitario_corrente;
        $sconto_percentuale_corrente = $riga->sconto_percentuale;
    } else {
        $qta = 1;
    }
    $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
    $show_notifica_prezzo = null;
    $show_notifica_sconto = null;
    $prezzo_unitario = 0;
    $sconto = 0;

    // Prezzi netti clienti / listino fornitore
    $query = 'SELECT minimo, massimo,
        sconto_percentuale,
        '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
    FROM mg_prezzi_articoli
    WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione).' AND id_anagrafica = '.prepare($id_anagrafica).'
    ORDER BY minimo ASC, massimo DESC';
    $prezzi = database()->fetchArray($query);

    // Prezzi listini clienti
    $query = 'SELECT sconto_percentuale AS sconto_percentuale_listino,
        '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino
    FROM mg_listini
    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
    LEFT JOIN an_anagrafiche ON mg_listini.id=an_anagrafiche.id_listino
    WHERE mg_listini.data_attivazione<=NOW() 
    AND (mg_listini_articoli.data_scadenza>=NOW() OR (mg_listini_articoli.data_scadenza IS NULL AND mg_listini.data_scadenza_predefinita>=NOW()))
    AND mg_listini.attivo=1
    AND id_articolo = '.prepare($id_articolo).'
    AND dir = '.prepare($direzione).'
    AND idanagrafica = '.prepare($id_anagrafica);
    $listino = database()->fetchOne($query);

    // Prezzi listini clienti sempre visibili
    $query = 'SELECT mg_listini.nome, sconto_percentuale AS sconto_percentuale_listino_visibile,
        '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino_visibile
    FROM mg_listini
    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
    WHERE mg_listini.data_attivazione<=NOW()
    AND (mg_listini_articoli.data_scadenza>=NOW() OR (mg_listini_articoli.data_scadenza IS NULL AND mg_listini.data_scadenza_predefinita>=NOW()))
    AND mg_listini.attivo=1 AND mg_listini.is_sempre_visibile=1 AND id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione);
    $listini_sempre_visibili = database()->fetchArray($query);

    if ($prezzi) {
        foreach ($prezzi as $prezzo) {
            if ($qta >= $prezzo['minimo'] && $qta <= $prezzo['massimo']) {
                $show_notifica_prezzo = $prezzo['prezzo_unitario'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
                $show_notifica_sconto = $prezzo['sconto_percentuale'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
                $prezzo_unitario = $prezzo['prezzo_unitario'];
                $sconto = $prezzo['sconto_percentuale'];
                continue;
            }

            if ($prezzo['minimo'] == null && $prezzo['massimo'] == null && $prezzo['prezzo_unitario'] != null) {
                $show_notifica_prezzo = $prezzo['prezzo_unitario'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
                $show_notifica_sconto = $prezzo['sconto_percentuale'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
                $prezzo_unitario = $prezzo['prezzo_unitario'];
                $sconto = $prezzo['sconto_percentuale'];
                continue;
            }
        }
    }
    if ($listino) {
        $show_notifica_prezzo = $listino['prezzo_unitario_listino'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
        $show_notifica_sconto = $listino['sconto_percentuale_listino'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
        $prezzo_unitario = $listino['prezzo_unitario_listino'];
        $sconto = $listino['sconto_percentuale_listino'];
    }
    if ($listini_sempre_visibili) {
        foreach ($listini_sempre_visibili as $listino_sempre_visibile) {
            $show_notifica_prezzo = $listino_sempre_visibile['prezzo_unitario_listino_visibile'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
            $show_notifica_sconto = $listino_sempre_visibile['sconto_percentuale_listino_visibile'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
        }
    }

    $result = [];
    $result['show_notifica_prezzo'] = $show_notifica_prezzo;
    $result['show_notifica_sconto'] = $show_notifica_sconto;
    $result['prezzo_unitario'] = $prezzo_unitario;
    $result['sconto'] = $sconto;

    return $result;
}

/**
 * Funzione PHP che controlla se un campo "cellulare" contiene già un prefisso telefonico:
 *
 * @return bool
 */
function checkPrefix($cellulare)
{
    // Array di prefissi telefonici da controllare
    $internationalPrefixes = ['+1', '+44', '+49', '+33', '+39']; // Esempi di prefissi

    // Controlla se il campo "cellulare" inizia con uno dei prefissi
    foreach ($internationalPrefixes as $prefix) {
        if (str_starts_with((string) $cellulare, $prefix)) {
            return true; // Un prefisso è già presente
        }
    }

    return false; // Nessun prefisso trovato
}

/**
 * Funzione PHP che dato id_modulo restituisce un array contenente tutti i valori di "search_" per quel modulo.
 *
 * @param int $id_module
 *
 * @return array
 */
function getSearchValues($id_module)
{
    $result = [];

    if (isset($_SESSION['module_'.$id_module])) {
        // Itera su tutti i valori
        foreach ($_SESSION['module_'.$id_module] as $key => $value) {
            // Controlla se la chiave inizia con "search_"
            if (!empty($value) && string_starts_with($key, 'search_')) {
                $result[str_replace(['search_', '-'], ['', ' '], $key)] = $value;
            }
        }
    }

    return $result;
}

/**
 * Funzione PHP che controlla se l'articolo ha una distinta.
 *
 * @param int $id_articolo
 *
 * @return bool
 */
function hasArticoliFiglio($id_articolo)
{
    if (function_exists('renderDistinta')) {
        return database()->fetchOne('SELECT qta FROM mg_articoli_distinte WHERE id_articolo='.prepare($id_articolo));
    } else {
        return false;
    }
}
