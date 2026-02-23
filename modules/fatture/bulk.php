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
use Models\Cache;
use Models\Module;
use Models\OperationLog;
use Modules\Aggiornamenti\Controlli\DatiFattureElettroniche;
use Modules\Anagrafiche\Anagrafica;
use Modules\Emails\Template;
use Modules\Fatture\Export\CSV;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Notifications\EmailNotification;
use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;
use Util\Zip;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
$stato_emessa = Stato::where('name', 'Emessa')->first()->id;
$is_fiscale = $dbo->selectOne('zz_segments', 'is_fiscale', ['id' => $_SESSION['module_'.$id_module]])['is_fiscale'];

switch (post('op')) {
    case 'export_bulk':
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
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `co_documenti`.`id` IN('.implode(',', array_map(prepare(...), $id_records)).')');

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

    case 'export_fe_bulk':
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

        if ($module->name == 'Fatture di vendita') {
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

    case 'generate_xml':
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

    case 'hook_send':
        $added = [];
        $failed = [];
        $skipped = [];

        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);

            if (!$fattura) {
                $failed[] = 'ID '.$id.' (non trovata)';
                continue;
            }

            try {
                $fattura_elettronica = new FatturaElettronica($fattura->id);

                // Verifica che la fattura sia in stato corretto per l'invio
                // Accetta 'GEN' (generata), NULL/vuoto (appena generate), 'ERR' (trasmissione fallita)
                if (!empty($fattura->codice_stato_fe) && $fattura->codice_stato_fe != 'GEN' && $fattura->codice_stato_fe != 'ERR') {
                    $skipped[] = $fattura->numero_esterno.' (stato: '.$fattura->codice_stato_fe.')';
                    continue;
                }

                // Verifica che la fattura elettronica sia generata e valida
                if (!empty($fattura_elettronica) && $fattura_elettronica->isGenerated()) {
                    $fattura->codice_stato_fe = 'QUEUE';
                    $fattura->data_stato_fe = date('Y-m-d H:i:s');
                    $fattura->hook_send = true;
                    $fattura->save();

                    // Logging dell'operazione di aggiunta alla coda di invio
                    OperationLog::setInfo('id_module', $id_module);
                    OperationLog::setInfo('id_record', $fattura->id);
                    OperationLog::build('hook-send');

                    $added[] = $fattura->numero_esterno;
                } else {
                    // Se la FE non è generata ma lo stato è vuoto, impostalo a GEN
                    if (empty($fattura->codice_stato_fe)) {
                        $fattura->codice_stato_fe = 'GEN';
                        $fattura->save();
                    }
                    $failed[] = $fattura->numero_esterno.' (FE non generata)';
                }
            } catch (UnexpectedValueException) {
                $failed[] = $fattura->numero_esterno.' (FE non valida)';
            } catch (Exception $e) {
                $failed[] = $fattura->numero_esterno.' (errore: '.$e->getMessage().')';
            }
        }

        // Messaggi di feedback
        if (!empty($added)) {
            flash()->info(tr('_NUM_ fatture elettroniche aggiunte alla coda di invio: _LIST_', [
                '_NUM_' => count($added),
                '_LIST_' => implode(', ', $added),
            ]));
        }

        if (!empty($skipped)) {
            flash()->warning(tr('_NUM_ fatture saltate (stato non corretto): _LIST_', [
                '_NUM_' => count($skipped),
                '_LIST_' => implode(', ', $skipped),
            ]));
        }

        if (!empty($failed)) {
            flash()->error(tr('_NUM_ fatture non aggiunte alla coda (errori): _LIST_', [
                '_NUM_' => count($failed),
                '_LIST_' => implode(', ', $failed),
            ]));
        }

        if (empty($added) && empty($skipped) && empty($failed)) {
            flash()->warning(tr('Nessuna fattura elaborata'));
        }

        break;

    case 'export_xml_bulk':
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
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title`, `co_tipidocumento`.`dir` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record`=`co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento`=`co_statidocumento`.`id` WHERE `co_documenti`.`id` IN('.implode(',', array_map(prepare(...), $id_records)).')');

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
                        $fattura_upload = $fattura->uploads()->where('name', 'Fattura Elettronica')->first();
                        $src = basename((string) $fattura_upload->filename);
                        $dst = basename((string) $fattura_upload->original_name);
                    }

                    $file = slashes('files/'.$module->attachments_directory.'/'.$src);
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

    case 'export_receipts_bulk':
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
        $fatture = $dbo->fetchArray('SELECT `co_documenti`.`id`, `numero_esterno`, `data`, `ragione_sociale`, `co_tipidocumento_lang`.`title`, `co_tipidocumento`.`dir` FROM `co_documenti` INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento`=`co_statidocumento`.`id` WHERE `co_documenti`.`id` IN('.implode(',', array_map(prepare(...), $id_records)).')');

        $failed = [];
        $added = 0;
        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $zz_file = $dbo->table('zz_files')->where('id_module', '=', $id_module)->where('id_record', '=', $fattura->id)->where('name', 'like', 'Ricevuta%')->first();
                $fattura_upload = $fattura->uploads()->where('id', $zz_file->id)->first();
                $src = basename((string) $fattura_upload->filename);
                $dst = basename((string) $fattura_upload->original_name);

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

    case 'copy_bulk':
        $list = [];
        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);

            $id_segment = (post('id_segment') ?: $fattura->id_segment);
            $dir = $dbo->fetchOne('SELECT `dir` FROM `co_tipidocumento` WHERE `id`='.prepare($fattura->idtipodocumento))['dir'];

            // + 1 giorno
            if (post('skip_time') == 'Giorno') {
                $data = date('Y-m-d', strtotime('+1 day', strtotime((string) $fattura->data)));
            }

            // + 1 settimana
            if (post('skip_time') == 'Settimana') {
                $data = date('Y-m-d', strtotime('+1 week', strtotime((string) $fattura->data)));
            }

            // + 1 mese
            if (post('skip_time') == 'Mese') {
                $data = date('Y-m-d', strtotime('+1 month', strtotime((string) $fattura->data)));
            }

            // + 1 anno
            if (post('skip_time') == 'Anno') {
                $data = date('Y-m-d', strtotime('+1 year', strtotime((string) $fattura->data)));
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
                    $new_riga->idintervento = null;
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

    case 'check_bulk':
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

    case 'export_csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $fatture = Fattura::whereIn('id', $id_records)->get();
        $exporter->setRecords($fatture);

        $count = $exporter->exportRecords();

        download($file, 'fatture.csv');
        exit;

    case 'delete_bulk':
        $count = 0;
        $count_tot = sizeof($id_records);

        foreach ($id_records as $id) {
            $documento = Fattura::find($id);
            $emails = database()->fetchOne("SELECT COUNT(id) as `count` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN(SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' AND `em_emails`.`id_record` = ".$id.' GROUP BY `em_emails`.`id_record`')['count'];

            if (($documento->codice_stato_fe == 'GEN' || $documento->codice_stato_fe == '') && empty($emails)) {
                try {
                    $documento->delete();
                } catch (InvalidArgumentException) {
                }
            } else {
                ++$count;
            }
        }

        $count_eliminati = $count_tot - $count;
        flash()->info(tr('_NUM_ Fatture eliminate!', [
            '_NUM_' => $count_eliminati,
        ]));

        if ($count > 0) {
            flash()->warning(tr('_NUM_ Fatture non eliminate in quanto sono già state inviate allo SDI, o via email!', [
                '_NUM_' => $count,
            ]));
        }

        break;

    case 'change_bank':
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

    case 'change_status':
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

    case 'send-invoices':
        $list = [];
        $user = auth_osm()->getUser();
        $fatture = Fattura::vendita()
            ->whereIn('id', $id_records)
            ->orderBy('data')
            ->get();

        // Template email predefinito
        $template = Template::where('id_module', $id_module)
            ->where('predefined', 1)
            ->first();

        if (empty($template)) {
            flash()->error(tr('Nessun template email predefinito trovato per il modulo Fatture!'));
            break;
        }

        $module = $template->module;
        $success_count = 0;
        $failed_count = 0;
        $failed_emails = [];

        foreach ($fatture as $fattura) {
            $mail = Modules\Emails\Mail::build($user, $template, $fattura->id);

            // Destinatari
            $emails = [];
            if (!empty($fattura->anagrafica->email)) {
                $emails[] = $fattura->anagrafica->email;
            }

            // Aggiungo email referenti in base alla mansione impostata nel template
            $mansioni = $dbo->select('em_mansioni_template', ['idmansione'], [], ['id_template' => $template->id]);
            foreach ($mansioni as $mansione) {
                $referenti = $dbo->table('an_referenti')
                    ->where('idmansione', $mansione['idmansione'])
                    ->where('idanagrafica', $fattura->idanagrafica)
                    ->where('email', '!=', '')
                    ->get();

                foreach ($referenti as $referente) {
                    if (!in_array($referente->email, $emails)) {
                        $emails[] = $referente->email;
                    }
                }
            }

            // Se non ci sono destinatari, salta questa fattura
            if (empty($emails)) {
                ++$failed_count;
                $failed_emails[] = $fattura->numero_esterno;
                continue;
            }

            // Aggiungi tutti i destinatari all'email
            foreach ($emails as $receiver) {
                $mail->addReceiver($receiver);
            }

            // Contenuti
            $placeholder_options = ['is_pec' => intval($mail->account->pec ?? 0)];
            $mail->content = $template->getTranslation('body');
            $mail->subject = $template->getTranslation('subject');

            // Conferma di lettura
            $mail->read_notify = $template->read_notify;

            // Prima rimuoviamo eventuali stampe predefinite per evitare duplicati
            $mail->resetPrints();

            // Stampe da allegare
            $selected_prints = $dbo->fetchArray('SELECT id_print FROM em_print_template WHERE id_template = '.prepare($template['id']));
            $prints = array_column($selected_prints, 'id_print');

            // Aggiungi le stampe selezionate come allegati SOLO per questa fattura
            foreach ($prints as $print_id) {
                // Passa l'ID della fattura corrente per allegare solo questa fattura
                $mail->addPrint($print_id, $fattura->id);
            }

            // Salvataggio email nella coda di invio
            $mail->save();

            // Invio mail istantaneo
            $email = EmailNotification::build($mail);
            $email_success = $email->send();

            if ($email_success) {
                OperationLog::setInfo('id_email', $mail->id);
                OperationLog::setInfo('id_module', $id_module);
                OperationLog::setInfo('id_record', $fattura->id);
                OperationLog::build('send-email');
                $list[] = $fattura->numero_esterno;
                ++$success_count;
            } else {
                $mail->delete();
                ++$failed_count;
                $failed_emails[] = $fattura->numero_esterno;
            }
        }

        // Mostra messaggi di riepilogo
        if ($success_count > 0) {
            flash()->info(tr('Inviate con successo _COUNT_ email per le fatture _LIST_', [
                '_COUNT_' => $success_count,
                '_LIST_' => implode(', ', $list),
            ]));
        }

        if ($failed_count > 0) {
            flash()->error(tr('Impossibile inviare _COUNT_ email per le fatture _LIST_', [
                '_COUNT_' => $failed_count,
                '_LIST_' => implode(', ', $failed_emails),
            ]));

            // Aggiungi suggerimento per verificare gli indirizzi email
            flash()->warning(tr('Verificare che gli indirizzi email dei destinatari siano corretti e che i domini esistano.'));
        }

        break;

    case 'verify_notifications':
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

    case 'change_segment':
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

$operations['change_bank'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna banca').'</span>',
    'data' => [
        'title' => tr('Aggiornare la banca?'),
        'msg' => tr('Per ciascuna fattura selezionata, verrà aggiornata la banca').'
        <br><br>{[ "type": "select", "label": "'.tr('Banca').'", "name": "id_banca", "required": 1, "values": "query=SELECT id, CONCAT (nome, \' - \' , iban) AS descrizione FROM co_banche WHERE id_anagrafica='.prepare($anagrafica_azienda->idanagrafica).'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['change_segment'] = [
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
    // Verifica lo stato del cron
    $cron_active = false;
    $ultima_esecuzione = Cache::where('name', 'Ultima esecuzione del cron')->first();

    if ($ultima_esecuzione && $ultima_esecuzione->content) {
        try {
            $data_ultima_esecuzione = Carbon::parse($ultima_esecuzione->content);
            $ora_attuale = Carbon::now();
            $ore_trascorse = $data_ultima_esecuzione->diffInHours($ora_attuale);

            if ($ore_trascorse <= 1) {
                $cron_active = true;
            }
        } catch (Exception) {
            // Se il contenuto non è una data valida
            $cron_active = false;
        }
    }

    $hook_send_class = $cron_active ? 'btn btn-lg btn-warning' : 'btn btn-lg btn-warning disabled';
    $hook_send_title = $cron_active ? '' : 'title="'.tr('Il cron non è configurato correttamente. Configurare il cron prima di utilizzare questa funzione.').'"';
    $hook_send_icon = $cron_active ? 'fa fa-paper-plane' : 'fa fa-exclamation-triangle';

    $operations['hook_send'] = [
        'text' => '<span><i class="'.$hook_send_icon.'"></i> '.tr('Invia FE').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Vuoi davvero aggiungere queste fatture alla coda di invio per le fatture elettroniche?'),
            'button' => tr('Procedi'),
            'class' => $hook_send_class,
        ],
        'attributes' => $hook_send_title,
    ];
}

if ($module->name == 'Fatture di vendita') {
    $operations['check_bulk'] = [
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

$operations['copy_bulk'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Duplica').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero duplicare le righe selezionate?').'<br><br>{[ "type": "select", "label": "'.tr('Fattura in avanti di').'", "name": "skip_time", "required": 1, "values": "list=\"Giorno\":\"'.tr('Un giorno').'\", \"Settimana\":\"'.tr('Una settimana').'\", \"Mese\":\"'.tr('Un mese').'\", \"Anno\":\"'.tr('Un anno').'\" ", "value": "Giorno" ]}<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'", "select-options-escape": true ]}<br>{[ "type": "checkbox", "label": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "placeholder": "'.tr('Aggiungere i riferimenti ai documenti esterni?').'", "name": "riferimenti" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['delete_bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina').'</span>',
];

if ($dir == 'entrata') {
    $operations['change_status'] = [
        'text' => '<span><i class="fa fa-refresh"></i> '.tr('Emetti fatture').'</span>',
        'data' => [
            'title' => tr('Emissione fatture'),
            'msg' => tr('Vuoi emettere le fatture selezionate? Verranno emesse solo le fatture in Bozza'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
        ],
    ];
}

$operations['export_csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con le fatture selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

if ($module->name == 'Fatture di vendita') {
    $operations['export_bulk'] = [
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
$operations['export_fe_bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe FE').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare i PDF delle fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['export_receipts_bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta ricevute').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le ricevute selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

$operations['export_xml_bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta XML').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

if ($module->name == 'Fatture di vendita') {
    $operations['generate_xml'] = [
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

$operations['send-invoices'] = [
    'text' => '<span><i class="fa fa-envelope"></i> '.tr('Invia fatture').'</span>',
    'data' => [
        'title' => tr('Invia fatture'),
        'msg' => tr('Vuoi inviare le fatture PDF ai contatti email predefiniti in anagrafica?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['registrazione_contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'fatture',
        'url' => base_path_osm().'/add.php?id_module='.Module::where('name', 'Prima nota')->first()->id,
    ],
];

if (Interaction::isEnabled()) {
    $operations['verify_notifications'] = [
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
