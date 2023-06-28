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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\ListinoClienti\DettaglioPrezzo;

switch (post('op')) {
    case 'copy_listino':
        $id_anagrafiche = explode(",",post('idanagrafica', true)[0]);

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            // Informazioni di base
            $listino = $dbo->selectOne('mg_prezzi_articoli', '*', ['id' => $id]);
            $prezzo_unitario = $listino['prezzo_unitario'];
            $sconto = $listino['sconto_percentuale'];
            $id_articolo = $listino['id_articolo'];
            $direzione = $listino['dir'];
            $minimo = $listino['minimo'];
            $massimo = $listino['massimo'];

            $articolo = Articolo::find($id_articolo);

            foreach ($id_anagrafiche as $id_anagrafica) {
                $anagrafica = Anagrafica::find($id_anagrafica);

                if ($listino['id_anagrafica'] != $id_anagrafica) {
                    if ($minimo==null && $massimo==null) {
                        // Salvataggio del prezzo predefinito
                        $prezzo_unitario = $listino['prezzo_unitario'];
                        $sconto = $listino['sconto_percentuale'];
                        $dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
                            ->first();
                        if (empty($dettaglio_predefinito)) {
                            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
                        }

                        if ($dettaglio_predefinito->sconto_percentuale != $sconto || $dettaglio_predefinito->prezzo_unitario != $prezzo_unitario) {
                            $dettaglio_predefinito->sconto_percentuale = $sconto;
                            $dettaglio_predefinito->setPrezzoUnitario($prezzo_unitario);
                            $dettaglio_predefinito->save();
                            if ($articolo->id_fornitore == $anagrafica->idanagrafica && $direzione == 'uscita') {
                                $prezzo_unitario = $prezzo_unitario - ($prezzo_unitario * $sconto / 100);
                                $articolo->prezzo_acquisto = $prezzo_unitario;
                                $articolo->save();
                            }

                            $numero_totale++;
                        }
                    } else {
                        $dettaglio = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);

                        $dettaglio->minimo = $minimo;
                        $dettaglio->massimo = $massimo;
                        $dettaglio->sconto_percentuale = $sconto;
                        $dettaglio->setPrezzoUnitario($prezzo_unitario);
                        $dettaglio->save();

                        $numero_totale++;
                    }
                }
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ listini creati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun listino creato!'));
        }
        break;

    case 'change_prezzo':

        foreach ($id_records as $id) {
            $listino = DettaglioPrezzo::find($id);

            $prezzo_unitario_new = $listino->prezzo_unitario + ($listino->prezzo_unitario * post('percentuale') / 100);

            $listino->setPrezzoUnitario($prezzo_unitario_new);
            $listino->save();
        }

        flash()->info(tr('Listini aggiornati!'));

        break;
}

$segment = $dbo->selectOne('zz_segments', 'name', ['id' => $_SESSION['module_'.$id_module]['id_segment']])['name'];

if ($segment!='Tutti') {
    $operations['copy_listino'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Copia _TYPE_', ['_TYPE_' => strtolower($module['name'])]),
        'data' => [
            'title' => tr('Copiare i listini selezionati?'),
            'msg' => '{[ "type": "select", "multiple":"1", "label": "<small>'.tr('Selezionare le anagrafiche in cui copiare i listini selezionati:').'</small>", "ajax-source":"'.strtolower($segment).'", "name": "idanagrafica[]" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];
} else {
    $operations['copy_listino'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Copia _TYPE_', ['_TYPE_' => strtolower($module['name'])]),
        'data' => [
            'title' => tr('Selezionare prima un segmento tra "Clienti" e "Fornitori"'),
            'msg' => '',
            'button' => '',
            'class' => 'hide',
        ],
    ];
}

$operations['change_prezzo'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna prezzo unitario').'</span>',
    'data' => [
        'title' => tr('Aggiornare il prezzo unitario per i listini selezionati?'),
        'msg' => tr('Per indicare uno sconto inserire la percentuale con il segno meno, al contrario per un rincaro inserire la percentuale senza segno.').'<br><br>{[ "type": "number", "label": "'.tr('Percentuale sconto/magg.').'", "name": "percentuale", "required": 1, "icon-after": "%" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];




return $operations;
