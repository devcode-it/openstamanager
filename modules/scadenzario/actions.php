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

use Modules\Scadenzario\Scadenza;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $tipo = post('tipo');
        $da_pagare = post('da_pagare');
        $descrizione = post('descrizione');
        $iddocumento = post('iddocumento') ?: '';
        $data_emissione = post('data_emissione') ?: date('Y-m-d');

        $dbo->query('INSERT INTO co_scadenziario(idanagrafica, iddocumento, descrizione, tipo, data_emissione, scadenza, da_pagare, pagato) VALUES('.prepare($idanagrafica).', '.prepare($iddocumento).', '.prepare($descrizione).', '.prepare($tipo).', '.prepare($data_emissione).', '.prepare($data).', '.prepare($da_pagare).", '0')");
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Scadenza inserita!'));
        break;

    case 'update':
        $idanagrafica = post('idanagrafica');
        $tipo = post('tipo');
        $descrizione = post('descrizione');
        $iddocumento = post('iddocumento') ?: 0;
        $scadenze = database()->table('co_scadenziario')->where('iddocumento', '=', $iddocumento)->orderBy('scadenza')->get();
        $totale_pagato = 0;
        $id_scadenza_non_completa = null;

        foreach ($scadenze as $id => $scadenza) {
            $pagato = floatval(post('pagato')[$id]);
            $data_scadenza = post('scadenza')[$id];
            $data_concordata = post('data_concordata')[$id];
            $da_pagare = post('da_pagare')[$id];

            if (!empty($iddocumento)) {
                $id_tipo = $dbo->selectOne('co_documenti', 'idtipodocumento', ['id' => $iddocumento])['idtipodocumento'];
                $tipo_documento = $dbo->selectOne('co_tipidocumento', '*', ['id' => $id_tipo]);

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

            $id_scadenza = $scadenza->id;
            if (!empty($id_scadenza)) {
                $database->update('co_scadenziario', [
                    'idanagrafica' => $idanagrafica,
                    'descrizione' => $descrizione,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $data_scadenza,
                    'data_concordata' => $data_concordata,
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
        }

        flash()->info(tr('Scadenze aggiornate!'));

        break;

    case 'delete':
        $dbo->query("DELETE FROM co_scadenziario WHERE id='".$id_record."'");
        flash()->info(tr('Scadenza eliminata!'));
        break;

    case 'allega_fattura':
        $scadenza = Scadenza::find($id_record);
        $id_documento = post('iddocumento');
        $print_predefined = $dbo->selectOne('zz_prints', '*', ['predefined' => 1, 'id_module' => Modules::get('Fatture di vendita')['id']]);

        $print = Prints::render($print_predefined['id'], $id_documento, null, true);
        $upload = Uploads::upload($print['pdf'], [
            'name' => $scadenza->descrizione,
            'original_name' => $scadenza->descrizione.'.pdf',
            'category' => 'Generale',
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        flash()->info(tr('Stampa allegata correttamente!'));

        break;
}
