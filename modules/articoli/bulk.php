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
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Modules\Articoli\Export\CSV;
use Modules\Iva\Aliquota;
use Modules\ListiniCliente\Articolo as ArticoloListino;
use Modules\Preventivi\Components\Articolo as ArticoloPreventivo;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\ListinoClienti\DettaglioPrezzo;

include_once __DIR__.'/../../core.php';

// Segmenti
$id_preventivi = Module::where('name', 'Preventivi')->first()->id;
$id_segment = $_SESSION['module_'.$id_preventivi]['id_segment'];

switch (post('op')) {
    case 'change-acquisto':
        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $percentuale = post('percentuale');

            $new_prezzo_acquisto = $articolo->prezzo_acquisto + ($articolo->prezzo_acquisto * $percentuale / 100);
            $articolo->prezzo_acquisto = $new_prezzo_acquisto;
            $articolo->save();

            if (!empty($articolo->id_fornitore)) {
                $prezzo_predefinito = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $articolo->id_fornitore, 'uscita')->first();
                $prezzo_predefinito->setPrezzoUnitario($new_prezzo_acquisto);
                $prezzo_predefinito->save();
            }
        }

        flash()->info(tr('Prezzi di acquisto aggiornati!'));

        break;

    case 'change-vendita':
        $percentuale = post('percentuale');
        $prezzo_partenza = post('prezzo_partenza');
        $tipologia = post('tipologia');
        $arrotondamento = post('arrotondamento');
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $articoli_coeff = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);

            if (empty((int) $articolo->coefficiente)) {
                $prezzo_partenza = post('prezzo_partenza') == 'vendita' ? $articolo->prezzo_vendita : $articolo->prezzo_acquisto;
                $aliquota_iva = floatval(Aliquota::find($articolo->idiva_vendita)->percentuale);

                $new_prezzo_vendita = $prezzo_partenza + ($prezzo_partenza * $percentuale / 100);

                // Arrotondamento
                if (!empty($tipologia) && !empty($arrotondamento)) {
                    if ($tipologia == 'ivato') {
                        $new_prezzo_vendita = $new_prezzo_vendita + ($new_prezzo_vendita * $aliquota_iva / 100);
                    }

                    $new_prezzo_vendita = ceil($new_prezzo_vendita / $arrotondamento) * $arrotondamento;
                }

                if (in_array($tipologia, ['ivato', '']) && !$prezzi_ivati) {
                    $new_prezzo_vendita = $new_prezzo_vendita * 100 / (100 + $aliquota_iva);
                }

                if (in_array($tipologia, ['imponibile', '']) && $prezzi_ivati) {
                    $new_prezzo_vendita = $new_prezzo_vendita + ($new_prezzo_vendita * $aliquota_iva / 100);
                }

                $articolo->setPrezzoVendita($new_prezzo_vendita, $articolo->idiva_vendita);
                $articolo->save();
            } else {
                ++$articoli_coeff;
            }
        }

        flash()->info(tr('Prezzi di vendita aggiornati!'));
        if ($articoli_coeff > 0) {
            flash()->warning(tr('_NUM_ prezzi di vendita non aggiornati per coefficiente impostato!', [
                '_NUM_' => $articoli_coeff,
            ]));
        }

        break;

    case 'change-coefficiente':
        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $coefficiente = post('coefficiente');

            $articolo->coefficiente = $coefficiente;
            $articolo->prezzo_vendita = $articolo->prezzo_acquisto * $coefficiente;
            $articolo->save();
        }

        flash()->info(tr('Coefficienti di vendita aggiornati!'));

        break;

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $elementi = $dbo->fetchArray('
                SELECT `co_documenti`.`id` FROM `co_documenti` 
                INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` 
                WHERE `co_righe_documenti`.`idarticolo` = '.prepare($id).'
                
                UNION 
                
                SELECT `dt_ddt`.`id` FROM `dt_ddt` 
                INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id` 
                WHERE `dt_righe_ddt`.`idarticolo` = '.prepare($id).'
                
                UNION 
                
                SELECT `or_ordini`.`id` FROM `or_ordini` 
                INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` 
                WHERE `or_righe_ordini`.`idarticolo` = '.prepare($id).'
                
                UNION 
                
                SELECT `co_contratti`.`id` FROM `co_contratti` 
                INNER JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti`.`id` 
                WHERE `co_righe_contratti`.`idarticolo` = '.prepare($id).'
                
                UNION 
                
                SELECT `co_preventivi`.`id` FROM `co_preventivi` 
                INNER JOIN `co_righe_preventivi` ON `co_righe_preventivi`.`idpreventivo` = `co_preventivi`.`id` 
                WHERE `co_righe_preventivi`.`idarticolo` = '.prepare($id).'
                
                UNION 
                
                SELECT `in_interventi`.`id` FROM `in_interventi` 
                INNER JOIN `in_righe_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` 
                WHERE `in_righe_interventi`.`idarticolo` = '.prepare($id)
            );

            if (!empty($elementi)) {
                $dbo->query('UPDATE `mg_articoli` SET `deleted_at` = NOW() WHERE `id` = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            } else {
                $dbo->query('DELETE FROM `mg_articoli` WHERE `id` = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            }
        }

        flash()->info(tr('Articoli eliminati!'));

        break;

    case 'stampa-etichette':
        $_SESSION['superselect']['id_articolo_barcode'] = $id_records;

        if (post('tipologia') == 'singola') {
            $id_print = Prints::getPrints()['Barcode'];
        } else {
            $id_print = Prints::getPrints()['Barcode bulk'];
        }

        redirect(base_path().'/pdfgen.php?id_print='.$id_print.'&id_record='.Articolo::where('codice', '!=', '')->first()->id);
        exit;

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

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome, $data, 0, post('id_segment'));
        $id_preventivo = $preventivo->id;

        foreach ($id_records as $id) {
            $originale = Articolo::find($id);
            $articolo = ArticoloPreventivo::build($preventivo, $originale);
            $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');
            $articolo->qta = 1;
            $articolo->um = $originale->um ?: null;
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->prezzo_unitario = $originale->prezzo_vendita;
            $articolo->idiva = $id_iva;
            $articolo->setPrezzoUnitario($originale->prezzo_vendita, $id_iva);
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
        redirect(base_path().'/editor.php?id_module='.$id_preventivi.'&id_record='.$id_preventivo);
        exit;

    case 'export-csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $anagrafiche = Articolo::whereIn('id', $id_records)->get();
        $exporter->setRecords($anagrafiche);

        $count = $exporter->exportRecords();

        download($file, 'articoli.csv');
        break;

    case 'change-categoria':
        $categoria = post('id_categoria');
        $sottocategoria = post('subcategoria');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $articolo->id_categoria = $categoria;
            $articolo->id_sottocategoria = $sottocategoria ?: null;
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Categoria e Sottocategoria aggiornata a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'change-iva':
        $iva = post('id_iva');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $articolo->idiva_vendita = $iva;
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Aliquota iva cambiata a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'set-acquisto-ifzero':
        $n_art = 0;
        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);

            if ($articolo->prezzo_acquisto == 0 && empty($articolo->idfornitore)) {
                $new_prezzo_acquisto = $dbo->fetchOne('SELECT (`prezzo_unitario`-`sconto_unitario`) AS prezzo_acquisto FROM `co_righe_documenti` LEFT JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id`=`co_documenti`.`idtipodocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `idarticolo`='.prepare($id).' AND `dir`="uscita" ORDER BY `co_documenti`.`data` DESC, `co_righe_documenti`.`id` DESC LIMIT 0,1')['prezzo_acquisto'];

                $articolo->prezzo_acquisto = $new_prezzo_acquisto;
                $articolo->save();

                if ($new_prezzo_acquisto != 0) {
                    ++$n_art;
                }
            }
        }

        flash()->info(tr('Prezzi di acquisto aggiornati per _NUM_ articoli!', [
            '_NUM_' => $n_art,
        ]));

        break;

    case 'change-um':
        $um = post('um');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $articolo->um = $um;
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Unità di misura cambiata a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'change-conto-acquisto':
        $conto_acquisto = post('conto_acquisto');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $articolo->idconto_acquisto = $conto_acquisto;
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Conto predefinito di acquisto cambiato a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'change-conto-vendita':
        $conto_vendita = post('conto_vendita');
        $n_articoli = 0;

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $articolo->idconto_vendita = $conto_vendita;
            $articolo->save();

            ++$n_articoli;
        }

        if ($n_articoli > 0) {
            flash()->info(tr('Conto predefinito di vendita cambiato a _NUM_ articoli!', [
                '_NUM_' => $n_articoli,
            ]));
        } else {
            flash()->warning(tr('Nessun articolo modificato!'));
        }

        break;

    case 'set-provvigione':
        $n_art = 0;
        foreach ($id_records as $id) {
            $exist = $dbo->selectOne('co_provvigioni', 'id', ['idarticolo' => $id, 'idagente' => post('idagente')]);

            if ($exist) {
                $dbo->update('co_provvigioni', [
                    'idagente' => post('idagente'),
                    'provvigione' => post('provvigione'),
                    'tipo_provvigione' => post('tipo_provvigione'),
                ], ['idarticolo' => $id, 'idagente' => post('idagente')]);
            } else {
                $dbo->insert('co_provvigioni', [
                    'idarticolo' => $id,
                    'idagente' => post('idagente'),
                    'provvigione' => post('provvigione'),
                    'tipo_provvigione' => post('tipo_provvigione'),
                ]);
            }
            ++$n_art;
        }

        flash()->info(tr('Provvigioni inserite correttamente!', [
            '_NUM_' => $n_art,
        ]));

        break;

    case 'add-listino':
        $id_listino = post('id_listino');
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

        foreach ($id_records as $id) {
            $articolo = Articolo::find($id);
            $prezzo_unitario = $prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita;
            $articolo_listino = ArticoloListino::where('id_articolo', $id)->where('id_listino', $id_listino)->first();

            if (!$articolo_listino) {
                $articolo_listino = ArticoloListino::build($articolo, $id_listino);
            }
            $articolo_listino->data_scadenza = post('data_scadenza') ?: null;
            $articolo_listino->setPrezzoUnitario($prezzo_unitario);
            $articolo_listino->sconto_percentuale = post('sconto_percentuale');
            $articolo_listino->save();
        }

        flash()->info(tr('Listino aggiornato correttamente!'));

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

$operations['export-csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con gli articoli selezionati?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

$operations['change-acquisto'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna prezzo di acquisto').'</span>',
    'data' => [
        'title' => tr('Aggiornare il prezzo di acquisto per gli articoli selezionati?'),
        'msg' => tr('Per indicare uno sconto inserire la percentuale con il segno meno, al contrario per un rincaro inserire la percentuale senza segno.').'<br><br>{[ "type": "number", "label": "'.tr('Percentuale sconto/magg.').'", "name": "percentuale", "required": 1, "icon-after": "%" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['change-vendita'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna prezzo di vendita').'</span>',
    'data' => [
        'title' => tr('Aggiornare il prezzo di vendita per gli articoli selezionati?'),
        'msg' => tr('Per indicare uno sconto inserire la percentuale con il segno meno, al contrario per un rincaro inserire la percentuale senza segno.').'<br><br>
        {[ "type": "select", "label": "'.tr('Partendo da:').'", "name": "prezzo_partenza", "required": 1, "values": "list=\"acquisto\":\"Prezzo di acquisto\",\"vendita\":\"Prezzo di vendita\"" ]}<br>
        {[ "type": "number", "label": "'.tr('Percentuale sconto/magg.').'", "name": "percentuale", "required": 1, "icon-after": "%" ]}<br>
        {[ "type": "select", "label": "'.tr('Arrotonda prezzo:').'", "name": "tipologia", "values": "list=\"0\":\"Non arrotondare\",\"imponibile\":\"Imponibile\",\"ivato\":\"Ivato\"", "value": 0 ]}<br>
        {[ "type": "select", "label": "'.tr('Arrotondamento:').'", "name": "arrotondamento", "values": "list=\"0.1\":\"0,10 €\",\"1\":\"1,00 €\",\"10\":\"10,00 €\",\"100\":\"100,00 €\"" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['change-coefficiente'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna coefficiente di vendita').'</span>',
    'data' => [
        'title' => tr('Aggiornare il coefficiente di vendita per gli articoli selezionati?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificato il coefficiente e il relativo prezzo di vendita').'<br><br>{[ "type": "number", "label": "'.tr('Coefficiente di vendita').'", "name": "coefficiente", "required": 1 ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['stampa-etichette'] = [
    'text' => '<span><i class="fa fa-barcode"></i> '.tr('Stampa etichette').'</span>',
    'data' => [
        'title' => tr('Stampare le etichette?'),
        'msg' => tr('Per ciascun articolo selezionato, se presente il barcode, verrà stampata un\'etichetta').'<br><br>
        {[ "type": "select", "label": "'.tr('Tipologia stampa').'", "name": "tipologia", "required": 1, "values": "list=\"singola\":\"Singola\",\"a4\":\"Formato A4\"", "value": "singola" ]}<br>',
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
        {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_preventivi, 'is_sezionale' => 1]).', "value": "'.$id_segment.'", "select-options-escape": true ]}
        {[ "type": "select", "label": "'.tr('Tipo di attività').'", "name": "id_tipo", "ajax-source": "tipiintervento", "required": 1 ]}
        {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['change-categoria'] = [
    'text' => '<span><i class="fa fa-briefcase"></i> '.tr('Aggiorna categoria e sottocategoria').'</span>',
    'data' => [
        'title' => tr('Cambiare la categoria e la sottocategoria?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificata la categoria e la sottocategoria').'
        <br><br>{[ "type": "select", "label": "'.tr('Categoria').'", "name": "id_categoria", "required": 1, "ajax-source": "categorie", "extra": "onchange=\"$(\'#subcategoria\').enable();updateSelectOption(\'id_categoria\', $(\'#id_categoria\').val());session_set(\'superselect,id_categoria\', $(\'#id_categoria\').val(), 0);$(\'#subcategoria\').val(null).trigger(\'change\');\"" ]}<br>
        {[ "type": "select", "label": "'.tr('Sottocategoria').'", "name": "subcategoria", "ajax-source": "sottocategorie", "disabled": "1", "select-options-escape": true ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['change-iva'] = [
    'text' => '<span><i class="fa fa-percent"></i> '.tr('Aggiorna aliquota iva').'</span>',
    'data' => [
        'title' => tr('Cambiare l\'aliquota iva?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificata l\'aliquota iva').'
        <br><br>{[ "type": "select", "label": "'.tr('Iva').'", "name": "id_iva", "required": 1, "ajax-source": "iva" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['set-acquisto-ifzero'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Imposta prezzo di acquisto da fattura ').'</span>',
    'data' => [
        'title' => tr('Impostare il prezzo di acquisto per gli articoli selezionati?'),
        'msg' => 'Il prezzo di acquisto verrà impostato sugli articoli che non hanno nessun prezzo di acquisto inserito e verrà aggiornato in base alla fattura di acquisto più recente',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['change-um'] = [
    'text' => '<span><i class="fa fa-balance-scale"></i> '.tr('Aggiorna unità di misura').'</span>',
    'data' => [
        'title' => tr('Cambiare l\'unità di misura?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificata l\'unità di misura').'
        <br><br>{[ "type": "select", "label": "'.tr('Unità di misura').'", "name": "um", "required": 1, "ajax-source": "misure" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['change-conto-acquisto'] = [
    'text' => '<span><i class="fa fa-money"></i> '.tr('Aggiorna conto predefinito di acquisto').'</span>',
    'data' => [
        'title' => tr('Cambiare il conto predefinito di acquisto?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificato il conto predefinito di acquisto').'
        <br><br>{[ "type": "select", "label": "'.tr('Conto acquisto').'", "name": "conto_acquisto", "required": 1, "ajax-source": "conti-acquisti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['change-conto-vendita'] = [
    'text' => '<span><i class="fa fa-money"></i> '.tr('Aggiorna conto predefinito di vendita').'</span>',
    'data' => [
        'title' => tr('Cambiare il conto predefinito di vendita?'),
        'msg' => tr('Per ciascun articolo selezionato, verrà modificato il conto predefinito di vendita').'
        <br><br>{[ "type": "select", "label": "'.tr('Conto vendita').'", "name": "conto_vendita", "required": 1, "ajax-source": "conti-vendite" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['set-provvigione'] = [
    'text' => '<span><i class="fa fa-percent"></i> '.tr('Imposta una provvigione').'</span>',
    'data' => [
        'title' => tr('Impostare una provvigione?'),
        'msg' => tr('Selezionare un agente e la provvigione prevista:').'
        <br><br>{[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "required": 1, "ajax-source": "agenti" ]}
        <br>{[ "type": "number", "label": "'.tr('Provvigione').'", "name": "provvigione", "required": 1, "icon-after": "choice|untprc|" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['add-listino'] = [
    'text' => '<span><i class="fa fa-plus"></i> '.tr('Aggiungi a listino cliente').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero aggiungere gli articoli al listino cliente?').'<br><br>{[ "type": "select", "label": "'.tr('Listino cliente').'", "name": "id_listino", "required": 1, "ajax-source": "listini" ]}
        <br>{[ "type": "number", "label": "'.tr('Sconto percentuale').'", "name": "sconto_percentuale", "required": 1, "icon-after": "%" ]}
        <br>{[ "type": "date", "label": "'.tr('Data scadenza').'", "name": "data_scadenza", "placeholder": "'.tr('Utilizza data scadenza predefinita listino').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

return $operations;
