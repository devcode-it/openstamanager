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

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $tipologia = filter('tipologia');
        $causale = filter('causale');
        $percentuale = filter('percentuale');
        $percentuale_imponibile = filter('percentuale_imponibile');

        if (isset($descrizione) && isset($percentuale) && isset($percentuale_imponibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_ritenuta_contributi` WHERE `descrizione`='.prepare($descrizione).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query('UPDATE `co_ritenuta_contributi` SET `descrizione`='.prepare($descrizione).', `tipologia`='.prepare($tipologia).', `causale`='.prepare($causale).', `percentuale`='.prepare($percentuale).', `percentuale_imponibile`='.prepare($percentuale_imponibile).' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!", [
                    '_TYPE_' => "ritenuta d'acconto",
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $tipologia = filter('tipologia');
        $causale = filter('causale');
        $percentuale = filter('percentuale');
        $percentuale_imponibile = filter('percentuale_imponibile');

        if (isset($descrizione) && isset($percentuale) && isset($percentuale_imponibile)) {
            if ($dbo->fetchNum('SELECT * FROM `co_ritenuta_contributi` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->query('INSERT INTO `co_ritenuta_contributi` (`descrizione`, `tipologia`, `causale`, `percentuale`, `percentuale_imponibile`) VALUES ('.prepare($descrizione).', '.prepare($tipologia).', '.prepare($causale).', '.prepare($percentuale).', '.prepare($percentuale_imponibile).')');
                $id_record = $dbo->lastInsertedID();

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => "ritenuta d'acconto",
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con la stessa descrizione!", [
                    '_TYPE_' => "ritenuta d'acconto",
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':
        if (!empty($id_record)) {
            $dbo->query('DELETE FROM `co_ritenuta_contributi` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => "ritenuta d'acconto",
            ]));
        }

        break;
}
