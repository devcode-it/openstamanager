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

include_once __DIR__.'/core.php';

use Models\Note;
use Models\OperationLog;
use Models\Upload;
use Modules\Checklists\Check;
use Modules\Checklists\Checklist;
use Modules\Emails\Template;
use Notifications\EmailNotification;

if (empty($structure) || empty($structure['enabled'])) {
    exit(tr('Accesso negato'));
}

$upload_dir = base_dir().'/'.Uploads::getDirectory($id_module, $id_plugin);

$database->beginTransaction();

// Upload allegati e rimozione
if (filter('op') == 'aggiungi-allegato' || filter('op') == 'rimuovi-allegato') {
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

        //UPLOAD PER CKEDITOR
        if (filter('op') == 'aggiungi-allegato' && !empty($_FILES) && !empty($_FILES['upload']['name'])) {

            $CKEditor = get('CKEditor');
            $funcNum = get('CKEditorFuncNum');

            
            $allowed_extension = array(
                "png","jpg","jpeg"
            );

            //Maximum file limit (unit: byte)
            $max_size='2097152'; //2MB

            // Get image file extension
            $file_extension = pathinfo($_FILES["upload"]["name"], PATHINFO_EXTENSION);

            if(in_array(strtolower($file_extension),$allowed_extension) && $_FILES["upload"]['size']<$max_size){

                $upload = Uploads::upload($_FILES['upload'], [
                    'name' => filter('nome_allegato'),
                    'category' => filter('categoria'),
                    'id_module' => Modules::get('Gestione documentale')['id'],
                    'id_record' => $id_record,
                ]);

                // Upload da form
                if (!empty($funcNum) ){
                    echo '
                    <link rel="stylesheet" type="text/css" href="'.$baseurl.'/assets/dist/css/app.min.css" />
                    <script src="'.$baseurl.'/assets/dist/js/app.min.js"></script>';
                }
                
                // Creazione file fisico
                if (!empty($upload)) {
                    //flash()->info(tr('File caricato correttamente!'));

                    $id_allegato = $dbo->lastInsertedID();
                    $upload = Upload::find($id_allegato);

                    $response = [
                        'fileName' => basename($upload->filepath),
                        'uploaded' => 1,
                        'url' => $upload->filepath
                    ];

                    // Upload da form
                    if (!empty($funcNum) ){
                        echo '
                        <script type="text/javascript">
                            $(document).ready(function() {
                                window.parent.toastr.success("'.tr('Caricamento riuscito').'");
                                window.parent.CKEDITOR.tools.callFunction('.$funcNum.', "'.$baseurl.'/'.$upload->filepath.'");
                            });
                        </script>';
                    }
                    
                    // Copia-incolla
                    else {
                        echo json_encode($response);
                    }
                   
                } else {

                    //flash()->error(tr('Errore durante il caricamento del file!'));
                    echo '<script type="text/javascript">  window.parent.toastr.error("'.tr('Errore durante il caricamento del file!').'"); </script>';
                
                }

            }else{
                
                //flash()->error(tr('Estensione non permessa!'));
                echo '<script type="text/javascript">  window.parent.toastr.error("'.tr('Estensione non permessa').'"); </script>';

            }
            
            exit();
        }

        // UPLOAD
        if (filter('op') == 'aggiungi-allegato' && !empty($_FILES) && !empty($_FILES['file']['name'])) {
            $upload = Uploads::upload($_FILES['file'], [
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
        elseif (filter('op') == 'rimuovi-allegato' && filter('filename') !== null) {
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

        redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.((!empty($options['id_plugin'])) ? '#tab_'.$options['id_plugin'] : ''));
    }
}

// Download allegati
elseif (filter('op') == 'download-allegato') {
    $rs = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module='.prepare($id_module).' AND id='.prepare(filter('id')).' AND filename='.prepare(filter('filename')));

    download($upload_dir.'/'.$rs[0]['filename'], $rs[0]['original']);
} elseif (filter('op') == 'visualizza-modifica-allegato') {
    include_once base_dir().'/include/modifica_allegato.php';
}

// Modifica dati di un allegato
elseif (filter('op') == 'modifica-allegato') {
    $id_allegato = filter('id_allegato');
    $upload = Upload::find($id_allegato);

    $upload->name = post('nome_allegato');
    $upload->category = post('categoria_allegato');

    $upload->save();
}

// Modifica nome della categoria degli allegati
elseif (filter('op') == 'modifica-categoria-allegato') {
    $category = post('category');
    $name = post('name');

    $uploads = $structure->uploads($id_record)->where('category', $category);
    foreach ($uploads as $upload) {
        $upload->category = $name;
        $upload->save();
    }
}

// Validazione dati
elseif (filter('op') == 'validate') {
    // Lettura informazioni di base
    $init = $structure->filepath('init.php');
    if (!empty($init)) {
        include_once $init;
    }

    // Validazione del campo
    $validation = $structure->filepath('validation.php');
    if (!empty($validation)) {
        include_once $validation;
    }

    echo json_encode($response);

    return;
}

// Aggiunta nota interna
elseif (filter('op') == 'aggiungi-nota') {
    $contenuto = post('contenuto');
    $data_notifica = post('data_notifica') ?: null;

    $nota = Note::build($user, $structure, $id_record, $contenuto, $data_notifica);

    flash()->info(tr('Nota interna aggiunta correttamente!'));
}

// Rimozione data di notifica dalla nota interna
elseif (filter('op') == 'rimuovi-notifica-nota') {
    $id_nota = post('id_nota');
    $nota = Note::find($id_nota);

    $nota->notification_date = null;
    $nota->save();

    flash()->info(tr('Data di notifica rimossa dalla nota interna!'));
}

// Rimozione nota interna
elseif (filter('op') == 'rimuovi-nota') {
    $id_nota = post('id_nota');
    $nota = Note::find($id_nota);

    $nota->delete();

    flash()->info(tr('Nota interna aggiunta correttamente!'));
}

// Clonazione di una checklist
elseif (filter('op') == 'copia-checklist') {
    $content = post('content');
    $checklist_id = post('checklist');
    
    $users = post('assigned_users');
    $users = array_clean($users);
   
    $group_id = post('group_id');
    
    $checklist = Checklist::find($checklist_id);
    $checklist->copia($user, $id_record, $users, $group_id);
}

// Aggiunta check alla checklist
elseif (filter('op') == 'aggiungi-check') {
    $content = post('content');
    $parent_id = post('parent') ?: null;

    $users = post('assigned_users');
    $users = array_clean($users);
   
    $group_id = post('group_id');

    $check = Check::build($user, $structure, $id_record, $content, $parent_id);
    $check->setAccess($users, $group_id);
}

// Rimozione di un check della checklist
elseif (filter('op') == 'rimuovi-check') {
    $check_id = post('check_id');
    $check = Check::find($check_id);

    if (!empty($check) && $check->user->id == $user->id) {
        $check->delete();
    } else {
        flash()->error(tr('Impossibile eliminare il check!'));
    }
}

// Gestione check per le checklist
elseif (filter('op') == 'toggle-check') {
    $check_id = post('check_id');
    $check = Check::find($check_id);

    if (!empty($check) && $check->assignedUsers->pluck('id')->search($user->id) !== false) {
        $check->toggleCheck($user);
    } else {
        flash()->error(tr('Impossibile cambiare lo stato del check!'));
    }
}

// Gestione ordine per le checklist
elseif (filter('op') == 'ordina-checks') {
    $ids = explode(',', $_POST['order']);
    $order = 0;

    foreach ($ids as $id) {
        $dbo->query('UPDATE `zz_checks` SET `order` = '.prepare($order).' WHERE id = '.prepare($id));
        ++$order;
    }
}

// Inizializzazione email
elseif (post('op') == 'send-email') {
    $template = Template::find(post('template'));

    $mail = \Modules\Emails\Mail::build($user, $template, $id_record);

    // Rimozione allegati predefiniti
    $mail->resetPrints();

    // Destinatari
    $receivers = array_clean(post('destinatari'));
    $types = post('tipo_destinatari');
    foreach ($receivers as $key => $receiver) {
        $mail->addReceiver($receiver, $types[$key]);
    }

    // Contenuti
    $mail->subject = post('subject');
    $mail->content = $_POST['body']; // post('body', true);

    // Conferma di lettura
    $mail->read_notify = post('read_notify');

    // Stampe da allegare
    $prints = post('prints');
    foreach ($prints as $print) {
        $mail->addPrint($print);
    }

    // Allegati originali
    $files = post('uploads');
    foreach ($files as $file) {
        $mail->addUpload($file);
    }

    // Salvataggio email nella coda di invio
    $mail->save();

    // Invio mail istantaneo
    $email = EmailNotification::build($mail);
    $email_success = $email->send();

    if ($email_success) {
        OperationLog::setInfo('id_email', $mail->id);
        flash()->info(tr('Email inviata correttamente!'));
    } else {
        $mail->delete();
        flash()->error(tr('Errore durante l\'invio email! Verifica i parametri dell\'account SMTP utilizzato.'));
    }
} elseif (filter('op') == 'aggiorna_colonne') {
    include_once base_dir().'/include/colonne.php';
} elseif (filter('op') == 'toggle_colonna') {
    $visible = filter('visible');
    $id_riga = filter('id_vista');

    $dbo->query('UPDATE `zz_views` SET `visible` = '.prepare($visible).' WHERE id = '.prepare($id_riga));
} elseif (filter('op') == 'ordina_colonne') {
    $order = explode(',', post('order', true));

    foreach ($order as $i => $id_riga) {
        $dbo->query('UPDATE `zz_views` SET `order` = '.prepare($i).' WHERE id='.prepare($id_riga));
    }
} elseif (filter('op') == 'visualizza_righe_riferimenti') {
    include_once base_dir().'/include/riferimenti/riferimenti.php';
} elseif (filter('op') == 'visualizza_righe_documento') {
    include_once base_dir().'/include/riferimenti/righe_documento.php';
} elseif (filter('op') == 'salva_riferimento_riga') {
    $database->insert('co_riferimenti_righe', [
        'source_type' => filter('source_type'),
        'source_id' => filter('source_id'),
        'target_type' => filter('target_type'),
        'target_id' => filter('target_id'),
    ]);
} elseif (filter('op') == 'rimuovi_riferimento_riga') {
    $database->delete('co_riferimenti_righe', [
        'id' => filter('idriferimento'),
    ]);
}

// Inclusione di eventuale plugin personalizzato
if (!empty($structure['script'])) {
    $path = $structure->getEditFile();
    if (!empty($path)) {
        include $path;
    }

    $database->commitTransaction();

    return;
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
    if (!empty(post('id_records'))){
        $id_records = post('id_records');
        $id_records = is_array($id_records) ? $id_records : explode(';', $id_records);
        $id_records = array_clean($id_records);
        $id_records = array_unique($id_records);
    }

    $bulk = $structure->filepath('bulk.php');
    $bulk = empty($bulk) ? [] : include $bulk;
    $bulk = empty($bulk) ? [] : $bulk;

    if (in_array(post('op'), array_keys($bulk))) {
        redirect(base_path().'/controller.php?id_module='.$id_module, 'js');
    } else {
        // Esecuzione delle operazioni del modulo
        ($include_file = $structure->filepath('actions.php')) ? include $include_file : null;

        // Operazioni generiche per i campi personalizzati
        if (post('op') != null) {
            $custom_where = !empty($id_plugin) ? '`id_plugin` = '.prepare($id_plugin) : '`id_module` = '.prepare($id_module);

            $query = 'SELECT `id`, `html_name` AS `name` FROM `zz_fields` WHERE '.$custom_where;
            $customs = $dbo->fetchArray($query);

            if (!string_starts_with(post('op'), 'delete')) {
                $values = [];
                foreach ($customs as $custom) {
                    if (post($custom['name']) !== null) {
                        $values[$custom['id']] = post($custom['name']);
                    }
                }

                // Inserimento iniziale
                if (string_starts_with(post('op'), 'add')) {
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
                elseif (string_starts_with(post('op'), 'update')) {
                    $query = 'SELECT `zz_field_record`.`id_field` FROM `zz_field_record` JOIN `zz_fields` ON `zz_fields`.`id` = `zz_field_record`.`id_field` WHERE id_record = '.prepare($id_record).' AND '.$custom_where;
                    $customs_present = $dbo->fetchArray($query);
                    $customs_present = array_column($customs_present, 'id_field');

                    foreach ($values as $key => $value) {
                        if (in_array($key, $customs_present)) {
                            $dbo->update('zz_field_record', [
                                'value' => $value,
                            ], [
                                'id_record' => $id_record,
                                'id_field' => $key,
                            ]);
                        } else {
                            $dbo->insert('zz_field_record', [
                                'id_record' => $id_record,
                                'id_field' => $key,
                                'value' => $value,
                            ]);
                        }
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
