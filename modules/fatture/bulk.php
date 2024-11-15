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

use Carbon\Carbon;
use Models\Module;
use Modules\Aggiornamenti\Controlli\DatiFattureElettroniche;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Export\CSV;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;
use Util\Zip;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$stato_emessa = Stato::where('name', 'Emessa')->first()->id;
$is_fiscale = $dbo->selectOne('zz_segments', 'is_fiscale', ['id' => $_SESSION['module_'.$id_module]])['is_fiscale'];

switch (post('op')) {
    case 'export-bulk':
        $dir = base_dir().'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `co_documenti`.`id` IN('.implode(',', $id_records).')');

        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $dir.'tmp/', false, false);
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

    case 'exportFE-bulk':
        $dir = base_dir().'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fattureFE_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        $module = Module::find($id_module);

        if ($module->getTranslation('title') == 'Fatture di vendita') {
            $print_name = 'Fattura elettronica di vendita';
        } else {
            $print_name = 'Fattura elettronica di acquisto';
        }
        $id_print = Prints::getPrints()[$print_name];

        if (!empty($id_records)) {
            foreach ($id_records as $id_record) {
                Prints::render($id_print, $id_record, $dir.'tmp/', false, true);
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
                $fattura_elettronica = new FatturaElettronica($id);

                if (!empty($fattura_elettronica) && !$fattura_elettronica->isGenerated()) {
                    $file = $fattura_elettronica->save();
                    $added[] = $fattura->numero_esterno;
                }
            } catch (UnexpectedValueException) {
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

            try {
                $fattura_elettronica = new FatturaElettronica($fattura->id);

                if (!empty($fattura_elettronica) && $fattura_elettronica->isGenerated() && $fattura->codice_stato_fe == 'GEN') {
                    $fattura->codice_stato_fe = 'QUEUE';
                    $fattura->data_stato_fe = date('Y-m-d H:i:s');
                    $fattura->hook_send = true;
                    $fattura->save();

                    $added[] = $fattura->numero_esterno;
                }
            } catch (UnexpectedValueException) {
                $failed[] = $fattura->numero_esterno;
            }
        }

        flash()->info(tr('Le fatture elettroniche sono state aggiunte alla coda di invio'));

        break;

    case 'export-xml-bulk':
        $dir = base_dir().'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da esportare
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title`, `co_tipidocumento`.`dir` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record`=`co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento`=`co_statidocumento`.`id` WHERE `co_documenti`.`id` IN('.implode(',', $id_records).')');

        $failed = [];
        $added = 0;
        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $include = true;

                try {
                    if ($r['dir'] == 'entrata') {
                        $fe = new FatturaElettronica($fattura->id);
                        $include = $fe->isGenerated();
                    } else {
                        $include = $fattura->isFE();
                    }
                } catch (UnexpectedValueException) {
                    $include = false;
                }

                if (!$include) {
                    $failed[] = $fattura->numero_esterno;
                } else {
                    if ($r['dir'] == 'entrata') {
                        $dst = $fe->getFilename();
                        $src = $dbo->selectOne('zz_files', 'filename', ['original' => $dst])['filename'];
                    } else {
                        $src = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->filename);
                        $dst = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->original_name);
                    }

                    $file = slashes($module->upload_directory.'/'.$src);
                    $dest = slashes($dir.'tmp/'.$dst);

                    $result = copy($file, $dest);

                    if ($result) {
                        ++$added;
                    // operationLog('export-xml-bulk', ['id_record' => $r['id']]);
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

    case 'export-ricevute-bulk':
        $dir = base_dir().'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'ricevute_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da esportare
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title`, `co_tipidocumento`.`dir` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento`=`co_statidocumento`.`id` WHERE `co_documenti`.`id` IN('.implode(',', $id_records).')');

        $failed = [];
        $added = 0;
        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $zz_file = $dbo->table('zz_files')->where('id_module', '=', $id_module)->where('id_record', '=', $fattura->id)->where('name', 'like', 'Ricevuta%')->first();
                $src = basename($fattura->uploads()->where('id', $zz_file->id)->first()->filename);
                $dst = basename($fattura->uploads()->where('id', $zz_file->id)->first()->original_name);

                $file = slashes($module->upload_directory.'/'.$src);
                $dest = slashes($dir.'tmp/'.$dst);

                $result = copy($file, $dest);

                if ($result) {
                    ++$added;
                // operationLog('export-xml-bulk', ['id_record' => $r['id']]);
                } else {
                    $failed[] = $fattura->numero_esterno;
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
                flash()->warning(tr('Le ricevute _LIST_ non sono state incluse poichè non ancora generate o non presenti sul server', [
                    '_LIST_' => implode(', ', $failed),
                ]));
            }
        }
        break;

    case 'copy-bulk':
        $list = [];
        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);

            $id_segment = (post('id_segment') ?: $fattura->id_segment);
            $dir = $dbo->fetchOne('SELECT `dir` FROM `co_tipidocumento` WHERE `id`='.prepare($fattura->idtipodocumento))['dir'];

            // + 1 giorno
            if (post('skip_time') == 'Giorno') {
                $data = date('Y-m-d', strtotime('+1 day', strtotime($fattura->data)));
            }

            // + 1 settimana
            if (post('skip_time') == 'Settimana') {
                $data = date('Y-m-d', strtotime('+1 week', strtotime($fattura->data)));
            }

            // + 1 mese
            if (post('skip_time') == 'Mese') {
                $data = date('Y-m-d', strtotime('+1 month', strtotime($fattura->data)));
            }

            // + 1 anno
            if (post('skip_time') == 'Anno') {
                $data = date('Y-m-d', strtotime('+1 year', strtotime($fattura->data)));
            }

            $new = $fattura->replicate();

            $new->data = $data;
            $new->id_segment = $id_segment;
            $new->numero = Fattura::getNextNumero($data, $dir, $id_segment);
            $new->id_autofattura = null;

            $new->save();

            $righe = $fattura->getRighe();
            foreach ($righe as $riga) {
                $new_riga = $riga->replicate();
                $new_riga->setDocument($new);

                if (!post('riferimenti')) {
                    $new_riga->idpreventivo = 0;
                    $new_riga->idcontratto = 0;
                    $new_riga->idintervento = 0;
                    $new_riga->idddt = 0;
                    $new_riga->idordine = 0;
                }

                $new_riga->save();

                if ($new_riga->isArticolo()) {
                    $new_riga->movimenta($new_riga->qta);
                }
            }

            if (!empty($fattura->numero_esterno)) {
                array_push($list, $fattura->numero_esterno);
            }
        }

        flash()->info(tr('Fatture _LIST_ duplicate correttamente!', [
            '_LIST_' => implode(',', $list),
        ]));

        break;

    case 'check-bulk':
        $controllo = new DatiFattureElettroniche();
        $fatture = [];
        foreach ($id_records as $id) {
            $fattura_vendita = Fattura::vendita()
                ->whereNotIn('codice_stato_fe', ['ERR', 'NS', 'EC02', 'ERVAL'])
                ->where('data', '>=', $_SESSION['period_start'])
                ->where('data', '<=', $_SESSION['period_end'])
                ->where('id', '=', $id)
                ->orderBy('data')
                ->first();

            if (!empty($fattura_vendita)) {
                $fatture[$id] = $fattura_vendita;

                $controllo->checkFattura($fattura_vendita);
            }
        }

        $results = $controllo->getResults();
        $num = count($results);

        // Messaggi di risposta
        if (empty($fatture)) {
            flash()->warning(tr('Nessuna fattura utile per il controllo!'));
        } elseif (empty($results)) {
            flash()->info(tr('Nessuna anomalia!'));
        } else {
            flash()->info(tr('Fatture _LIST_ controllate.', [
                '_LIST_' => implode(',', array_column($results, 'numero')),
            ]));

            $riepilogo_anomalie = tr('Attenzione: Trovate _NUM_ anomalie! Le seguenti fatture non trovano corrispondenza tra XML e dati nel documento', ['_NUM_' => $num]).':</br></br>';

            foreach ($results as $anomalia) {
                $fattura = $fatture[$anomalia['id']];

                $riepilogo_anomalie .= '<ul>
    <li>'.reference($fattura, $fattura->getReference()).'</li>
    <li>'.$anomalia['descrizione'].'</li>
</ul><br>';
            }

            flash()->warning($riepilogo_anomalie);
        }
        break;

    case 'export-csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $fatture = Fattura::whereIn('id', $id_records)->get();
        $exporter->setRecords($fatture);

        $count = $exporter->exportRecords();

        download($file, 'fatture.csv');

        break;

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $documento = Fattura::find($id);
            try {
                $documento->delete();
            } catch (InvalidArgumentException) {
            }
        }

        flash()->info(tr('Fatture eliminate!'));
        break;

    case 'change-bank':
        $list = [];
        foreach ($id_records as $id) {
            $documento = Fattura::find($id);
            $documento->id_banca_azienda = post('id_banca');
            $documento->save();
            array_push($list, $fattura->numero_esterno);
        }

        flash()->info(tr('Banca aggiornata per le Fatture _LIST_ !', [
            '_LIST_' => implode(',', $list),
        ]));

        break;

    case 'change-stato':
        $list = [];
        $new_stato = Stato::where('name', 'Emessa')->first()->id;
        $fatture = Fattura::vendita()
        ->whereIn('id', $id_records)
        ->orderBy('data')
        ->get();

        foreach ($fatture as $fattura) {
            $data = $fattura->data;

            $fattura = Fattura::find($fattura['id']);
            $stato_precedente = Stato::find($fattura->idstatodocumento);

            $data_fattura_precedente = $dbo->fetchOne('
            SELECT
                MAX(`data`) AS datamax
            FROM
                `co_documenti`
                INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
                LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_documenti`.`idstatodocumento` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
                INNER JOIN `zz_segments` ON `zz_segments`.`id` = `co_documenti`.`id_segment`
            WHERE
                `co_statidocumento_lang`.`title` = "Emessa" AND `co_tipidocumento`.`dir`="entrata" AND `co_documenti`.`id_segment`='.$fattura->id_segment);

            if ((setting('Data emissione fattura automatica') == 1) && ($dir == 'entrata') && Carbon::parse($data)->lessThan(Carbon::parse($data_fattura_precedente['datamax'])) && (!empty($data_fattura_precedente['datamax']))) {
                $fattura->data = $data_fattura_precedente['datamax'];
                $fattura->data_competenza = $data_fattura_precedente['datamax'];
            }

            if ($dir == 'entrata') {
                $fattura->data_registrazione = post('data');
            } else {
                $fattura->data_registrazione = post('data_registrazione');
            }

            if ($stato_precedente->getTranslation('title') == 'Bozza' && $fattura->isFiscale()) {
                $fattura->stato()->associate($new_stato);
                $results = $fattura->save();
                $message = '';

                foreach ($results as $numero => $result) {
                    foreach ($result as $title => $links) {
                        foreach ($links as $link => $errors) {
                            if (empty($title)) {
                                flash()->warning(tr('La fattura elettronica num. _NUM_ potrebbe avere delle irregolarità!', [
                                    '_NUM_' => $numero,
                                ]).' '.tr('Controllare i seguenti campi: _LIST_', [
                                    '_LIST_' => implode(', ', $errors),
                                ]).'.');
                            } else {
                                $message .= '
                                    <p><b>'.$title.' '.$link.'</b></p>
                                    <ul>';

                                foreach ($errors as $error) {
                                    if (!empty($error)) {
                                        $message .= '
                                            <li>'.$error.'</li>';
                                    }
                                }

                                $message .= '
                                    </ul>';
                            }
                        }
                    }
                }

                if ($message) {
                    // Messaggi informativi sulle problematiche
                    $message = tr('La fattura elettronica numero _NUM_ non è stata generata a causa di alcune informazioni mancanti', [
                        '_NUM_' => $numero,
                    ]).':'.$message;

                    flash()->warning($message);
                }

                array_push($list, $fattura->numero_esterno);
            }
        }

        if (!empty($list)) {
            flash()->info(tr('Le fatture _LIST_ sono state emesse!', [
                '_LIST_' => implode(',', $list),
            ]));
        } else {
            flash()->warning(tr('Nessuna fattura emessa!'));
        }

        break;

    case 'verify-notifiche':
        foreach ($id_records as $id) {
            $documento = Fattura::find($id);

            if ($documento->codice_stato_fe == 'GEN' || $documento->codice_stato_fe == 'WAIT') {
                $result = Interaction::getInvoiceRecepits($id);
                $last_recepit = $result['results'][0];
                if (!empty($last_recepit)) {
                    // Importazione ultima ricevuta individuata
                    $fattura = Ricevuta::process($last_recepit);
                }
            }
        }
        break;

    case 'cambia-sezionale':
        $count = 0;
        $n_doc = 0;

        foreach ($id_records as $id) {
            $documento = Fattura::find($id);
            ++$count;

            if ($documento->stato->getTranslation('title') == 'Bozza') {
                $documento->id_segment = post('id_segment');
                $documento->save();
                ++$n_doc;
            }
        }

        if ($n_doc > 0) {
            flash()->info(tr('_NUM_ fatture spostate', [
                '_NUM_' => $n_doc,
            ]));
        }

        if (($count - $n_doc) > 0) {
            flash()->warning(tr('_NUM_ fatture non sono state spostate perchè non sono in stato "Bozza".', [
                '_NUM_' => $count - $n_doc,
            ]));
        }

        break;
}

$operations['change-bank'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna banca').'</span>',
    'data' => [
        'title' => tr('Aggiornare la banca?'),
        'msg' => tr('Per ciascuna fattura selezionata, verrà aggiornata la banca').'
        <br><br>{[ "type": "select", "label": "'.tr('Banca').'", "name": "id_banca", "required": 1, "values": "query=SELECT id, CONCAT (nome, \' - \' , iban) AS descrizione FROM co_banche WHERE id_anagrafica='.prepare($anagrafica_azienda->idanagrafica).'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['cambia-sezionale'] = [
    'text' => '<span><i class="fa fa-tags"></i> '.tr('Cambia sezionale'),
    'data' => [
        'title' => tr('Cambia sezionale'),
        'msg' => tr('Scegli il sezionale _TIPOLOGIA_ in cui spostare le fatture in stato "Bozza" selezionate', [
            '_TIPOLOGIA_' => $is_fiscale ? tr('fiscale') : tr('non fiscale'),
        ]).':<br><br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1, 'is_fiscale' => $is_fiscale, 'escludi_id' => $_SESSION['module_'.$id_module]['id_segment']]).', "select-options-escape": true ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
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

if ($module->getTranslation('title') == 'Fatture di vendita') {
    $operations['check-bulk'] = [
        'text' => '<span><i class="fa fa-list-alt"></i> '.tr('Controlla fatture elettroniche').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Controllare corrispondenza tra XML e fattura di vendita?<br><small>(le fatture dovranno essere state generate)</small>'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];
}

$operations['copy-bulk'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Duplica selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero duplicare le righe selezionate?').'<br><br>{[ "type": "select", "label": "'.tr('Fattura in avanti di').'", "name": "skip_time", "required": 1, "values": "list=\"Giorno\":\"'.tr('Un giorno').'\", \"Settimana\":\"'.tr('Una settimana').'\", \"Mese\":\"'.tr('Un mese').'\", \"Anno\":\"'.tr('Un anno').'\" ", "value": "Giorno" ]}<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'", "select-options-escape": true ]}<br>{[ "type": "checkbox", "label": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "placeholder": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "name": "riferimenti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

// TODO: 06/08/2024 Migliorare introducendo controlli e portare in versione stabile
if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span> <span class="badge badge-danger">beta</span>',
    ];
}

if ($dir == 'entrata') {
    $operations['change-stato'] = [
        'text' => '<span><i class="fa fa-refresh"></i> '.tr('Emetti fatture').'</span>',
        'data' => [
            'title' => tr('Emissione fatture'),
            'msg' => tr('Vuoi emettere le fatture selezionate? Verranno emesse solo le fatture in Bozza'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
        ],
    ];
}

$operations['export-csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con le fatture selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

if ($module->getTranslation('title') == 'Fatture di vendita') {
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
$operations['exportFE-bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe FE').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare i PDF delle fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];
$operations['export-ricevute-bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta ricevute').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le ricevute selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];
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

if ($module->getTranslation('title') == 'Fatture di vendita') {
    $operations['genera-xml'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Genera fatture elettroniche').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Generare le fatture elettroniche per i documenti selezionati?<br><small>(le fatture dovranno trovarsi nello stato <i class="fa fa-clock-o text-info" title="Emessa"></i> <small>Emessa</small> e non essere mai state generate)</small>'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];
}

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'fatture',
        'url' => base_path().'/add.php?id_module='.Module::where('name', 'Prima nota')->first()->id,
    ],
];

if (Interaction::isEnabled()) {
    $operations['verify-notifiche'] = [
        'text' => '<i class="fa fa-question-circle"></i> '.tr('Verifica ricevute').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Vuoi verificare ed importare automaticamente le ricevute di queste fatture?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];
}

return $operations;
