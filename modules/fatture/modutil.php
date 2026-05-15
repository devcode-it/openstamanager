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

use Modules\Fatture\Fattura;
use Modules\Interventi\Intervento;
use Modules\Iva\Aliquota;
use Util\Generator;

/*
 * Funzione per generare un nuovo numero per la fattura.
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_new_numerofattura')) {
    function get_new_numerofattura($data)
    {
        global $dir;
        global $id_segment;

        return Fattura::getNextNumero($data, $dir, $id_segment);
    }
}

/*
 * Funzione per calcolare il numero secondario successivo utilizzando la maschera dalle impostazioni.
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_new_numerosecondariofattura')) {
    function get_new_numerosecondariofattura($data)
    {
        global $dir;
        global $id_segment;

        return Fattura::getNextNumeroSecondario($data, $dir, $id_segment);
    }
}

/*
 * Calcolo imponibile fattura (totale_righe - sconto).
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_imponibile_fattura')) {
    function get_imponibile_fattura($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        return $fattura->imponibile;
    }
}

/*
 * Calcolo totale fattura (imponibile + iva).
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_totale_fattura')) {
    function get_totale_fattura($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        return $fattura->totale;
    }
}
/*
 * Calcolo netto a pagare fattura (totale - ritenute - bolli).
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_netto_fattura')) {
    function get_netto_fattura($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        return $fattura->netto;
    }
}

/*
 * Calcolo iva detraibile fattura.
 *
 * @deprecated 2.4.5
 */
if (!function_exists('get_ivadetraibile_fattura')) {
    function get_ivadetraibile_fattura($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        return $fattura->iva_detraibile;
    }
}

/*
 * Calcolo iva indetraibile fattura.
 *
 * @deprecated 2.4.5
 */

if (!function_exists('get_ivaindetraibile_fattura')) {
    function get_ivaindetraibile_fattura($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        return $fattura->iva_indetraibile;
    }
}

/*
 * Elimina una scadenza in base al codice documento.
 *
 * @deprecated 2.4.17
 */
if (!function_exists('elimina_scadenze')) {
    function elimina_scadenze($id_documento)
    {
        $fattura = Fattura::find($id_documento);

        $fattura->rimuoviScadenze();
    }
}
/*
 * Funzione per ricalcolare lo scadenzario di una determinata fattura
 * $id_documento	string		E' l'id del documento di cui ricalcolare lo scadenzario
 * $pagamento		string		Nome del tipo di pagamento. Se è vuoto lo leggo da co_pagamenti_documenti, perché significa che devo solo aggiornare gli importi.
 * $pagato boolean Indica se devo segnare l'importo come pagato.
 *
 * @deprecated 2.4.17
 */
if (!function_exists('aggiungi_scadenza')) {
    function aggiungi_scadenza($id_documento, $pagamento = '', $pagato = false)
    {
        $fattura = Fattura::find($id_documento);

        $fattura->registraScadenze($pagato);
    }
}

/*
 * Elimina i movimenti collegati ad una fattura.
 * Se il flag $prima_nota è impostato a 1 elimina solo i movimenti di Prima Nota, altrimenti rimuove quelli automatici.
 *
 * @param $id_documento
 * @param int $prima_nota
 *
 * @deprecated 2.4.17
 */

if (!function_exists('elimina_movimenti')) {
    function elimina_movimenti($id_documento, $prima_nota = 0)
    {
        $dbo = database();

        $id_mastrino = $dbo->fetchOne('SELECT id_mastrino FROM co_movimenti WHERE id_documento='.prepare($id_documento).' AND prima_nota='.prepare($prima_nota))['id_mastrino'];

        $dbo->delete('co_movimenti', ['id_mastrino' => $id_mastrino, 'prima_nota' => $prima_nota]);
    }
}

/*
 * Funzione per aggiungere la fattura in prima nota
 * $id_documento	string		E' l'id del documento da collegare alla prima nota
 * $dir			string		Direzione dell'importo (entrata, uscita)
 * $prima_nota		boolean		Indica se il movimento è un movimento di prima nota o un movimento normale (di default movimento normale).
 *
 * @deprecated 2.4.17
 */
if (!function_exists('aggiungi_movimento')) {
    function aggiungi_movimento($id_documento, $dir, $prima_nota = 0)
    {
        $dbo = database();

        $fattura = Fattura::find($id_documento);
        $is_nota = $fattura->isNota();

        // Ottimizzazione: usa il modello Eloquent invece di query SQL diretta
        $totale_bolli = $is_nota ? -$fattura->bollo : $fattura->bollo;
        $totale_ritenuta_acconto = $is_nota ? -$fattura->ritenuta_acconto : $fattura->ritenuta_acconto;
        $totale_ritenutacontributi = $is_nota ? -$fattura->totale_ritenuta_contributi : $fattura->totale_ritenuta_contributi;
        $totale_rivalsa_inps = $is_nota ? -$fattura->rivalsa_inps : $fattura->rivalsa_inps;
        $data_documento = $fattura->data;
        $split_payment = $fattura->split_payment;

        $netto_fattura = get_netto_fattura($id_documento);
        $totale_fattura = get_totale_fattura($id_documento);
        $totale_fattura = $is_nota ? -$totale_fattura : $totale_fattura;

        $imponibile_fattura = get_imponibile_fattura($id_documento);

        // Calcolo l'iva della rivalsa inps
        $iva_rivalsa_inps = 0;

        $rsr = $dbo->fetchArray('SELECT `id_iva`, `rivalsa_inps` FROM `co_righe_documenti` WHERE `id_documento`='.prepare($id_documento));

        for ($r = 0; $r < sizeof($rsr); ++$r) {
            $qi = Aliquota::find(prepare($rsr[$r]['id_iva']))->percentuale;
            $rsi = $dbo->fetchArray($qi);
            $iva_rivalsa_inps += $rsr[$r]['rivalsa_inps'] / 100 * $rsi[0]['percentuale'];
        }

        // Lettura iva indetraibile fattura
        $query = 'SELECT SUM(`iva_indetraibile`) AS iva_indetraibile FROM `co_righe_documenti` GROUP BY `id_documento` HAVING `id_documento`='.prepare($id_documento);
        $rs = $dbo->fetchArray($query);
        $iva_indetraibile_fattura = $is_nota ? -$rs[0]['iva_indetraibile'] : $rs[0]['iva_indetraibile'];

        // Lettura iva delle righe in fattura
        $query = 'SELECT `iva` FROM `co_righe_documenti` WHERE `id_documento`='.prepare($id_documento);
        $rs = $dbo->fetchArray($query);
        $iva_fattura = sum(array_column($rs, 'iva'), null) + $iva_rivalsa_inps - $iva_indetraibile_fattura;
        $iva_fattura = $is_nota ? -$iva_fattura : $iva_fattura;

        // Imposto i segni + e - in base se la fattura è di acquisto o vendita
        if ($dir == 'uscita') {
            $segno_mov1_cliente = -1;
            $segno_mov2_ricavivendite = 1;
            $segno_mov3_iva = 1;

            $segno_mov4_inps = 1;
            $segno_mov5_ritenuta_acconto = -1;

            // Lettura conto fornitore
            $query = 'SELECT id_conto_fornitore FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.id=co_documenti.id_anagrafica WHERE co_documenti.id='.prepare($id_documento);
            $rs = $dbo->fetchArray($query);
            $id_conto_controparte = $rs[0]['id_conto_fornitore'];

            if ($id_conto_controparte == '') {
                $id_conto_controparte = setting('Conto per Riepilogativo fornitori');
            }
        } else {
            $segno_mov1_cliente = 1;
            $segno_mov2_ricavivendite = -1;
            $segno_mov3_iva = -1;

            $segno_mov4_inps = -1;
            $segno_mov5_ritenuta_acconto = 1;

            // Lettura conto cliente
            $query = 'SELECT id_conto_cliente FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.id=co_documenti.id_anagrafica WHERE co_documenti.id='.prepare($id_documento);
            $rs = $dbo->fetchArray($query);
            $id_conto_controparte = $rs[0]['id_conto_cliente'];

            if ($id_conto_controparte == '') {
                $id_conto_controparte = setting('Conto per Riepilogativo clienti');
            }
        }

        // Lettura info fattura
        $query = 'SELECT *, `co_documenti`.`data_competenza`, `co_documenti`.`note`, `co_documenti`.`id_pagamento`, `co_documenti`.`id` AS id_documento, `co_stati_documento_lang`.`title` AS `stato`, `co_tipidocumento_lang`.`title` AS descrizione_tipo FROM `co_documenti` INNER JOIN `co_stati_documento` ON `co_documenti`.`id_stato`=`co_stati_documento`.`id` INNER JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica`=`an_anagrafiche`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_documenti`.`id`='.prepare($id_documento);

        $rs = $dbo->fetchArray($query);
        $data = $rs[0]['data_competenza'];
        $ragione_sociale = $rs[0]['ragione_sociale'];

        $id_mastrino = get_new_id_mastrino();

        // Prendo il numero doc. esterno se c'è, altrimenti quello normale
        if (!empty($rs[0]['numero_esterno'])) {
            $numero = $rs[0]['numero_esterno'];
        } else {
            $numero = $rs[0]['numero'];
        }

        // Abbreviazioni contabili dei movimenti
        $tipodoc = '';
        if ($rs[0]['descrizione_tipo'] == 'Nota di credito') {
            $tipodoc = 'Nota di credito';
        } elseif ($rs[0]['descrizione_tipo'] == 'Nota di debito') {
            $tipodoc = 'Nota di debito';
        } else {
            $tipodoc = 'Fattura';
        }

        $descrizione = $tipodoc.' num. '.$numero;

        /*
            Il mastrino si apre con almeno 3 righe di solito (esempio fattura di vendita):
            1) dare imponibile+iva al conto cliente
            2) avere imponibile sul conto dei ricavi
            3) avere iva sul conto dell'iva a credito (ed eventuale iva indetraibile sul rispettivo conto)

            aggiuntivo:
            4) eventuale rivalsa inps
            5) eventuale ritenuta d'acconto
        */
        // 1) Aggiungo la riga del conto cliente
        $importo_cliente = $totale_fattura;

        if ($split_payment) {
            $importo_cliente = sum($importo_cliente, -$iva_fattura, 2);
        }

        $query2 = 'INSERT INTO co_movimenti(id_mastrino, data, id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
        $params = [
            ':id_mastrino' => $id_mastrino,
            ':data' => $data,
            ':id_documento' => $id_documento,
            ':id_anagrafica' => '',
            ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
            ':id_conto' => $id_conto_controparte,
            ':totale' => ($importo_cliente + $totale_bolli) * $segno_mov1_cliente,
            ':prima_nota' => $prima_nota,
        ];
        $dbo->query($query2, $params);

        // 2) Aggiungo il totale sul conto dei ricavi/spese scelto
        // Lettura descrizione conto ricavi/spese per ogni riga del documento
        $righe = $dbo->fetchArray('SELECT id_conto, SUM(subtotale - sconto) AS imponibile FROM co_righe_documenti WHERE id_documento='.prepare($id_documento).' GROUP BY id_conto');

        foreach ($righe as $riga) {
            // Retrocompatibilità
            $id_conto_riga = $riga['id_conto'];
            $riga['imponibile'] = $is_nota ? -$riga['imponibile'] : $riga['imponibile'];

            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data, id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_riga,
                ':totale' => $riga['imponibile'] * $segno_mov2_ricavivendite,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }

        // 3) Aggiungo il totale sul conto dell'iva
        // Lettura id conto iva
        if ($iva_fattura != 0 && !$split_payment) {
            $descrizione_conto_iva = ($dir == 'entrata') ? 'Iva su vendite' : 'Iva su acquisti';
            $id_conto_iva = setting('Conto per '.$descrizione_conto_iva);

            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data, id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_iva,
                ':totale' => $iva_fattura * $segno_mov3_iva,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }

        // Lettura id conto iva indetraibile
        if ($iva_indetraibile_fattura != 0 && !$split_payment) {
            $id_conto_iva2 = setting('Conto per Iva indetraibile');

            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_iva2,
                ':totale' => $iva_indetraibile_fattura * $segno_mov3_iva,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }

        // 4) Aggiungo la rivalsa INPS se c'è
        // Lettura id conto inps
        if ($totale_rivalsa_inps != 0) {
            $id_conto_inps = setting('Conto per Erario c/INPS');

            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_inps,
                ':totale' => $totale_rivalsa_inps * $segno_mov4_inps,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }

        // 5) Aggiungo la ritenuta d'acconto se c'è
        // Lettura id conto ritenuta e la storno subito
        if ($totale_ritenuta_acconto != 0) {
            $id_conto_ritenuta_acconto = setting("Conto per Erario c/ritenute d'acconto");

            // DARE nel conto ritenuta
            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_ritenuta_acconto,
                ':totale' => $totale_ritenuta_acconto * $segno_mov5_ritenuta_acconto,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);

            // AVERE nel riepilogativo clienti
            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_controparte,
                ':totale' => ($totale_ritenuta_acconto * $segno_mov5_ritenuta_acconto) * -1,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }

        // 6) Aggiungo la ritenuta enasarco se c'è
        // Lettura id conto ritenuta e la storno subito
        if ($totale_ritenutacontributi != 0) {
            $id_conto_ritenutaenasarco = setting('Conto per Erario c/enasarco');

            // DARE nel conto ritenuta
            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_ritenutaenasarco,
                ':totale' => $totale_ritenutacontributi * $segno_mov5_ritenuta_acconto,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);

            // AVERE nel riepilogativo clienti
            $query2 = 'INSERT INTO co_movimenti(id_mastrino, data,  id_documento, id_anagrafica, descrizione, id_conto, totale, prima_nota) VALUES(:id_mastrino, :data, :id_documento, :id_anagrafica, :descrizione, :id_conto, :totale, :prima_nota)';
            $params = [
                ':id_mastrino' => $id_mastrino,
                ':data' => $data,
                ':id_documento' => $id_documento,
                ':id_anagrafica' => '',
                ':descrizione' => $descrizione.' del '.date('d/m/Y', strtotime((string) $data)).' ('.$ragione_sociale.')',
                ':id_conto' => $id_conto_controparte,
                ':totale' => ($totale_ritenutacontributi * $segno_mov5_ritenuta_acconto) * -1,
                ':prima_nota' => $prima_nota,
            ];
            $dbo->query($query2, $params);
        }
    }
}
/*
 * Funzione per generare un nuovo codice per il mastrino.
 *
 * @deprecated 2.4.17
 */
if (!function_exists('get_new_id_mastrino')) {
    function get_new_id_mastrino($table = 'co_movimenti')
    {
        $dbo = database();

        $maxid_mastrino = $dbo->table($table)->max('id_mastrino');

        return intval($maxid_mastrino) + 1;
    }
}

/*
 * Ricalcola i costi aggiuntivi in fattura (rivalsa inps, ritenuta d'acconto, marca da bollo)
 * Deve essere eseguito ogni volta che si aggiunge o toglie una riga
 * $id_documento		int		ID della fattura.
 *
 * @deprecated 2.4.17
 */

if (!function_exists('ricalcola_costiagg_fattura')) {
    function ricalcola_costiagg_fattura($id_documento)
    {
        global $dir;

        $fattura = Fattura::find($id_documento);
        $fattura->save();
    }
}

/*
 * Verifica che il numero_esterno della fattura indicata sia correttamente impostato, a partire dai valori delle fatture ai giorni precedenti.
 * Restituisce il numero_esterno mancante in caso di numero errato.
 *
 * @return bool|string
 */
if (!function_exists('verifica_numero_fattura')) {
    function verifica_numero_fattura(Fattura $fattura)
    {
        if (empty($fattura->numero_esterno)) {
            return null;
        }

        $id_segment = $fattura->id_segment;
        $data = $fattura->data;

        $documenti = Fattura::where('id_segment', '=', $id_segment)
            ->where('data', '=', $data)
            ->get();

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'co_documenti', 'numero_esterno', [
            'data < '.prepare(date('Y-m-d', strtotime($data))),
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
            'id_segment = '.prepare($id_segment),
        ], $data);

        do {
            $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

            $filtered = $documenti->reject(fn ($item, $key) => $item->numero_esterno == $numero);

            if ($documenti->count() == $filtered->count()) {
                return $numero;
            }

            $documenti = $filtered;
            $ultimo = $numero;
        } while ($numero != $fattura->numero_esterno);

        return null;
    }

    function get_righe_composte(Fattura $documento)
    {
        global $dbo;

        $righe = [];

        // Righe documento
        $righe_documento = $documento->getRighe()->where('id_intervento', '!=', null)->groupBy(fn ($item, $key) => $item['prezzo_unitario'].'|'.$item['id_iva'].'|'.$item['sconto_unitario']);

        if (setting('Raggruppa attività per tipologia in fattura') && !$righe_documento->isEmpty()) {
            $articoli = [];
            foreach ($righe_documento as $gruppo) {
                $riga_base = [];
                foreach ($gruppo as $riga) {
                    $intervento = Intervento::find($riga->id_intervento);

                    if (!empty($intervento)) {
                        if ($riga['is_descrizione'] == 1) {
                            if (empty($riga_base[$intervento->id_tipo_intervento]['descrizione'])) {
                                $riga_base[$intervento->id_tipo_intervento]['descrizione'] = $riga;
                            } else {
                                $riga_base[$intervento->id_tipo_intervento]['descrizione']['descrizione'] .= "\n".$riga->descrizione;
                                $riga_base[$intervento->id_tipo_intervento]['descrizione']['qta'] += $riga->qta;
                            }
                        }

                        if ($riga['is_descrizione'] == 0) {
                            if (empty($riga_base[$intervento->id_tipo_intervento]['riga']) && empty($riga->id_articolo)) {
                                $riga_base[$intervento->id_tipo_intervento]['riga'] = $riga;
                            } elseif (empty($riga->id_articolo)) {
                                $riga_base[$intervento->id_tipo_intervento]['riga']['descrizione'] .= "\n".$riga->descrizione;
                                $riga_base[$intervento->id_tipo_intervento]['riga']['qta'] += $riga->qta;
                            } else {
                                $riga_base[$intervento->id_tipo_intervento]['articoli'][] = $riga;
                            }
                        }
                    } else {
                        $articoli[] = $riga;
                    }
                }

                foreach ($riga_base as $riga) {
                    if (!empty($riga['descrizione'])) {
                        $righe[] = $riga['descrizione'];
                    }

                    if (!empty($riga['riga'])) {
                        $righe[] = $riga['riga'];
                    }

                    if (!empty($riga['articoli'])) {
                        foreach ($riga['articoli'] as $articolo) {
                            $righe[] = $articolo;
                        }
                    }
                }

                if (!empty($articoli)) {
                    $righe = array_merge($righe, $articoli);
                }
            }

            // Estraggo le righe non collegate a interventi
            $righe_esterne = $documento->getRighe()->where('id_intervento', '=', null);
            foreach ($righe_esterne as $riga) {
                $righe[] = $riga;
            }
        } else {
            $righe = $documento->getRighe();
        }

        for ($index = 0; $index < count($righe); ++$index) {
            if (empty($righe[$index])) {
                unset($righe[$index]);
            }
        }

        $righe = collect($righe);

        return $righe;
    }
}
