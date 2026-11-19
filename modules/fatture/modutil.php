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
use Util\Generator;

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
