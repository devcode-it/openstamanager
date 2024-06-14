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

use Modules\Articoli\CausaleMovimento as Causale;

switch (filter('op')) {
    case 'update':
        $nome = post('nome');
        $descrizione = post('descrizione');
        if (isset($descrizione)) {
            $causale_new = Causale::where('id', '=', (new Causale())->getByField('title', $nome))->orWhere('name', $nome)->where('id', '!=', $id_record)->first();
            if (empty($causale_new)) {
                $causale->tipo_movimento = post('tipo_movimento');
                $causale->setTranslation('title', $nome);
                $causale->setTranslation('description', $descrizione);
                $causale->save();
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una causale con nome _NAME_.", [
                    '_NAME_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');
        if (empty(Causale::where('id', '=', (new Causale())->getByField('title', $descrizione))->where('id', '!=', $id_record)->first())) {
            $causale = Causale::build();
            $causale->tipo_movimento = post('tipo_movimento');
            $causale->save();
            $id_record = $dbo->lastInsertedID();
            $causale->setTranslation('title', post('nome'));
            $causale->setTranslation('description', $descrizione);
            $causale->save();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $descrizione]);
            }

            flash()->info(tr('Aggiunta nuova causale con nome _NAME_', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr("E' già presente una causale con nome _NAME_.", [
                '_NAME_' => $descrizione,
            ]));
        }
        break;

    case 'delete':
        if (!empty($id_record)) {
            $dbo->query('DELETE FROM `mg_causali_movimenti` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'movimento predefinito',
            ]));
        }

        break;
}
