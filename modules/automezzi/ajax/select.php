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

include_once __DIR__.'/../../../core.php';

use Modules\Anagrafiche\Tipo;

switch ($resource) {
    case 'tecnici_automezzo':
        $tipologia = Tipo::where('name', 'Tecnico')->first()->id;

        $query = "SELECT DISTINCT `an_anagrafiche`.`idanagrafica` AS id, CONCAT(`ragione_sociale`, IF(`citta` IS NULL OR `citta` = '', '', CONCAT(' (', `citta`, ')')), IF(`an_anagrafiche`.`deleted_at` IS NULL, '', ' (".tr('eliminata').")'),' - ', `an_anagrafiche`.`codice`) AS descrizione, `idtipointervento_default` FROM `an_anagrafiche` INNER JOIN (`an_tipianagrafiche_anagrafiche` INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')) ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
        LEFT JOIN `zz_users` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
        |where| ORDER BY `ragione_sociale`';

        foreach ($elements as $element) {
            $filter[] = '`an_anagrafiche`.`idanagrafica`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`an_anagrafiche`.`deleted_at` IS NULL';

            // come tecnico posso aprire viaggi solo a mio nome
            $user = AuthOSM::user();
            if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
                $where[] = '`an_anagrafiche`.`idanagrafica`='.$user['idanagrafica'];
            }

            if (!empty($superselect['idautomezzo'])) {
                $where[] = '`zz_users`.`id` IN (SELECT `id_user` FROM `zz_user_sedi` WHERE `idsede`='.prepare($superselect['idautomezzo']).')';
            }

            // Filtro per tipo anagrafica "Tecnico"
            $where[] = '`an_tipianagrafiche`.`id`='.prepare($tipologia);
        }

        if (!empty($search)) {
            $search_fields[] = '`ragione_sociale` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`citta` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`provincia` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`piva` LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '`an_anagrafiche`.`codice_fiscale` LIKE '.prepare('%'.$search.'%');
        }

        break;
}
