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
use Modules\Ubicazioni;
//use Modules\Articoli\Categoria;

switch (filter('op')) {
    case 'update':
        $u_label = filter('u_label');
		//$u_label_info = filter('u_label_info');
        $title = filter('title');
		$colore = filter('colore');
        $notes = filter('notes');
		$u_tags = filter('u_tags');
		//$u_id = filter('id');

        if (isset($u_label)) {
            if ($dbo->fetchNum('SELECT * FROM `mg_ubicazioni` WHERE `mg_ubicazioni`.`u_label`='.prepare($u_label).' AND `mg_ubicazioni`.`id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `mg_ubicazioni` LEFT JOIN `mg_ubicazioni_lang` ON (`mg_ubicazioni`.`id` = `mg_ubicazioni_lang`.`id_record` AND `mg_ubicazioni_lang`.`id_lang` = '.prepare(parameter: Models\Locale::getDefault()->id).') 
                    SET `mg_ubicazioni`.`u_label`='.prepare($u_label).',
					    `mg_ubicazioni`.`u_tags`='.prepare($u_tags).',
						`mg_ubicazioni`.`colore`='.prepare($colore).',
						`mg_ubicazioni_lang`.`title`='.prepare($title).',
						`mg_ubicazioni_lang`.`notes`='.prepare($notes).' 
					WHERE `mg_ubicazioni`.`id`='.prepare($id_record));
				//$ubicazione->colore = $colore;
				//$categoria->parent = $id_original ?: null;
				//$ubicazione->setTranslation('title', $title);
				//$ubicazione->setTranslation('notes', $notes);
				//$ubicazione->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
                    '_TYPE_' => 'ubicazione',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    //aggiornato con l'aggiunta di mg_ubicazioni_lang
    case 'add':
        $u_label = filter('u_label');
		//$u_label_info = filter('u_label_info');
        $title = filter('title');
		$colore = filter('colore');
        $notes = filter('notes');
		$u_tags = filter('u_tags');
		//$u_id = filter('id');

        if (isset($u_label)) {
            if ($dbo->fetchNum('SELECT * FROM `mg_ubicazioni` WHERE `u_label`='.prepare($u_label)) == 0) {
                $dbo->query('INSERT INTO `mg_ubicazioni` (`u_label`,`u_tags`,`colore`) VALUES ('.prepare($u_label).','.prepare($u_tags).','.prepare($colore).')');
                $id_record = $dbo->lastInsertedID();
                $dbo->query('INSERT INTO `mg_ubicazioni_lang` (`id_lang`,`id_record`,`title`,`notes`) VALUES ('.prepare(parameter: Models\Locale::getDefault()->id).','.prepare($id_record).','.prepare($title).','.prepare($notes).')');

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $u_label, 'text' => $u_label]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'ubicazione',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso valore.", [
                    '_TYPE_' => 'ubicazione',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        //$righe = $dbo->fetchNum('SELECT `id` FROM `co_righe_documenti` WHERE `um`='.prepare($record['valore']).'
          //  UNION SELECT `id` FROM `dt_righe_ddt` WHERE `um`='.prepare($record['valore']).'
          //  UNION SELECT `id` FROM `or_righe_ordini` WHERE `um`='.prepare($record['valore']).'
          //  UNION SELECT `id` FROM `co_righe_contratti` WHERE `um`='.prepare($record['valore']).'
          //  UNION SELECT `id` FROM `mg_articoli` WHERE `um`='.prepare($record['valore']).'
          //  UNION SELECT `id` FROM `co_righe_preventivi` WHERE `um`='.prepare($record['valore']));

        //aggiornato con il delete anche dei record mg_ubicazione_lang
        if (!empty($id_record)) {
            $dbo->query('DELETE FROM `mg_ubicazioni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'ubicazione',
            ]));
            $dbo->query('DELETE FROM `mg_ubicazioni_lang` WHERE `id_record`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'ubicazione_lang',
            ]));
        } else {
            flash()->error(tr('Errore cancellazione di questa ubicazione.'));
        }

        break;
}
