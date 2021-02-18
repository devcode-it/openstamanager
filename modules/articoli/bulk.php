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
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Modules\Preventivi\Components\Articolo as ArticoloPreventivo;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Prints;

switch (post('op')) {
    case 'change-acquisto':
        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $percentuale = post('percentuale');

            $new_prezzo_acquisto = $articolo->prezzo_acquisto + ($articolo->prezzo_acquisto * $percentuale / 100);
            $articolo->prezzo_acquisto = $new_prezzo_acquisto;
            $articolo->save();
        }

        flash()->info(tr('Prezzi di acquisto aggiornati!'));

        break;

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir` FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idarticolo` = '.prepare($id).')

            UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir` FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`id` IN (SELECT `idddt` FROM `dt_righe_ddt` WHERE `idarticolo` = '.prepare($id).')

            UNION SELECT `co_preventivi`.`id`, `co_preventivi`.`data_bozza`, `co_preventivi`.`numero`,  0 AS numero_esterno , "Preventivo" AS tipo_documento, 0 AS dir FROM `co_preventivi` WHERE `co_preventivi`.`id` IN (SELECT `idpreventivo` FROM `co_righe_preventivi` WHERE `idarticolo` = '.prepare($id).')  ORDER BY `data`');

            if (!empty($elementi)) {
                $dbo->query('UPDATE mg_articoli SET deleted_at = NOW() WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            } else {
                $dbo->query('DELETE FROM `mg_articoli` WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            }
        }

        flash()->info(tr('Articoli eliminati!'));

        break;

    case 'stampa-etichette':
        $_SESSION['superselect']['id_articolo_barcode'] = $id_records;
        $id_print = Prints::getPrints()['Barcode'];

        redirect(base_path().'/pdfgen.php?id_print='.$id_print.'&id_record='.Articolo::where('barcode', '!=', '')->first()->id);
        exit();

        break;

    case 'change-qta':
        $descrizione = post('descrizione');
        $data = post('data');
        $qta = post('qta');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $qta_movimento = $qta - $articolo->qta;
            $articolo->movimenta($qta_movimento, $descrizione, $data, true);

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Quantità cambiate a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'crea-preventivo':
        $nome = post('nome');
        $data = post('data');
        $id_tipo = post('id_tipo');
        $id_cliente = post('id_cliente');
        $anagrafica = Anagrafica::find($id_cliente);
        $tipo = TipoSessione::find($id_tipo);
        $n_articoli = 0;

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome, $data, 0);
        $id_preventivo = $preventivo->id;

        foreach ($id_records as $id) {
            $originale = Articolo::find($id);
            $articolo = ArticoloPreventivo::build($preventivo, $originale);
            $articolo->qta = 1;
            $articolo->descrizione = $originale->descrizione;
            $articolo->um = $originale->um ?: null;
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->prezzo_unitario = $originale->prezzo_vendita;
            $articolo->idiva = $originale->idiva_vendita;
            $articolo->setPrezzoUnitario($originale->prezzo_vendita, $originale->idiva_vendita);
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('_NUM_ articoli sono stati aggiunti al preventivo', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        $database->commitTransaction();
        redirect(base_path().'/editor.php?id_module='.Modules::get('Preventivi')['id'].'&id_record='.$id_preventivo);
        exit();

        break;
}

if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
        'data' => [
            'msg' => tr('Vuoi davvero eliminare gli articoli selezionati?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
        ],
    ];
}

$operations['change-acquisto'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna prezzo di acquisto').'</span>',
    'data' => [
        'title' => tr('Aggiornare il prezzo di acquisto per gli articoli selezionati?'),
        'msg' => 'Per indicare uno sconto inserire la percentuale con il segno meno, al contrario per un rincaro inserire la percentuale senza segno.<br><br>{[ "type": "number", "label": "'.tr('Percentuale sconto/rincaro').'", "name": "percentuale", "required": 1, "icon-after": "%" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['stampa-etichette'] = [
    'text' => '<span><i class="fa fa-barcode"></i> '.tr('Stampa etichette').'</span>',
    'data' => [
        'title' => tr('Stampare le etichette?'),
        'msg' => tr('Per ciascun articolo selezionato, se presente il barcode, verrà stampata un\'etichetta'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['change-qta'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna quantità').'</span>',
    'data' => [
        'title' => tr('Cambiare le quantità?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificata la quantità').'
        <br><br>{[ "type": "text", "label": "'.tr('Quantità').'", "name": "qta", "required": 1 ]}
        {[ "type": "text", "label": "'.tr('Causale').'", "name": "descrizione", "required": 1 ]}
        {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['crea-preventivo'] = [
    'text' => '<span><i class="fa fa-plus"></i> '.tr('Crea preventivo').'</span>',
    'data' => [
        'title' => tr('Creare preventivo?'),
        'msg' => tr('Ogni articolo selezionato, verrà aggiunto al preventivo').'
        <br><br>{[ "type": "text", "label": "'.tr('Nome preventivo').'", "name": "nome", "required": 1 ]}
        {[ "type": "select", "label": "'.tr('Cliente').'", "name": "id_cliente", "ajax-source": "clienti", "required": 1 ]}
        {[ "type": "select", "label": "'.tr('Tipo di attività').'", "name": "id_tipo", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento", "required": 1 ]}
        {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

return $operations;
