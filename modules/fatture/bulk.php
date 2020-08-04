<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
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
        $list = [];
        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);
            array_push($list, $fattura->numero_esterno);

            $id_segment = (post('id_segment') ? post('id_segment') : $fattura->id_segment);
            $dir = $dbo->fetchOne('SELECT dir FROM co_tipidocumento WHERE id='.prepare($fattura->idtipodocumento))['dir'];

            //+ 1 giorno
            if (post('skip_time') == 'Giorno') {
                $data = date('Y-m-d', strtotime('+1 day', strtotime($fattura->data)));
            }

            //+ 1 settimana
            if (post('skip_time') == 'Settimana') {
                $data = date('Y-m-d', strtotime('+1 week', strtotime($fattura->data)));
            }

            //+ 1 mese
            if (post('skip_time') == 'Mese') {
                $data = date('Y-m-d', strtotime('+1 month', strtotime($fattura->data)));
            }

            //+ 1 anno
            if (post('skip_time') == 'Anno') {
                $data = date('Y-m-d', strtotime('+1 year', strtotime($fattura->data)));
            }

            $new = $fattura->replicate();

            $new->data = $data;
            $new->id_segment = $id_segment;
            $new->numero = Fattura::getNextNumero($data, $dir, $id_segment);
            if (!empty($fattura->numero_esterno)) {
                $new->numero_esterno = Fattura::getNextNumeroSecondario($data, $dir, $id_segment);
            }

            $new->codice_stato_fe = null;
            $new->progressivo_invio = null;
            $new->data_stato_fe = null;

            $stato = Stato::where('descrizione', 'Bozza')->first();
            $new->stato()->associate($stato);

            $new->save();

            $righe = $fattura->getRighe();
            foreach ($righe as $riga) {
                $new_riga = $riga->replicate();
                $new_riga->setParent($new);

                if (!post('riferimenti')) {
                    $new_riga->idpreventivo = 0;
                    $new_riga->idcontratto = 0;
                    $new_riga->idintervento = 0;
                    $new_riga->idddt = 0;
                    $new_riga->idordine = 0;
                }

                $new_riga->qta_evasa = 0;
                $new_riga->original_type = null;
                $new_riga->original_id = null;
                $new_riga->save();

                if ($new_riga->isArticolo()) {
                    $new_riga->movimenta($new_riga->qta);
                }
            }
        }

        flash()->info(tr('Fatture _LIST_ duplicate correttamente!', [
            '_LIST_' => implode(',', $list),
        ]));

        break;

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $documento = Fattura::find($id);
            try {
                $documento->delete();
            } catch (InvalidArgumentException $e) {
            }
        }

        flash()->info(tr('Fatture eliminate!'));
        break;
}

if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
    ];
}

$operations['copy-bulk'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Duplica selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero duplicare le righe selezionate?').'<br><br>{[ "type": "select", "label": "'.tr('Fattura in avanti di').'", "name": "skip_time", "required": 1, "values": "list=\"Giorno\":\"'.tr('Un giorno').'\", \"Settimana\":\"'.tr('Una settimana').'\", \"Mese\":\"'.tr('Un mese').'\", \"Anno\":\"'.tr('Un anno').'\" ", "value": "Giorno" ]}<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$id_module.' ORDER BY name", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}<br>{[ "type": "checkbox", "placeholder": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "name": "riferimenti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
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
