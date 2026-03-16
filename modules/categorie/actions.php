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
use Modules\Articoli\Categoria;

switch (filter('op')) {
    case 'update':
        $nome = filter('nome_add') ?: filter('nome');
        $nota = filter('nota_add') ?: filter('nota');
        $colore = filter('colore_add') ?: filter('colore');
        $id_original = filter('id_original') ?: null;
        $is_articolo = (filter('is_articolo_add') ?: filter('is_articolo')) ?: 0;
        $is_impianto = (filter('is_impianto_add') ?: filter('is_impianto')) ?: 0;

        // Verifica che almeno uno dei due flag sia selezionato
        if ($is_articolo == 0 && $is_impianto == 0) {
            flash()->error(tr('È necessario selezionare almeno una delle due opzioni: Articolo o Impianto.'));
            break;
        }

        // Verifica se esiste già una categoria con lo stesso nome
        $categoria_esistente = Categoria::where('name', $nome)->where('id', '!=', $id_record)->where('parent', '=', $id_original)->first();

        if (!empty($categoria_esistente) && $categoria_esistente != $id_record) {
            // Mostra un messaggio di errore con link alla categoria esistente
            $message = tr('Esiste già una categoria con il nome _NOME_', [
                '_NOME_' => '"'.$nome.'"',
            ]);

            $link = Modules::link('Categorie', $categoria_esistente->id, $nome);
            flash()->error($message.': '.$link);
        } else {
            if (isset($nome) && isset($nota) && isset($colore)) {
                $categoria->colore = $colore;
                $categoria->parent = $id_original ?: null;
                $categoria->is_articolo = $is_articolo;
                $categoria->is_impianto = $is_impianto;
                $categoria->name = $nome;
                $categoria->save();

                $categoria->setTranslation('title', $nome);
                $categoria->setTranslation('note', $nota);
                // Aggiorna i flag delle sottocategorie se è un parent
                $subcategorie = Categoria::where('parent', '=', $id_record)->get();
                if (!empty($subcategorie)) {
                    foreach ($subcategorie as $sub) {
                        $sub->is_articolo = $is_articolo;
                        $sub->is_impianto = $is_impianto;
                        $sub->save();
                    }

                    flash()->info(tr('Salvataggio completato! Aggiornate anche _NUM_ sottocategorie.', [
                        '_NUM_' => count($subcategorie),
                    ]));
                } else {
                    flash()->info(tr('Salvataggio completato!'));
                }
            } else {
                flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
            }
        }

        // Redirect alla categoria se si sta modificando una sottocategoria
        if (!empty($id_original)) {
            $database->commitTransaction();
            redirect_url(base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'add':
        $nome = filter('nome_add');
        $nota = filter('nota_add');
        $colore = filter('colore_add');
        $id_original = filter('id_original') ?: null;
        $is_articolo = (filter('is_articolo_add') ?: filter('is_articolo')) ?: 0;
        $is_impianto = (filter('is_impianto_add') ?: filter('is_impianto')) ?: 0;

        // Verifica che almeno uno dei due flag sia selezionato
        if ($is_articolo == 0 && $is_impianto == 0) {
            flash()->error(tr('È necessario selezionare almeno una delle due opzioni: Articolo o Impianto.'));
            break;
        }

        // Verifica se esiste già una categoria con lo stesso nome
        $categoria_new = Categoria::where('name', $nome)->where('id', '!=', $id_record)->where('parent', '=', $id_original)->first();

        if (!empty($categoria_new)) {
            // Mostra un messaggio di errore con link alla categoria esistente
            $message = tr('Esiste già una categoria con il nome _NOME_', [
                '_NOME_' => '"'.$nome.'"',
            ]);

            $link = Modules::link('Categorie', $categoria_new->id, $categoria_new->getTranslation('title'));
            flash()->error($message.': '.$link);
        } else {
            $categoria = Categoria::build($colore, $nome);
            $id_record = $dbo->lastInsertedID();
            $categoria->parent = $id_original;
            $categoria->is_articolo = $is_articolo;
            $categoria->is_impianto = $is_impianto;
            $categoria->save();

            $categoria->setTranslation('note', $nota);
            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        }

        if (!empty($id_original)) {
            $database->commitTransaction();
            redirect_url(base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }
        echo json_encode(['id' => $id_record, 'text' => $nome]);

        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE (`id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT `id` FROM `zz_categorie` WHERE `parent`='.prepare($id).')) AND `deleted_at` IS NULL') == 0) {
            $dbo->delete('zz_categorie', ['id' => $id]);

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni articoli collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;
}
