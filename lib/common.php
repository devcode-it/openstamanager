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
use Intervention\Image\ImageManager;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Contratti\Contratto;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Util\Generator;

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

    $result = $result * $data['qta'];

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
    $sconto_combinato = $riga->sconto_percentuale_combinato ? '('.$riga->sconto_percentuale_combinato.')' : '';

    return replace($text, [
        '_TOT_' => $sconto_combinato ?: Translator::numberToLocale(abs($totale)),
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

    // Ottimizzazione: usa eager loading per evitare il problema N+1
    $contratti = Contratto::whereIn('id', function ($query) use ($riga) {
        $query->select('idcontratto')
            ->from('co_righe_contratti')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class);
    })->get();
    foreach ($contratti as $contratto) {
        $riga_contratto = database()->table('co_righe_contratti')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class)
            ->where('idcontratto', $contratto->id)
            ->first();
        $documents['documento'][] = $contratto;
        $documents['qta'][] = $riga_contratto->qta;
    }

    $fatture = Fattura::whereIn('id', function ($query) use ($riga) {
        $query->select('iddocumento')
            ->from('co_righe_documenti')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class);
    })->get();
    foreach ($fatture as $fattura) {
        $riga_fattura = database()->table('co_righe_documenti')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class)
            ->where('iddocumento', $fattura->id)
            ->first();
        $documents['documento'][] = $fattura;
        $documents['qta'][] = $riga_fattura->qta;
    }

    $ddts = DDT::whereIn('id', function ($query) use ($riga) {
        $query->select('idddt')
            ->from('dt_righe_ddt')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class);
    })->get();
    foreach ($ddts as $ddt) {
        $riga_ddt = database()->table('dt_righe_ddt')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class)
            ->where('idddt', $ddt->id)
            ->first();
        $documents['documento'][] = $ddt;
        $documents['qta'][] = $riga_ddt->qta;
    }

    $interventi = Intervento::whereIn('id', function ($query) use ($riga) {
        $query->select('idintervento')
            ->from('in_righe_interventi')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class);
    })->get();
    foreach ($interventi as $intervento) {
        $riga_intervento = database()->table('in_righe_interventi')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class)
            ->where('idintervento', $intervento->id)
            ->first();
        $documents['documento'][] = $intervento;
        $documents['qta'][] = $riga_intervento->qta;
    }

    $ordini = Ordine::whereIn('id', function ($query) use ($riga) {
        $query->select('idordine')
            ->from('or_righe_ordini')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class);
    })->get();
    foreach ($ordini as $ordine) {
        $riga_ordine = database()->table('or_righe_ordini')
            ->where('original_id', $riga->id)
            ->where('original_type', $riga::class)
            ->where('idordine', $ordine->id)
            ->first();
        $documents['documento'][] = $ordine;
        $documents['qta'][] = $riga_ordine->qta;
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
    $query = 'SELECT minimo, massimo,
        sconto_percentuale AS sconto_percentuale_listino,
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
    $listini = database()->fetchArray($query);

    // Prezzi listini clienti sempre visibili
    $query = 'SELECT mg_listini.nome, minimo, massimo, sconto_percentuale AS sconto_percentuale_listino_visibile,
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
    // Gestione listini con logica minimo/massimo
    $listino_predefinito = null;
    $listino_selezionato = null;
    if ($listini) {
        foreach ($listini as $listino_item) {
            if ($listino_item['minimo'] == null && $listino_item['massimo'] == null && $listino_item['prezzo_unitario_listino'] != null) {
                $listino_predefinito = $listino_item;
                continue;
            }

            if ($qta >= $listino_item['minimo'] && $qta <= $listino_item['massimo']) {
                $listino_selezionato = $listino_item;
            }
        }

        $listino = $listino_selezionato ?: $listino_predefinito;

        if ($listino) {
            $show_notifica_prezzo = $listino['prezzo_unitario_listino'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
            $show_notifica_sconto = $listino['sconto_percentuale_listino'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
            $prezzo_unitario = $listino['prezzo_unitario_listino'];
            $sconto = $listino['sconto_percentuale_listino'];
        }
    }

    // Gestione listini sempre visibili con logica minimo/massimo
    if ($listini_sempre_visibili) {
        // Raggruppa i listini sempre visibili per nome
        $listini_visibili_per_nome = [];
        foreach ($listini_sempre_visibili as $listino_visibile) {
            $nome = $listino_visibile['nome'];
            if (!isset($listini_visibili_per_nome[$nome])) {
                $listini_visibili_per_nome[$nome] = [];
            }
            $listini_visibili_per_nome[$nome][] = $listino_visibile;
        }

        // Per ogni nome, seleziona il listino appropriato per la quantità
        foreach ($listini_visibili_per_nome as $nome => $listini_con_stesso_nome) {
            $listino_predefinito_visibile = null;
            $listino_selezionato_visibile = null;
            foreach ($listini_con_stesso_nome as $listino_visibile) {
                if ($listino_visibile['minimo'] == null && $listino_visibile['massimo'] == null && $listino_visibile['prezzo_unitario_listino_visibile'] != null) {
                    $listino_predefinito_visibile = $listino_visibile;
                    continue;
                }

                if ($qta >= $listino_visibile['minimo'] && $qta <= $listino_visibile['massimo']) {
                    $listino_selezionato_visibile = $listino_visibile;
                }
            }

            $listino_visibile_selezionato = $listino_selezionato_visibile ?: $listino_predefinito_visibile;

            if ($listino_visibile_selezionato) {
                $show_notifica_prezzo = $listino_visibile_selezionato['prezzo_unitario_listino_visibile'] != $prezzo_unitario_corrente ? true : $show_notifica_prezzo;
                $show_notifica_sconto = $listino_visibile_selezionato['sconto_percentuale_listino_visibile'] != $sconto_percentuale_corrente ? true : $show_notifica_sconto;
            }
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
            // Controlla se la chiave inizia con "_search_"
            if (!empty($value) && string_starts_with($key, '_search_')) {
                $result[str_replace(['_search_', '-'], ['', ' '], $key)] = $value;
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
    }

    return false;
}

/**
 * Funzione per generare una classe helper standard per le immagini.
 *
 * @return ImageManager
 */
function getImageManager()
{
    return extension_loaded('gd') ? ImageManager::gd() : ImageManager::imagick();
}

/**
 * Determina la banca dell'azienda da utilizzare per il documento.
 *
 * @param \Modules\Anagrafiche\Anagrafica $azienda Anagrafica dell'azienda
 * @param int $id_pagamento ID del tipo di pagamento
 * @param string $conto Tipo di conto (vendite/acquisti)
 * @param string $direzione Direzione del documento (entrata/uscita)
 * @param \Modules\Anagrafiche\Anagrafica $anagrafica_controparte Anagrafica della controparte
 *
 * @return int|null ID della banca selezionata
 */
function getBancaAzienda($azienda, $id_pagamento, $conto, $direzione, $anagrafica_controparte)
{
    $database = database();

    // Per i documenti di vendita, priorità alla banca dell'azienda
    // Per i documenti di acquisto, priorità alla banca del fornitore
    $anagrafica_principale = ($direzione == 'entrata') ? $azienda : $anagrafica_controparte;

    // Pulizia preventiva dei riferimenti a banche inesistenti nell'anagrafica
    cleanInvalidBankReferences($azienda);
    cleanInvalidBankReferences($anagrafica_controparte);

    // Per i documenti di vendita, verifica prima la banca predefinita per accrediti del cliente
    if ($direzione == 'entrata' && !empty($anagrafica_controparte->idbanca_vendite)) {
        $id_banca = $anagrafica_controparte->idbanca_vendite;

        // Verifica che la banca esista effettivamente
        $banca_esistente = Banca::find($id_banca);
        if (!$banca_esistente || $banca_esistente->deleted_at) {
            $id_banca = null;
        }

        // Se la banca del cliente è valida, la restituisce
        if ($id_banca) {
            return $id_banca;
        }
    }

    // 1. Banca predefinita dell'anagrafica principale per il tipo di operazione
    $id_banca = $anagrafica_principale->{"idbanca_{$conto}"};

    // Verifica che la banca esista effettivamente
    if ($id_banca) {
        $banca_esistente = Banca::find($id_banca);
        if (!$banca_esistente || $banca_esistente->deleted_at) {
            $id_banca = null;
        }
    }

    // 2. Banca dell'azienda con conto corrispondente al tipo di pagamento (predefinita)
    if (empty($id_banca)) {
        $id_banca = getBancaByPagamento($database, $azienda->id, $id_pagamento, $conto, true);
    }

    // 3. Banca dell'azienda con conto corrispondente al tipo di pagamento (qualsiasi)
    if (empty($id_banca)) {
        $id_banca = getBancaByPagamento($database, $azienda->id, $id_pagamento, $conto, false);
    }

    // 4. Fallback: banca predefinita dell'azienda
    if (empty($id_banca)) {
        $banca_predefinita = Banca::where('id_anagrafica', $azienda->id)
            ->where('predefined', 1)
            ->whereNull('deleted_at')
            ->first();
        $id_banca = $banca_predefinita?->id;
    }

    return $id_banca;
}

/**
 * Cerca una banca dell'azienda associata al tipo di pagamento.
 *
 * @param object $database Database object
 * @param int $id_anagrafica ID dell'anagrafica
 * @param int $id_pagamento ID del tipo di pagamento
 * @param string $conto Tipo di conto (vendite/acquisti)
 * @param bool $solo_predefinita Se true, cerca solo banche predefinite
 *
 * @return int|null ID della banca trovata
 */
function getBancaByPagamento($database, $id_anagrafica, $id_pagamento, $conto, $solo_predefinita)
{
    $where_predefined = $solo_predefinita ? 'AND `predefined`=1' : '';

    $query = "SELECT `id` FROM `co_banche`
              WHERE `deleted_at` IS NULL
              {$where_predefined}
              AND `id_pianodeiconti3` = (SELECT idconto_{$conto} FROM `co_pagamenti` WHERE `id` = :id_pagamento)
              AND `id_anagrafica` = :id_anagrafica";

    $result = $database->fetchOne($query, [
        ':id_pagamento' => $id_pagamento,
        ':id_anagrafica' => $id_anagrafica,
    ]);

    return $result['id'] ?? null;
}

/**
 * Pulisce i riferimenti a banche inesistenti o eliminate dall'anagrafica.
 *
 * @param \Modules\Anagrafiche\Anagrafica $anagrafica Anagrafica da pulire
 *
 * @return void
 */
function cleanInvalidBankReferences($anagrafica)
{
    $changed = false;

    // Verifica idbanca_vendite
    if ($anagrafica->idbanca_vendite) {
        $banca = Banca::find($anagrafica->idbanca_vendite);
        if (!$banca || $banca->deleted_at) {
            $anagrafica->idbanca_vendite = null;
            $changed = true;
        }
    }

    // Verifica idbanca_acquisti
    if ($anagrafica->idbanca_acquisti) {
        $banca = Banca::find($anagrafica->idbanca_acquisti);
        if (!$banca || $banca->deleted_at) {
            $anagrafica->idbanca_acquisti = null;
            $changed = true;
        }
    }

    // Salva le modifiche se necessario
    if ($changed) {
        $anagrafica->save();
    }
}

/**
 * Calcola il prossimo numero progressivo per un documento.
 *
 * Funzione generica per il calcolo del numero progressivo utilizzata da vari moduli
 * (Contratti, Ordini, Interventi, DDT, Fatture, Preventivi, Anagrafiche).
 *
 * @param string $table Nome della tabella del database
 * @param string $field Nome del campo da calcolare (numero, numero_esterno, codice)
 * @param string $data Data del documento
 * @param int $id_segment ID del segmento
 * @param array $options Opzioni aggiuntive:
 *   - 'data_field': nome del campo data (default 'data')
 *   - 'direction': direzione del documento (entrata/uscita)
 *   - 'skip_direction': direzione da saltare (ritorna stringa vuota)
 *   - 'type_document': tipo di documento per condizioni extra
 *   - 'type_document_field': campo del tipo documento (es. idtipoddt, idtipoordine)
 *   - 'type_document_table': tabella del tipo documento
 *   - 'conditions_extra': array di condizioni extra SQL
 *   - 'use_setting': usa setting() invece di Generator::getMaschera() (default false)
 *   - 'setting_key': chiave del setting per la maschera (se use_setting = true)
 *   - 'date_pattern': pattern della data per Generator::generate()
 *   - 'use_date_pattern': usa Generator::dateToPattern() per il pattern data
 *
 * @return string Il prossimo numero progressivo
 */
function getNextNumeroProgressivo($table, $field, $data, $id_segment, $options = [])
{
    // Opzioni di default
    $defaults = [
        'data_field' => 'data',
        'direction' => null,
        'skip_direction' => null,
        'type_document' => null,
        'type_document_field' => null,
        'type_document_table' => null,
        'conditions_extra' => [],
        'use_setting' => false,
        'setting_key' => null,
        'date_pattern' => null,
        'use_date_pattern' => false,
    ];

    $options = array_merge($defaults, $options);

    // Se la direzione corrisponde a quella da saltare, ritorna stringa vuota
    if ($options['skip_direction'] && $options['direction'] == $options['skip_direction']) {
        return '';
    }

    // Ottieni la maschera
    if ($options['use_setting']) {
        $maschera = setting($options['setting_key']);
    } else {
        $maschera = Generator::getMaschera($id_segment);
    }

    // Calcola le condizioni in base alla maschera
    $has_month = str_contains($maschera, 'm');
    $has_year = str_contains($maschera, 'YYYY') || str_contains($maschera, 'yy');

    // Costruisci le condizioni
    $conditions = [];

    // Condizione per anno (solo se data è specificata)
    if ($has_year && $data !== null) {
        $data_timestamp = strtotime((string) $data);
        $conditions[] = 'YEAR('.$options['data_field'].') = '.prepare(date('Y', $data_timestamp));
    }

    // Condizione per mese (solo se data è specificata)
    if ($has_month && $data !== null) {
        $data_timestamp = strtotime((string) $data);
        $conditions[] = 'MONTH('.$options['data_field'].') = '.prepare(date('m', $data_timestamp));
    }

    // Condizione per segmento (se specificato)
    if (!empty($id_segment)) {
        $conditions[] = 'id_segment = '.prepare($id_segment);
    }

    // Condizione per direzione/tipo documento
    if ($options['direction'] && $options['type_document_field'] && $options['type_document_table']) {
        $conditions[] = $options['type_document_field'].' IN (SELECT `id` FROM `'.$options['type_document_table'].'` WHERE `dir` = '.prepare($options['direction']).')';
    }

    // Aggiungi condizioni extra
    if (!empty($options['conditions_extra'])) {
        $conditions = array_merge($conditions, $options['conditions_extra']);
    }

    // Ottieni l'ultimo numero
    $ultimo = !empty($conditions)
        ? Generator::getPreviousFrom($maschera, $table, $field, $conditions, $data)
        : Generator::getPreviousFrom($maschera, $table, $field, null, $data);

    // Genera il nuovo numero
    $date_pattern = ($options['use_date_pattern'] && $data !== null) ? Generator::dateToPattern($data) : ($options['date_pattern'] ?? []);
    $numero = Generator::generate($maschera, $ultimo, 1, $date_pattern, $data);

    return $numero;
}

/**
 * Calcola il prossimo numero secondario progressivo per un documento.
 *
 * Funzione generica per il calcolo del numero secondario progressivo utilizzata da vari moduli
 * (Ordini, DDT, Fatture). Il numero secondario viene solitamente utilizzato per
 * i documenti di vendita (entrata).
 *
 * @param string $table Nome della tabella del database
 * @param string $field Nome del campo da calcolare (numero_esterno)
 * @param string $data Data del documento
 * @param int $id_segment ID del segmento
 * @param array $options Opzioni aggiuntive:
 *   - 'data_field': nome del campo data (default 'data')
 *   - 'direction': direzione del documento (entrata/uscita)
 *   - 'skip_direction': direzione da saltare (ritorna stringa vuota, default 'uscita')
 *   - 'type_document': tipo di documento per condizioni extra
 *   - 'type_document_field': campo del tipo documento (es. idtipoddt, idtipoordine)
 *   - 'type_document_table': tabella del tipo documento
 *   - 'conditions_extra': array di condizioni extra SQL
 *   - 'date_pattern': pattern della data per Generator::generate()
 *   - 'use_date_pattern': usa Generator::dateToPattern() per il pattern data
 *
 * @return string Il prossimo numero secondario progressivo
 */
function getNextNumeroSecondarioProgressivo($table, $field, $data, $id_segment, $options = [])
{
    // Opzioni di default
    $defaults = [
        'data_field' => 'data',
        'direction' => null,
        'skip_direction' => 'uscita',
        'type_document' => null,
        'type_document_field' => null,
        'type_document_table' => null,
        'conditions_extra' => [],
        'date_pattern' => null,
        'use_date_pattern' => true,
    ];

    $options = array_merge($defaults, $options);

    return getNextNumeroProgressivo($table, $field, $data, $id_segment, $options);
}
