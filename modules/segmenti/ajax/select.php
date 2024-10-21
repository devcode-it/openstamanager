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
use Modules\Segmenti\Segmento;

switch ($resource) {
    case 'segmenti':
        $user = Auth::user();
        $id_module = $superselect['id_module'];
        $is_fiscale = $superselect['is_fiscale'];
        $is_sezionale = $superselect['is_sezionale'];
        $for_fe = $superselect['for_fe'];
        $escludi_id = $superselect['escludi_id'];
        $tipo = $dbo->fetchOne('SELECT * FROM fe_tipi_documento WHERE codice = '.prepare($superselect['tipo']));
        $predefined_accredito = Segmento::where('predefined_accredito', 1)->where('id_module', $id_module)->first();
        $predefined_addebito = Segmento::where('predefined_addebito', 1)->where('id_module', $id_module)->first();

        if (isset($id_module)) {
            $query = 'SELECT `zz_segments`.`id`, `zz_segments_lang`.`title` AS descrizione FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `zz_group_segment` ON `zz_segments`.`id` = `zz_group_segment`.`id_segment` |where| ORDER BY `title` ASC';

            $where[] = '`zz_segments`.`id_module` = '.prepare($id_module);
            $where[] = '`zz_group_segment`.`id_gruppo` = '.prepare($user->idgruppo);

            if ($is_fiscale) {
                $where[] = '`zz_segments`.`is_fiscale` = '.prepare($is_fiscale);
            }

            if ($is_sezionale) {
                $where[] = '`zz_segments`.`is_sezionale` = '.prepare($is_sezionale);
            }

            if ($for_fe) {
                $where[] = '`zz_segments`.`for_fe` = '.prepare($for_fe);
            }

            if ($escludi_id) {
                $where[] = '`zz_segments`.`id` != '.prepare($escludi_id);
            }

            foreach ($elements as $element) {
                $filter[] = '`zz_segments`.`id`='.prepare($element);
            }

            if ($tipo['is_autofattura']) {
                $where[] = '`zz_segments`.`autofatture` = 1';
            }

            if ($tipo['is_nota_credito'] && $predefined_accredito) {
                $where[] = '`zz_segments`.`predefined_accredito` = 1';
            }

            if ($tipo['is_nota_debito'] && $predefined_addebito) {
                $where[] = '`zz_segments`.`predefined_addebito` = 1';
            }

            if (!empty($search)) {
                $search_fields[] = '`zz_segments_lang`.`title` LIKE '.prepare('%'.$search.'%');
            }

        }

        break;
}
