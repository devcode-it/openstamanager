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

use Modules\Articoli\Marca;

switch (post('op')) {
    // Aggiorno informazioni di base marca
    case 'update':
        $nome = filter('name');
        $link = filter('link');
        $id_original = filter('id_original') ?: null;
        $is_articolo = (filter('is_articolo_add') ?: filter('is_articolo')) ?: 0;
        $is_impianto = (filter('is_impianto_add') ?: filter('is_impianto')) ?: 0;

        // Verifica che almeno uno dei due flag sia selezionato
        if ($is_articolo == 0 && $is_impianto == 0) {
            flash()->error(tr('È necessario selezionare almeno una delle due opzioni: Articolo o Impianto.'));
            break;
        }
        // Verifica se esiste già una marca con lo stesso nome
        $marca_esistente = Marca::where('name', $nome)->where('id', '!=', $id_record)->first();

        if (!empty($marca_esistente) && $marca_esistente != $id_record) {
            // Mostra un messaggio di errore con link alla marca esistente
            $message = tr('Esiste già una marca con il nome _NOME_', [
                '_NOME_' => '"'.$nome.'"',
            ]);

            $link = Modules::link('Marche', $marca_esistente->id, $nome);
            flash()->error($message.': '.$link);
            break;
        }

        if (isset($nome)) {
            $marca->link = $link;
            $marca->name = $nome;
            $marca->parent = $id_original ?: null;
            $marca->is_articolo = $is_articolo;
            $marca->is_impianto = $is_impianto;
            $marca->save();
        }

        // Aggiorna i flag delle sottocategorie se è un parent
        $modello = Marca::where('parent', '=', $id_record)->get();
        if (!empty($modello)) {
            foreach ($modello as $mod) {
                $mod->is_articolo = $is_articolo;
                $mod->is_impianto = $is_impianto;
                $mod->save();
            }

            flash()->info(tr('Salvataggio completato! Aggiornati anche _NUM_ modelli.', [
                '_NUM_' => count($modello),
            ]));
        } else {
            flash()->info(tr('Salvataggio completato!'));
        }

        // Upload file
        if (!empty($_FILES) && !empty($_FILES['immagine']['name'])) {
            $upload = Uploads::upload($_FILES['immagine'], [
                'name' => 'Immagine',
                'category' => 'Immagini',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ], [
                'thumbnails' => true,
            ]);
            $filename = $upload->filename;

            if (!empty($filename)) {
                $dbo->update('zz_marche', [
                    'immagine' => $filename,
                ], [
                    'id' => $id_record,
                ]);
            } else {
                flash()->warning(tr("Errore durante il caricamento dell'immagine!"));
            }
        }

        // Eliminazione file
        if (post('delete_immagine')) {
            Uploads::delete($record['immagine'], [
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            $dbo->update('zz_marche', [
                'immagine' => null,
            ], [
                'id' => $id_record,
            ]);
        }

        if (!empty($id_original)) {
            $database->commitTransaction();
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        flash()->info(tr('Salvataggio completato!'));

        break;

        // Aggiungo marca
    case 'add':
        $nome = filter('name');
        $link = filter('link');
        $id_original = filter('id_original') ?: null;
        $is_articolo = (filter('is_articolo_add') ?: filter('is_articolo')) ?: 0;
        $is_impianto = (filter('is_impianto_add') ?: filter('is_impianto')) ?: 0;

        // Verifica che almeno uno dei due flag sia selezionato
        if ($is_articolo == 0 && $is_impianto == 0) {
            flash()->error(tr('È necessario selezionare almeno una delle due opzioni: Articolo o Impianto.'));
            break;
        }

        // Verifica se esiste già una marca con lo stesso nome
        $marca_new = Marca::where('name', $nome)->where('id', '!=', $id_record)->first();

        if (!empty($marca_new)) {
            // Mostra un messaggio di errore con link alla marca esistente
            $message = tr('Esiste già una marca con il nome _NOME_', [
                '_NOME_' => '"'.$nome.'"',
            ]);

            $link = Modules::link('Marche', $marca_new->id, $marca_new->name);
            flash()->error($message.': '.$link);
        } else {
            $marca = Marca::build($nome);
            $marca->parent = $id_original;
            $marca->is_articolo = $is_articolo;
            $marca->is_impianto = $is_impianto;
            $marca->link = $link;
            $marca->save();
            $id_record = $marca->id;

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'marca',
            ]));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
        } else {
            // Redirect alla marca se si sta aggiungendo una modello
            $database->commitTransaction();
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

        // Rimuovo marca
    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE (`id_marca`='.prepare($id).' OR `id_modello`='.prepare($id).'  OR `id_modello` IN (SELECT `id` FROM `zz_marche` WHERE `parent`='.prepare($id).')) AND `deleted_at` IS NULL') == 0) {
            $dbo->query('DELETE FROM `zz_marche` WHERE `id`='.prepare($id));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'marca',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni articoli collegati a questa marca. Impossibile eliminarla.'));
        }

        break;
}
