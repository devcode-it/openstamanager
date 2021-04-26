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

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Components\Articolo;
use Modules\Contratti\Components\Riga;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/../modutil.php';

$operazione = filter('op');

// Pianificazione fatturazione
switch ($operazione) {
    case 'add':
        $contratto = Contratto::find($id_record);

        if (post('scadenza') == 'Mensile') {
            $timeing = '+1 month';
        }
        if (post('scadenza') == 'Bimestrale') {
            $timeing = '+2 month';
        }
        if (post('scadenza') == 'Trimestrale') {
            $timeing = '+3 month';
        }
        if (post('scadenza') == 'Quadrimestrale') {
            $timeing = '+4 month';
        }
        if (post('scadenza') == 'Semestrale') {
            $timeing = '+6 month';
        }
        if (post('scadenza') == 'Annuale') {
            $timeing = '+12 month';
        }

        $selezioni = collect(post('selezione_periodo'));
        $periodi = post('periodo');

        $numero_fatture = 0;
        $date_pianificazioni = [];
        $pianificazioni = [];
        foreach ($selezioni as $key => $selezione) {
            $date = new DateTime($periodi[$key]);

            if (post('cadenza_fatturazione') == 'Inizio') {
                $date->modify('first day of this month');
            } elseif (post('cadenza_fatturazione') == 'Giorno' && !empty(post('giorno_fisso'))) {
                $date->modify('last day of this month');
                $last_day = $date->format('d');
                $day = post('giorno_fisso') > $last_day ? $last_day : post('giorno_fisso');

                // Correzione data
                $date->setDate($date->format('Y'), $date->format('m'), $day);
            }

            // Comversione della data in stringa standard
            $data_scadenza = $date->format('Y-m-d');

            ++$numero_fatture;

            // Creazione pianificazione
            $pianificazione = Pianificazione::build($contratto, $data_scadenza);
            $date_pianificazioni[] = $data_scadenza;
            $pianificazioni[$numero_fatture] = $pianificazione->id;
        }

        if ($numero_fatture > 0) {
            $righe_contratto = $contratto->getRighe();
            $subtotale = [];

            // Creazione nuove righe
            $qta = post('qta');
            foreach ($righe_contratto as $r) {
                $qta_evasa = $r->qta_evasa;
                $data_scadenza = '';
                $inizio = $date_pianificazioni[0];
                $fine = date('Y-m-d', strtotime($inizio.' -1 days'));
                $fine = date('Y-m-d', strtotime($fine.' '.$timeing));
                for ($rata = 1; $rata <= $numero_fatture; ++$rata) {
                    if ($qta_evasa < $r->qta) {
                        $qta_riga = ($qta[$r->id] <= ($r->qta - $qta_evasa) ? $qta[$r->id] : ($r->qta - $qta_evasa));
                        $descrizione = post('descrizione')[$r->id];

                        $descrizione = variables($descrizione, $inizio, $fine)['descrizione'];

                        $inizio = $fine;
                        $fine = date('Y-m-d', strtotime($timeing, strtotime($inizio)));
                        $inizio = date('Y-m-d', strtotime($inizio.' +1 days'));

                        $prezzo_unitario = ($r->subtotale / $r->qta);

                        if (!empty($r->idarticolo)) {
                            $articolo = ArticoloOriginale::find($r->idarticolo);
                            $riga = Articolo::build($contratto, $articolo);
                        } else {
                            $riga = Riga::build($contratto);
                        }

                        $riga->descrizione = $descrizione;
                        $riga->setPrezzoUnitario($prezzo_unitario, $r->idiva);
                        $riga->qta = $qta_riga;
                        $riga->idpianificazione = $pianificazioni[$rata];

                        $riga->save();

                        $qta_evasa += $qta_riga;
                        $pianificata[] = $pianificazioni[$rata];
                    } else {
                        $non_pianificata[] = $pianificazioni[$rata];
                    }
                }
                $r->delete();
            }
            $tot_non_pianificati = implode(', ', array_unique(array_diff($non_pianificata, $pianificata)));
            if (!empty($tot_non_pianificati)) {
                $dbo->query('DELETE FROM `co_fatturazione_contratti` WHERE `id` IN ('.$tot_non_pianificati.')');
            }
        }

        break;

    case 'reset':
        $dbo->query('DELETE FROM `co_fatturazione_contratti` WHERE `idcontratto`='.prepare($id_record));
        flash()->info(tr('Pianificazione rimossa'));

        break;

    case 'add_fattura':
        $id_rata = post('rata');
        $accodare = post('accodare');
        $pianificazione = Pianificazione::find($id_rata);
        $contratto = $pianificazione->contratto;

        $data = post('data');
        $id_segment = post('id_segment');
        $tipo = Tipo::find(post('idtipodocumento'));

        if (!empty($accodare)) {
            $documento = $dbo->fetchOne('SELECT co_documenti.id FROM co_documenti INNER JOIN co_statidocumento ON co_documenti.idstatodocumento = co_statidocumento.id WHERE co_statidocumento.descrizione = \'Bozza\' AND idanagrafica = '.prepare($contratto->idanagrafica));

            $id_documento = $documento['id'];
        }

        // Creazione fattura
        if (empty($id_documento)) {
            $fattura = Fattura::build($contratto->anagrafica, $tipo, $data, $id_segment);
        } else {
            $fattura = Fattura::find($id_documento);
        }
        $fattura->note = post('note');
        $fattura->save();

        $id_conto = post('id_conto');

        // Copia righe
        $righe = $pianificazione->getRighe();
        foreach ($righe as $riga) {
            $copia = $riga->copiaIn($fattura, $riga->qta);
            $copia->id_conto = $id_conto;
            $copia->save();
        }

        // Salvataggio fattura nella pianificazione
        $pianificazione->fattura()->associate($fattura);
        $pianificazione->save();

        break;
}
