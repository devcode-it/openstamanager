<?php

include_once __DIR__.'/core.php';

if (empty($structure) || empty($structure['enabled'])) {
    die(tr('Accesso negato'));
}

$upload_dir = DOCROOT.'/'.Uploads::getDirectory($id_module, $id_plugin);

$database->beginTransaction();

// GESTIONE UPLOAD
if (filter('op') == 'link_file' || filter('op') == 'unlink_file') {
    // Controllo sui permessi di scrittura per il modulo
    if (Modules::getPermission($id_module) != 'rw') {
        flash()->error(tr('Non hai permessi di scrittura per il modulo _MODULE_', [
            '_MODULE_' => '"'.Modules::get($id_module)['name'].'"',
        ]));
    }

    // Controllo sui permessi di scrittura per il file system
    elseif (!directory($upload_dir)) {
        flash()->error(tr('Non hai i permessi di scrittura nella cartella _DIR_!', [
            '_DIR_' => '"files"',
        ]));
    }

    // Gestione delle operazioni
    else {
        // UPLOAD
        if (filter('op') == 'link_file' && !empty($_FILES) && !empty($_FILES['blob']['name'])) {
            $upload = Uploads::upload($_FILES['blob'], [
                'name' => filter('nome_allegato'),
                'category' => filter('categoria'),
                'id_module' => $id_module,
                'id_plugin' => $id_plugin,
                'id_record' => $id_record,
            ]);

            // Creazione file fisico
            if (!empty($upload)) {
                flash()->info(tr('File caricato correttamente!'));
            } else {
                flash()->error(tr('Errore durante il caricamento del file!'));
            }
        }

        // DELETE
        elseif (filter('op') == 'unlink_file' && filter('filename') !== null) {
            $name = Uploads::delete(filter('filename'), [
                'id_module' => $id_module,
                'id_plugin' => $id_plugin,
                'id_record' => $id_record,
            ]);

            if (!empty($name)) {
                flash()->info(tr('File _FILE_ eliminato!', [
                    '_FILE_' => '"'.$name.'"',
                ]));
            } else {
                flash()->error(tr("Errore durante l'eliminazione del file!"));
            }
        }

        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.((!empty($options['id_plugin'])) ? '#tab_'.$options['id_plugin'] : ''));
    }
} elseif (filter('op') == 'download_file') {
    $rs = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module='.prepare($id_module).' AND id='.prepare(filter('id')).' AND filename='.prepare(filter('filename')));

    download($upload_dir.'/'.$rs[0]['filename'], $rs[0]['original']);
} elseif (post('op') == 'send-email') {
    $template = Mail::getTemplate(post('template'));
    $id_account = $template['id_smtp'];

    // Informazioni di log
    Filter::set('get', 'id_email', $template['id']);

    $mail = new Mail($id_account);

    // Conferma di lettura
    if (!empty(post('read_notify'))) {
        $mail->ConfirmReadingTo = $mail->From;
    }

    // Template
    $mail->setTemplate($template);

    // Destinatari
    $mail->addReceivers(post('destinatari'), post('tipo_destinatari'));

    // Oggetto
    $mail->Subject = post('subject');

    // Allegati
    $mail->attach(post('prints'), post('attachments'));

    // Contenuto
    $mail->Body = post('body');

    // Invio mail
    if (!$mail->send()) {
        flash()->error(tr("Errore durante l'invio dell'email").': '.$mail->ErrorInfo);
    } else {
        flash()->info(tr('Email inviata correttamente!'));
    }
}

// Inclusione di eventuale plugin personalizzato
if (!empty($structure['script'])) {
    include $structure->getEditFile();

    $database->commitTransaction();

    return;
}

// Caricamento funzioni del modulo
$modutil = $structure->filepath('modutil.php');
if (!empty($modutil)) {
    include_once $modutil;
}

// Lettura risultato query del modulo
$init = $structure->filepath('init.php');
if (!empty($init)) {
    include_once $init;
}

// Retrocompatibilità
if (!isset($record) && isset($records[0])) {
    $record = $records[0];
} elseif (!isset($records[0]) && isset($record)) {
    $records = [$record];
} elseif (!isset($record)) {
    $record = [];
    $records = [$record];
}

// Registrazione del record
HTMLBuilder\HTMLBuilder::setRecord($record);

if ($structure->permission == 'rw') {
    // Esecuzione delle operazioni di gruppo
    $id_records = post('id_records');
    $id_records = is_array($id_records) ? $id_records : explode(';', $id_records);
    $id_records = array_filter($id_records, function ($var) {
        return !empty($var);
    });
    $id_records = array_unique($id_records);

    $bulk = $structure->filepath('bulk.php');
    $bulk = empty($bulk) ? [] : include $bulk;
    $bulk = empty($bulk) ? [] : $bulk;

    if (in_array(post('op'), array_keys($bulk))) {
        redirect(ROOTDIR.'/controller.php?id_module='.$id_module, 'js');
    } else {
        // Esecuzione delle operazioni del modulo
        include $structure->filepath('actions.php');

        // Operazioni generiche per i campi personalizzati
        if (post('op') != null) {
            $query = 'SELECT `id`, `name` FROM `zz_fields` WHERE ';
            if (!empty($id_plugin)) {
                $query .= '`id_plugin` = '.prepare($id_plugin);
            } else {
                $query .= '`id_module` = '.prepare($id_module);
            }
            $customs = $dbo->fetchArray($query);

            if (!starts_with(post('op'), 'delete')) {
                $values = [];
                foreach ($customs as $custom) {
                    if (post($custom['name']) !== null) {
                        $values[$custom['id']] = post($custom['name']);
                    }
                }

                // Inserimento iniziale
                if (starts_with(post('op'), 'add')) {
                    // Informazioni di log
                    Filter::set('get', 'id_record', $id_record);

                    foreach ($values as $key => $value) {
                        $dbo->insert('zz_field_record', [
                            'id_record' => $id_record,
                            'id_field' => $key,
                            'value' => $value,
                        ]);
                    }
                }

                // Aggiornamento
                elseif (starts_with(post('op'), 'update')) {
                    foreach ($values as $key => $value) {
                        $dbo->update('zz_field_record', [
                            'value' => $value,
                        ], [
                            'id_record' => $id_record,
                            'id_field' => $key,
                        ]);
                    }
                }
            }

            // Eliminazione
            elseif (!empty($customs)) {
                $dbo->query('DELETE FROM `zz_field_record` WHERE `id_record` = '.prepare($id_record).' AND `id_field` IN ('.implode(',', array_column($customs, 'id')).')');
            }
        }
    }
}

$database->commitTransaction();
