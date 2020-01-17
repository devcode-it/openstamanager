<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;
use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;
use Util\Zip;

switch (post('op')) {
    case 'export-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $dir.'tmp/');
            }

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $zip);

                // Invio al browser dello zip
                download($zip);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;

    case 'delete-bulk':

        foreach ($id_records as $id) {
            $dbo->query('DELETE FROM co_documenti  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM mg_movimenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
        }

        flash()->info(tr('Fatture eliminate!'));

        break;

    case 'genera-xml':
        $failed = [];
        $added = [];

        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);
            try {
                $fattura_pa = new FatturaElettronica($id);

                if (!empty($fattura_pa) && !$fattura_pa->isGenerated()) {
                    $file = $fattura_pa->save($upload_dir);
                    $added[] = $fattura->numero_esterno;
                }
            } catch (UnexpectedValueException $e) {
                $failed[] = $fattura->numero_esterno;
            }
        }

        if (!empty($failed)) {
            flash()->warning(tr('Le fatture elettroniche _LIST_ non sono state generate.', [
                '_LIST_' => implode(', ', $failed),
            ]));
        }

        if (!empty($added)) {
            flash()->info(tr('Le fatture elettroniche _LIST_ sono state generate.', [
                '_LIST_' => implode(', ', $added),
            ]));
        }

        break;

    case 'hook-send':
        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);

            $fe = new \Plugins\ExportFE\FatturaElettronica($fattura->id);
            if ($fe->isGenerated() && $fattura->codice_stato_fe == 'GEN') {
                $fattura->codice_stato_fe = 'QUEUE';
                $fattura->data_stato_fe = date('Y-m-d H:i:s');
                $fattura->hook_send = true;
                $fattura->save();
            }
        }

        flash()->info(tr('Le fatture elettroniche sono state aggiunte alla coda di invio'));

        break;

    case 'export-xml-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da esportare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione, co_tipidocumento.dir FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        $failed = [];
        $added = 0;
        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $include = true;

                try {
                    if ($r['dir'] == 'entrata') {
                        $fe = new \Plugins\ExportFE\FatturaElettronica($fattura->id);
                        $include = $fe->isGenerated();
                    } else {
                        $include = $fattura->isFE();
                    }
                } catch (UnexpectedValueException $e) {
                    $include = false;
                }

                if (!$include) {
                    $failed[] = $fattura->numero_esterno;
                } else {
                    if ($r['dir'] == 'entrata') {
                        $src = $fe->getFilename();
                        $dst = $src;
                    } else {
                        $src = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->filepath);
                        $dst = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->original_name);
                    }

                    $file = slashes($module->upload_directory.'/'.$src);
                    $dest = slashes($dir.'/tmp/'.$dst);

                    $result = copy($file, $dest);

                    if ($result) {
                        ++$added;
                    //operationLog('export-xml-bulk', ['id_record' => $r['id']]);
                    } else {
                        $failed[] = $fattura->numero_esterno;
                    }
                }
            }

            // Creazione zip
            if (extension_loaded('zip') and !empty($added)) {
                Zip::create($dir.'tmp/', $zip);

                // Invio al browser il file zip
                download($zip);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }

            if (!empty($failed)) {
                flash()->warning(tr('Le fatture elettroniche _LIST_ non sono state incluse poichè non ancora generate o non presenti sul server', [
                    '_LIST_' => implode(', ', $failed),
                ]));
            }
        }
        break;


    case 'copy-bulk':

        foreach ($id_records as $id_record) {

            // Lettura dati fattura attuale
            $rs = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id='.prepare($id_record));

            $dir = $dbo->fetchOne("SELECT dir FROM co_tipidocumento WHERE id=".prepare($rs['idtipodocumento']))['dir'];

            //+ 1 settimana
            if(post('skip_time')=='Giorno'){
                $data = date("Y-m-d", strtotime( '+1 day' , strtotime ( $rs['data'] )) );
            }

            //+ 1 settimana
            if(post('skip_time')=='Settimana'){
                $data = date("Y-m-d", strtotime( '+1 week' , strtotime ( $rs['data'] )) );
            }

            //+ 1 mese
            if(post('skip_time')=='Mese'){
                $data = date("Y-m-d", strtotime( '+1 month' , strtotime ( $rs['data'] )) );
            }

            //+ 1 anno
            if(post('skip_time')=='Anno'){
                $data = date("Y-m-d", strtotime( '+1 year' , strtotime ( $rs['data'] )) );
            }

            // Duplicazione righe
            $righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

            $id_segment = $rs['id_segment'];

            // Calcolo prossimo numero fattura
            $numero = get_new_numerofattura(date('Y-m-d'));

            if ($dir == 'entrata') {
                $numero_esterno = get_new_numerosecondariofattura(date('Y-m-d'));
            } else {
                $numero_esterno = '';
            }

            // Duplicazione intestazione
            $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idcausalet, idspedizione, idporto, idaspettobeni, idvettore, n_colli, idsede_partenza, idsede_destinazione, idtipodocumento, idstatodocumento, idpagamento, idconto, idrivalsainps, idritenutaacconto, rivalsainps, iva_rivalsainps, ritenutaacconto, bollo, note, note_aggiuntive, buono_ordine, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($data).', '.prepare($rs['idanagrafica']).', '.prepare($rs[0]['idcausalet']).', '.prepare($rs['idspedizione']).', '.prepare($rs['idporto']).', '.prepare($rs['idaspettobeni']).', '.prepare($rs['idvettore']).', '.prepare($rs['n_colli']).', '.prepare($rs['idsede_partenza']).', '.prepare($rs['idsede_destinazione']).', '.prepare($rs['idtipodocumento']).', (SELECT id FROM co_statidocumento WHERE descrizione=\'Bozza\'), '.prepare($rs['idpagamento']).', '.prepare($rs['idconto']).', '.prepare($rs['idrivalsainps']).', '.prepare($rs['idritenutaacconto']).', '.prepare($rs['rivalsainps']).', '.prepare($rs['iva_rivalsainps']).', '.prepare($rs['ritenutaacconto']).', '.prepare($rs['bollo']).', '.prepare($rs['note']).', '.prepare($rs['note_aggiuntive']).', '.prepare($rs['buono_ordine']).', '.prepare($rs['id_segment']).')');
            $id_record = $dbo->lastInsertedID();

            // TODO: sistemare la duplicazione delle righe generiche e degli articoli, ignorando interventi, ddt, ordini, preventivi
            foreach ($righe as $riga) {

                if( !post('riferimenti') ){
                    $riga['idpreventivo'] = 0;
                    $riga['idcontratto'] = 0;
                    $riga['idintervento'] = 0;
                    $riga['idddt'] = 0;
                    $riga['idordine'] = 0;
                }
                // Scarico/carico nuovamente l'articolo da magazzino
                if (!empty($riga['idarticolo'])) {
                    add_articolo_infattura($id_record, $riga['idarticolo'], $riga['descrizione'], $riga['idiva'], $riga['qta'], $riga['subtotale'], $riga['sconto'], $riga['sconto_unitario'], $riga['tipo_sconto'], $riga['idintervento'], $riga['idconto'], $riga['um']);
                } else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idordine, idddt, idintervento, idarticolo, idpreventivo, idcontratto, is_descrizione, idtecnico, idagente, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, um, qta, `order`) VALUES('.prepare($id_record).', '.prepare($riga['idordine']).', '.prepare($riga['idddt']).', '.prepare($riga['idintervento']).', '.prepare($riga['idarticolo']).', '.prepare($riga['idpreventivo']).', '.prepare($riga['idcontratto']).', '.prepare($riga['is_descrizione']).', '.prepare($riga['idtecnico']).', '.prepare($riga['idagente']).', '.prepare($riga['idconto']).', '.prepare($riga['idiva']).', '.prepare($riga['desc_iva']).', '.prepare($riga['iva']).', '.prepare($riga['iva_indetraibile']).', '.prepare($riga['descrizione']).', '.prepare($riga['subtotale']).', '.prepare($riga['sconto']).', '.prepare($riga['sconto_unitario']).', '.prepare($riga['tipo_sconto']).', '.prepare($riga['idritenutaacconto']).', '.prepare($riga['ritenutaacconto']).', '.prepare($riga['idrivalsainps']).', '.prepare($riga['rivalsainps']).', '.prepare($riga['um']).', '.prepare($riga['qta']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            ricalcola_costiagg_fattura($id_record);
            aggiorna_sedi_movimenti('documenti', $id_record);


        }

        flash()->info(tr('Fatture duplicate correttamente!'));

        break;
}

if (App::debug()) {
    $operations = [
        'delete-bulk' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
    ];
}

$operations['copy-bulk'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Duplica selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero duplicare le righe selezionate?').'<br><br>{[ "type": "select", "label": "", "name": "skip_time", "required": 1, "values": "list=\"Giorno\":\"'.tr('Giorno').'\", \"Settimana\":\"'.tr('Settimana').'\", \"Mese\":\"'.tr('Mese').'\", \"Anno\":\"'.tr('Anno').'\" ", "value": "Giorno" ]}<br>{[ "type": "checkbox", "placeholder": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "name": "riferimenti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'fatture',
        'url' => $rootdir.'/add.php?id_module='.Modules::get('Prima nota')['id'],
    ],
];

if ($module->name == 'Fatture di vendita') {
    $operations['genera-xml'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Genera fatture elettroniche').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Generare le fatture elettroniche per i documenti selezionati?<br><small>(le fatture dovranno essere nello stato <i class="fa fa-clock-o text-info" title="Emessa"></i> <small>Emessa</small> e non essere mai state generate)</small>'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];

    $operations['export-bulk'] = [
        'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Vuoi davvero esportare i PDF delle fatture selezionate in un archivio ZIP?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];
}

$operations['export-xml-bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta XML').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

if (Interaction::isEnabled()) {
    $operations['hook-send'] = [
        'text' => '<span><i class="fa fa-paper-plane"></i> '.tr('Coda di invio FE').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Vuoi davvero aggiungere queste fatture alla coda di invio per le fatture elettroniche?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
        ],
    ];
}

return $operations;
