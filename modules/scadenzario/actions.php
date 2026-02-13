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

include_once __DIR__.'/../../core.php';

use Models\Module;
use Models\PrintTemplate;
use Modules\Fatture\Tipo;
use Modules\Scadenzario\Scadenza;
use Plugins\AssicurazioneCrediti\AssicurazioneCrediti;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $tipo = post('tipo');
        $da_pagare = post('da_pagare');
        $descrizione = strip_tags(post('descrizione'));
        $iddocumento = post('iddocumento') ?: 0;
        $data_emissione = post('data_emissione') ?: date('Y-m-d');

        $dbo->insert('co_scadenziario', [
            'idanagrafica' => $idanagrafica,
            'iddocumento' => $iddocumento,
            'descrizione' => $descrizione,
            'tipo' => $tipo,
            'data_emissione' => $data_emissione,
            'scadenza' => $data,
            'da_pagare' => $da_pagare,
            'pagato' => 0,
        ]);
        $id_record = $dbo->lastInsertedID();

        $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $idanagrafica)->where('data_inizio', '<=', $data)->where('data_fine', '>=', $data)->first();
        if (!empty($assicurazione_crediti)) {
            $assicurazione_crediti->fixTotale();
            $assicurazione_crediti->save();
        }

        flash()->info(tr('Scadenza inserita!'));

        // Restituisce l'ID della scadenza appena creata per le chiamate AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['id_record' => $id_record]);
        }
        break;

    case 'update':
        $idanagrafica = post('idanagrafica');
        $tipo = post('tipo');
        $descrizione = strip_tags(post('descrizione'));
        $iddocumento = post('iddocumento') ?: 0;
        if (!empty($iddocumento)) {
            $scadenze = database()->table('co_scadenziario')->where('iddocumento', '=', $iddocumento)->orderBy('scadenza')->get();
        }
        $totale_pagato = 0;
        $id_scadenza_non_completa = null;
        $da_pagare = 0;

        foreach ($scadenze as $id => $scadenza) {
            $pagato = floatval(post('pagato')[$id]);
            $data_scadenza = post('scadenza')[$id];
            $data_concordata = post('data_concordata')[$id];
            $da_pagare = post('da_pagare')[$id];

            if (!empty($iddocumento)) {
                $tipo_documento = Tipo::find($documento->idtipodocumento);

                if ($tipo_documento['dir'] == 'uscita') {
                    if ($pagato > 0) {
                        $pagato = -$pagato;
                    }
                    if ($da_pagare > 0) {
                        $da_pagare = -$da_pagare;
                    }
                } else {
                    if ($pagato < 0) {
                        $pagato = -$pagato;
                    }
                    if ($da_pagare < 0) {
                        $da_pagare = -$da_pagare;
                    }
                }

                if (!empty($tipo_documento['reversed'])) {
                    $pagato = -$pagato;
                    $da_pagare = -$da_pagare;
                }
            }

            $totale_pagato = sum($totale_pagato, $pagato);
            $id_pagamento = post('id_pagamento')[$id] ?: $documento->idpagamento;
            $id_banca_azienda = post('id_banca_azienda')[$id] ?: $documento->id_banca_azienda;
            $id_banca_controparte = post('id_banca_controparte')[$id] ?: $documento->id_banca_controparte;

            $id_scadenza = $scadenza->id ?? $scadenza['id'];
            if (!empty($id_scadenza)) {
                $database->update('co_scadenziario', [
                    'idanagrafica' => $idanagrafica,
                    'descrizione' => $descrizione,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $data_scadenza,
                    'data_concordata' => $data_concordata ?: null,
                    'id_pagamento' => $id_pagamento,
                    'id_banca_azienda' => $id_banca_azienda,
                    'id_banca_controparte' => $id_banca_controparte,
                    'note' => post('note'),
                    'distinta' => post('distinta') ?: null,
                ], ['id' => $id_scadenza]);

                if ($da_pagare == 0) {
                    $database->delete('co_scadenziario', ['id' => $id_scadenza]);
                }
            } else {
                $database->insert('co_scadenziario', [
                    'idanagrafica' => $idanagrafica,
                    'descrizione' => $descrizione,
                    'tipo' => $tipo,
                    'iddocumento' => $iddocumento,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $data_scadenza,
                    'data_concordata' => $data_concordata,
                    'data_emissione' => date('Y-m-d'),
                    'note' => post('note'),
                ]);

                $id_scadenza = $database->lastInsertedID();
            }

            if ($pagato != $da_pagare) {
                $id_scadenza_non_completa = $id_scadenza;
            }

            $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $idanagrafica)
            ->where('data_inizio', '<=', $data_scadenza)
            ->where('data_fine', '>=', $data_scadenza)
            ->first();

            if (!empty($assicurazione_crediti)) {
                $assicurazione_crediti->fixTotale();
                $assicurazione_crediti->save();
            }
        }

        flash()->info(tr('Scadenze aggiornate!'));

        break;

    case 'delete':
        $scadenza = Scadenza::find($id_record);
        $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $scadenza->idanagrafica)->where('data_inizio', '<=', $scadenza->scadenza)->where('data_fine', '>=', $scadenza->scadenza)->first();

        $dbo->table('co_scadenziario')
            ->where('id', $id_record)
            ->delete();

        if (!empty($assicurazione_crediti)) {
            $assicurazione_crediti->fixTotale();
            $assicurazione_crediti->save();
        }

        flash()->info(tr('Scadenza eliminata!'));

        break;

    case 'allega_fattura':
        $scadenza = Scadenza::find($id_record);
        $id_documento = post('iddocumento');
        $print_predefined = PrintTemplate::where('predefined', 1)->where('id_module', Module::where('name', 'Fatture di vendita')->first()->id)->first();

        $print = Prints::render($print_predefined->id, $id_documento, null, true);
        $upload = Uploads::upload($print['pdf'], [
            'name' => $scadenza->descrizione,
            'original_name' => $scadenza->descrizione.'.pdf',
            'category' => 'Generale',
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        flash()->info(tr('Stampa allegata correttamente!'));

        break;

    case 'update_inline_scadenza':
        $id_scadenza = post('id_scadenza');
        $scadenza = Scadenza::find($id_scadenza);

        if (!empty($scadenza)) {
            // Aggiornamento dei campi modificabili inline
            $scadenza->id_banca_azienda = post('id_banca_azienda') ?: null;
            $scadenza->id_banca_controparte = post('id_banca_controparte') ?: null;
            $scadenza->id_pagamento = post('id_pagamento') ?: null;
            $scadenza->data_concordata = post('data_concordata') ?: null;
            $scadenza->da_pagare = post('da_pagare') ?: 0;
            $scadenza->pagato = post('pagato') ?: 0;

            $scadenza->save();

            flash()->info(tr('Scadenza aggiornata!'));
        } else {
            flash()->error(tr('Scadenza non trovata!'));
        }

        break;

    case 'save_new_scadenza':
        $index = post('index');
        $iddocumento = post('iddocumento');
        $idanagrafica = post('idanagrafica');

        // Recupera i dati dai campi del form
        $id_banca_azienda = post('id_banca_azienda');
        $id_banca_controparte = post('id_banca_controparte');
        $id_pagamento = post('id_pagamento');
        $scadenza = post('scadenza');
        $data_concordata = post('data_concordata');
        $da_pagare = post('da_pagare');
        $pagato = post('pagato');

        // Verifica se i campi essenziali sono valorizzati
        if (!empty($scadenza) && !empty($da_pagare) && $da_pagare != 0) {
            // Crea una nuova scadenza
            $descrizione = 'Scadenza documento';
            $tipo = 'fattura'; // Tipo di default
            $data_emissione = date('Y-m-d');

            $dbo->insert('co_scadenziario', [
                'idanagrafica' => $idanagrafica,
                'iddocumento' => $iddocumento,
                'descrizione' => $descrizione,
                'tipo' => $tipo,
                'data_emissione' => $data_emissione,
                'scadenza' => $scadenza,
                'da_pagare' => $da_pagare,
                'pagato' => $pagato ?: 0,
                'id_pagamento' => $id_pagamento ?: null,
                'id_banca_azienda' => $id_banca_azienda ?: null,
                'id_banca_controparte' => $id_banca_controparte ?: null,
                'data_concordata' => $data_concordata ?: null,
            ]);
            $id_record = $dbo->lastInsertedID();

            $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $idanagrafica)->where('data_inizio', '<=', $scadenza)->where('data_fine', '>=', $scadenza)->first();
            if (!empty($assicurazione_crediti)) {
                $assicurazione_crediti->fixTotale();
                $assicurazione_crediti->save();
            }

            echo json_encode(['success' => true, 'id_record' => $id_record]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dati insufficienti per salvare la scadenza']);
        }

        break;
}
