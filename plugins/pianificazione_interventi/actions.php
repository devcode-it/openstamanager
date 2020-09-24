<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Contratto;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\PianificazioneInterventi\Components\Articolo;
use Plugins\PianificazioneInterventi\Components\Riga;
use Plugins\PianificazioneInterventi\Promemoria;

$operazione = filter('op');

// Pianificazione intervento
switch ($operazione) {
    case 'add-promemoria':
        $contratto = Contratto::find($id_parent);
        $tipo = TipoSessione::find(filter('idtipointervento'));

        $promemoria = Promemoria::build($contratto, $tipo, filter('data_richiesta'));
        echo $promemoria->id;

        break;

    case 'edit-promemoria':
        $dbo->update('co_promemoria', [
            'data_richiesta' => post('data_richiesta'),
            'idtipointervento' => post('idtipointervento'),
            'richiesta' => post('richiesta'),
            'idimpianti' => implode(',', post('idimpianti')),
            'idsede' => implode(',', post('idsede_c')),
        ], ['id' => $id_record]);

        flash()->info(tr('Promemoria inserito!'));

        break;

    // Eliminazione pianificazione
    case 'delete-promemoria':
        $id = post('id');

        $dbo->query('DELETE FROM `co_promemoria` WHERE id='.prepare($id));
        $dbo->query('DELETE FROM `co_righe_promemoria` WHERE id_promemoria='.prepare($id));

        flash()->info(tr('Pianificazione eliminata!'));

        break;

    // Eliminazione tutti i promemoria di questo contratto con non hanno l'intervento associato
    case 'delete-non-associati':
        $dbo->query('DELETE FROM `co_righe_promemoria` WHERE id_promemoria IN (SELECT id FROM `co_promemoria` WHERE idcontratto = :id_contratto AND idintervento IS NULL)', [
            ':id_contratto' => $id_record,
        ]);

        $dbo->query('DELETE FROM `co_promemoria` WHERE idcontratto = :id_contratto AND idintervento IS NULL', [
            ':id_contratto' => $id_record,
        ]);

        flash()->info(tr('Tutti i promemoria non associati sono stati eliminati!'));

        break;

    // Pianificazione ciclica
    case 'pianificazione':
        $intervallo = post('intervallo');
        $data_inizio = post('data_inizio');

        $count = 0;
        $count_interventi = 0;
        $count_promemoria = 0;

        $date_con_promemoria = [];
        $date_con_intervento = [];
        if (post('pianifica_promemoria')) {
            $promemoria_originale = Promemoria::find($id_record);
            $contratto = $promemoria_originale->contratto;

            // Promemoria del contratto raggruppati per data
            $promemoria_contratto = $contratto->promemoria()
                ->where('idtipointervento', $promemoria_originale->tipo->id)
                ->get()
                ->groupBy(function ($item) {
                    return $item->data_richiesta->toDateString();
                });

            $date_preimpostate = $promemoria_contratto->keys()->toArray();

            $data_conclusione = $contratto->data_conclusione;
            $data_inizio = new Carbon($data_inizio);
            $data_richiesta = $data_inizio->copy();
            $interval = CarbonInterval::make($intervallo.' days');

            $stato = Stato::where('codice', 'WIP')->first(); // Stato "In programmazione"

            // Ciclo partendo dalla data_richiesta fino alla data conclusione del contratto
            while ($data_richiesta->lessThanOrEqualTo($data_conclusione)) {
                // Creazione ciclica del promemoria se non ne esiste uno per la data richiesta
                $data_promemoria = $data_richiesta->format('Y-m-d');
                if (!in_array($data_promemoria, $date_preimpostate)) {
                    $promemoria_corrente = $promemoria_originale->replicate();
                    $promemoria_corrente->data_richiesta = $data_richiesta;
                    $promemoria_corrente->idintervento = null;
                    $promemoria_corrente->save();

                    // Copia delle righe
                    $righe = $promemoria_originale->getRighe();
                    foreach ($righe as $riga) {
                        $copia = $riga->replicate();
                        $copia->setDocument($promemoria_corrente);
                        $copia->save();
                    }

                    // Copia degli allegati
                    $allegati = $promemoria_originale->uploads();
                    foreach ($allegati as $allegato) {
                        $allegato->copia([
                            'id_module' => $allegato->id_module,
                            'id_plugin' => $allegato->id_plugin,
                            'id_record' => $promemoria_corrente->id,
                        ]);
                    }

                    ++$count_promemoria;
                } else {
                    $promemoria_corrente = $promemoria_contratto[$data_promemoria]->first();
                    $date_con_promemoria[] = dateFormat($data_promemoria);
                }

                // Creazione intervento collegato se non presente
                if (post('pianifica_intervento') && empty($promemoria->intervento)) {
                    // Creazione intervento
                    $intervento = Intervento::build($contratto->anagrafica, $promemoria_originale->tipo, $stato, $data_richiesta);
                    $intervento->idsede_destinazione = $promemoria_corrente->idsede ?: 0;
                    $intervento->richiesta = $promemoria_corrente->richiesta;
                    $intervento->idclientefinale = post('idclientefinale') ?: 0;
                    $intervento->save();

                    // Aggiungo i tecnici selezionati
                    $idtecnici = post('idtecnico');
                    foreach ($idtecnici as $idtecnico) {
                        add_tecnico($intervento->id, $idtecnico, $data_promemoria.' '.post('orario_inizio'), $data_promemoria.' '.post('orario_fine'));
                    }

                    // Copia delle informazioni del promemoria
                    $promemoria_corrente->pianifica($intervento);

                    ++$count_interventi;
                } elseif (post('pianifica_intervento')) {
                    $date_con_intervento[] = dateFormat($data_promemoria);
                }

                // Calcolo nuova data richiesta, non considero l'intervallo al primo ciclo
                $data_richiesta = $data_richiesta->add($interval);
                ++$count;
            }
        }

        if ($count == 0) {
            flash()->warning(tr('Nessun promemoria pianificato'));
        } else {
            flash()->info(tr('Sono stati creati _NUM_ promemoria!', [
                '_NUM_' => $count_promemoria,
            ]));

            if (!empty($date_con_promemoria)) {
                flash()->warning(tr('Le seguenti date presentano già un promemoria pianificato: _LIST_', [
                    '_LIST_' => implode(', ', $date_con_promemoria),
                ]));
            }

            if (post('pianifica_intervento')) {
                flash()->info(tr('Sono stati pianificati _NUM_ interventi!', [
                    '_NUM_' => $count_interventi,
                ]));

                if (!empty($date_con_intervento)) {
                    flash()->warning(tr('I promemoria delle seguenti date presentano già un intervento collegato: _LIST_', [
                        '_LIST_' => implode(', ', $date_con_intervento),
                    ]));
                }
            }
        }
    break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($promemoria, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));

        try {
            $articolo->qta = $qta;
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($promemoria);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));

        $riga->qta = $qta;

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        break;

    case 'delete_riga':
        $id_riga = post('idriga');
        $type = post('type');
        $riga = $promemoria->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }

        break;
}
