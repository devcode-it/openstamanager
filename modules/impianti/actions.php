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

use Models\Module;
use Modules\Checklists\Check;
use Modules\Impianti\Impianto;

$op = post('op');

$upload_dir = base_dir().'/files/'.Module::where('name', 'Impianti')->first()->directory;
$id_modulo_categorie_impianti = Module::where('name', 'Categorie')->first()->id;

switch ($op) {
    // Aggiorno informazioni di base impianto
    case 'update':
        $matricola = post('matricola');

        if (!empty($matricola)) {
            $dbo->update('my_impianti', [
                'idanagrafica' => post('idanagrafica'),
                'nome' => post('nome'),
                'matricola' => $matricola,
                'id_categoria' => post('id_categoria') ?: null,
                'id_sottocategoria' => post('id_sottocategoria') ?: null,
                'id_marca' => post('id_marca') ?: null,
                'id_modello' => post('id_modello') ?: null,
                'descrizione' => post('descrizione'),
                'note' => post('note'),
                'idsede' => post('idsede'),
                'data' => post('data') ?: null,
                'proprietario' => post('proprietario'),
                'palazzo' => post('palazzo'),
                'ubicazione' => post('ubicazione'),
                'idtecnico' => post('idtecnico'),
                'scala' => post('scala'),
                'piano' => post('piano'),
                'interno' => post('interno'),
                'occupante' => post('occupante'),
            ], ['id' => $id_record]);

            flash()->info(tr('Informazioni salvate correttamente!'));

            // Upload file
            if (!empty($_FILES) && !empty($_FILES['immagine']['name'])) {
                $upload = Uploads::upload($_FILES['immagine'], [
                    'name' => 'Immagine',
                    'category' => 'Immagini',
                    'id_module' => $id_module,
                    'id_record' => $id_record,
                    'key' => 'cover',
                ], [
                    'thumbnails' => true,
                ]);

                if (empty($upload)) {
                    flash()->warning(tr('Errore durante il caricamento del file in _DIR_!', [
                        '_DIR_' => $upload_dir,
                    ]));
                }
            }

            // Eliminazione file
            if (!empty(post('delete_immagine'))) {
                $impianto = Impianto::find($id_record);
                if (!empty($impianto->immagine_upload)) {
                    $impianto->immagine_upload->delete();
                }
            }
        }
        break;

        // Aggiungo impianto
    case 'add':
        $matricola = post('matricola');
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');
        $idtecnico = post('idtecnico');
        $idsede = post('idsede');
        $id_categoria = post('id_categoria');
        $id_sottocategoria = post('id_sottocategoria');
        $id_marca = post('id_marca');
        $id_modello = post('id_modello');

        if (!empty($matricola)) {
            $dbo->insert('my_impianti', [
                'matricola' => $matricola,
                'idanagrafica' => $idanagrafica,
                'nome' => $nome,
                'data' => date('Y-m-d'),
                'idtecnico' => $idtecnico ?: 0,
                'idsede' => $idsede ?: 0,
                'id_categoria' => $id_categoria ?: null,
                'id_sottocategoria' => $id_sottocategoria ?: null,
                'id_marca' => $id_marca ?: null,
                'id_modello' => $id_modello ?: null,
            ]);

            $id_record = $dbo->lastInsertedID();

            $checks_categoria = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_modulo_categorie_impianti).' AND id_record = '.prepare($id_categoria));
            foreach ($checks_categoria as $check_categoria) {
                $id_parent_new = null;
                if ($check_categoria['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_categoria['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
                }
                $check = Check::build($user, $structure, $id_record, $check_categoria['content'], $id_parent_new, $check_categoria['is_titolo'], $check_categoria['order']);
                $check->id_plugin = null;
                $check->note = $check_categoria['note'];
                $check->id_immagine = $check_categoria['id_immagine'];
                $check->save();

                // Riporto anche i permessi della check
                $users = [];
                $utenti = $dbo->table('zz_check_user')->where('id_check', $check_categoria['id'])->get();
                foreach ($utenti as $utente) {
                    $users[] = $utente->id_utente;
                }
                $check->setAccess($users, null);
            }

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $matricola.' - '.$nome]);
            }

            flash()->info(tr('Aggiunto nuovo impianto!'));
        }

        break;

        // Carica i campi da compilare del componente
    case 'load_componente':
        $filename = post('filename');
        $idarticolo = post('idarticolo');

        // Se è stato specificato un idarticolo, carico il file .ini dal campo `contenuto` di quell'idarticolo
        $rs = $dbo->fetchArray('SELECT `contenuto`, `componente_filename` FROM `mg_articoli` WHERE `id`='.prepare($idarticolo));

        // Se i campi da caricare sono del componente già salvato leggo dal campo `contenuto`...
        if ($rs[0]['componente_filename'] == $filename) {
            $contenuto = $rs[0]['contenuto'];
        }

        // ...altrimenti carico dal file .ini
        elseif (file_exists(base_dir().'/files/impianti/'.$filename)) {
            $contenuto = file_get_contents(base_dir().'/files/impianti/'.$filename);
        }

        crea_form_componente($contenuto);

        break;

        // Duplica impianto
    case 'copy':
        $database->beginTransaction();
        $dbo->query('CREATE TEMPORARY TABLE tmp SELECT * FROM my_impianti WHERE id= '.prepare($id_record));
        $dbo->query('ALTER TABLE tmp DROP id');
        $dbo->query('INSERT INTO my_impianti SELECT NULL,tmp. * FROM tmp');
        $id_record = $dbo->lastInsertedID();
        $dbo->query('DROP TEMPORARY TABLE tmp');

        $dbo->query('UPDATE my_impianti SET matricola = CONCAT (matricola, " (copia)") WHERE id = '.prepare($id_record));

        flash()->info(tr('Impianto duplicato correttamente!'));

        break;

        // Rimuovo impianto e scollego tutti i suoi componenti
    case 'delete':
        $dbo->query('DELETE FROM my_impianti WHERE id='.prepare($id_record));

        flash()->info(tr('Impianto e relativi componenti eliminati!'));
        break;

    case 'sync_checklist':
        Check::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        $checks_categoria = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_modulo_categorie_impianti).' AND id_record = '.prepare(post('id_categoria')));
        foreach ($checks_categoria as $check_categoria) {
            $id_parent_new = null;
            if ($check_categoria['id_parent']) {
                $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_categoria['id_parent']]);
                $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
            }
            $check = Check::build($user, $structure, $id_record, $check_categoria['content'], $id_parent_new, $check_categoria['is_titolo'], $check_categoria['order']);
            $check->id_plugin = null;
            $check->note = $check_categoria['note'];
            $check->id_immagine = $check_categoria['id_immagine'];
            $check->save();
        }
        flash()->info(tr('Checklist importate correttamente!'));

        break;

    // Creazione token OTP per l'impianto
    case 'create_otp_token':
        $id_module = post('id_module');
        $id_record = post('id_record');
        $descrizione = post('descrizione');
        $tipo_accesso = post('tipo_accesso');
        $email = post('email');
        $valido_dal = post('valido_dal');
        $valido_al = post('valido_al');

        try {
            // Debug: log dei dati ricevuti
            error_log("OTP Token Creation - Dati ricevuti: " . json_encode([
                'id_module' => $id_module,
                'id_record' => $id_record,
                'descrizione' => $descrizione,
                'tipo_accesso' => $tipo_accesso,
                'email' => $email,
                'valido_dal' => $valido_dal,
                'valido_al' => $valido_al
            ]));
            
            // Genera token sicuro
            $token = bin2hex(random_bytes(32));
            
            // Prepara i dati per l'inserimento
            $data = [
                'token' => $token,
                'descrizione' => $descrizione,
                'tipo_accesso' => $tipo_accesso,
                'id_module_target' => $id_module,
                'id_record_target' => $id_record,
                'email' => !empty($email) ? $email : '',
                'permessi' => 'r',
                'enabled' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            // Aggiungi date di validità se specificate
            if (!empty($valido_dal)) {
                $data['valido_dal'] = date('Y-m-d H:i:s', strtotime($valido_dal));
            }
            if (!empty($valido_al)) {
                $data['valido_al'] = date('Y-m-d H:i:s', strtotime($valido_al));
            }
            
            // Inserisci il token nel database
            error_log("OTP Token Creation - Dati da inserire: " . json_encode($data));
            $dbo->insert('zz_otp_tokens', $data);
            $token_id = $dbo->lastInsertedID();
            error_log("OTP Token Creation - Token inserito con ID: " . $token_id);
            
            // Prepara la risposta
            $otp_url = base_url().'/?token='.$token;
            
            $response = [
                'success' => true,
                'message' => tr('Token OTP creato con successo'),
                'token_info' => [
                    'id' => $token_id,
                    'token' => $token,
                    'descrizione' => $descrizione,
                    'url' => $otp_url,
                    'tipo_accesso' => $tipo_accesso,
                    'valido_dal' => !empty($valido_dal) ? date('d/m/Y', strtotime($valido_dal)) : tr('Nessuna scadenza'),
                    'valido_al' => !empty($valido_al) ? date('d/m/Y', strtotime($valido_al)) : tr('Nessuna scadenza')
                ]
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => tr('Errore durante la creazione del token: ').$e->getMessage()
            ];
            
            echo json_encode($response);
        }
        
        $dbo->query("COMMIT");
        exit;
        break;
}

// Operazioni aggiuntive per l'immagine
if (filter('op') == 'rimuovi-allegato' && filter('nome_allegato') == 'Immagine') {
    $impianto = Impianto::find($id_record);
    if (!empty($impianto->immagine_upload)) {
        $impianto->immagine_upload->delete();
    }
} elseif (filter('op') == 'aggiungi-allegato' && filter('nome_allegato') == 'Immagine') {
    // L'upload è già stato gestito dal FileManager con key='cover'
}
