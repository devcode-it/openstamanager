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

use Models\Module;
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
        } elseif (post('scadenza') == 'Bimestrale') {
            $timeing = '+2 month';
        } elseif (post('scadenza') == 'Trimestrale') {
            $timeing = '+3 month';
        } elseif (post('scadenza') == 'Quadrimestrale') {
            $timeing = '+4 month';
        } elseif (post('scadenza') == 'Semestrale') {
            $timeing = '+6 month';
        } elseif (post('scadenza') == 'Annuale') {
            $timeing = '+12 month';
        }

        $selezioni = collect(post('selezione_periodo'));
        $periodi = post('periodo');

        $numero_fatture = 0;
        $date_pianificazioni = [];
        $pianificazioni = [];

        $cadenza_fatturazione = post('cadenza_fatturazione');

        foreach ($selezioni as $key => $selezione) {
            $date = new DateTime($periodi[$key]);

            if ($cadenza_fatturazione == 'Inizio') {
                $date->modify('first day of this month');
            } elseif ($cadenza_fatturazione == 'Giorno' && !empty(post('giorno_fisso'))) {
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
            $pianificata = [];
            $non_pianificata = [];

            // Creazione nuove righe
            $qta = post('qta');
            foreach ($righe_contratto as $r) {
                $qta_evasa = $r->qta_evasa;
                $data_scadenza = '';
                $inizio = $date_pianificazioni[0];

                if ($cadenza_fatturazione == 'Fine') {
                    $inizio = Carbon\Carbon::parse($inizio)->startOfMonth()->format('Y-m-d');
                    $fine = Carbon\Carbon::parse($inizio)->endOfMonth()->format('Y-m-d');
                } else {
                    $fine = date('Y-m-d', strtotime($inizio.' '.$timeing));
                    $fine = date('Y-m-d', strtotime($fine.' -1 days'));
                }
                for ($rata = 1; $rata <= $numero_fatture; ++$rata) {
                    if ($qta_evasa < $r->qta) {
                        $qta_riga = ($qta[$r->id] <= ($r->qta - $qta_evasa) ? $qta[$r->id] : ($r->qta - $qta_evasa));
                        $descrizione = post('descrizione')[$r->id];

                        $descrizione = variables($descrizione, $inizio, $fine, $rata, $numero_fatture)['descrizione'];

                        $inizio = $fine;
                        $inizio = date('Y-m-d', strtotime($inizio.' +1 days'));

                        $fine = date('Y-m-d', strtotime($inizio.' '.$timeing));
                        $fine = date('Y-m-d', strtotime($fine.' -1 days'));
                        if ($cadenza_fatturazione == 'Fine') {
                            $fine = Carbon\Carbon::parse($fine)->endOfMonth()->format('Y-m-d');
                        }
                        $prezzo_unitario = setting('Utilizza prezzi di vendita comprensivi di IVA') ? (($r->subtotale + $r->iva) / ($r->qta ?: 1)) : ($r->subtotale / ($r->qta ?: 1));

                        if (!empty($r->idarticolo)) {
                            $articolo = ArticoloOriginale::find($r->idarticolo);
                            $riga = Articolo::build($contratto, $articolo);
                            $riga->original_id = $r->id;
                        } else {
                            $riga = Riga::build($contratto);
                        }

                        $riga->descrizione = $descrizione;
                        $riga->setPrezzoUnitario($prezzo_unitario, $r->idiva);
                        $riga->costo_unitario = $r->costo_unitario;
                        $riga->setSconto($r->tipo_sconto == 'PRC' ? $r->sconto_percentuale : $r->sconto_unitario, $r->tipo_sconto);
                        $riga->qta = $qta_riga;
                        $riga->setProvvigione($r->provvigione_percentuale ?: $r->provvigione_unitaria, $r->tipo_provvigione);
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
        $dbo->query('UPDATE `co_righe_contratti` SET `idpianificazione`=NULL WHERE `idpianificazione` IS NOT NULL AND `idcontratto`='.prepare($id_record));
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
            $documento = $dbo->fetchOne('SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id` LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_statidocumento_lang`.`title` = \'Bozza\' AND `idanagrafica` = '.prepare($contratto->idanagrafica));

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

        flash()->info(tr('Rata fatturata correttamente!'));
        database()->commitTransaction();
        redirect(base_path().'/controller.php?id_module='.Module::where('name', 'Fatture di vendita')->first()->id.'&id_record='.$fattura->id);
        exit;

    case 'add_fattura_multipla':
        $rate = post('rata');

        $data = post('data');
        $accodare = post('accodare');
        $id_segment = post('id_segment');
        $id_tipodocumento = post('idtipodocumento');
        $tipo = Tipo::find($id_tipodocumento);

        foreach ($rate as $i => $rata) {
            $id_rata = $rata;

            $pianificazione = Pianificazione::find($id_rata);

            $contratto = $pianificazione->contratto;
            if (!empty($accodare)) {
                $documento = $dbo->fetchOne(
                    'SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id` LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_statidocumento_lang`.`title` = \'Bozza\' AND `idanagrafica` = '.prepare($contratto->idanagrafica)
                );

                $id_documento = $documento['id'];
            }

            // Creazione fattura
            if (empty($id_documento)) {
                $fattura = Fattura::build($contratto->anagrafica, $tipo, $data, $id_segment);
            } else {
                $fattura = Fattura::find($id_documento);
            }

            $fattura->note = '';
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
        }

        flash()->info(tr('Rate fatturate correttamente!'));
        database()->commitTransaction();
        redirect(base_path().'/controller.php?id_module='.Module::where('name', 'Fatture di vendita')->first()->id);
        exit;
}
