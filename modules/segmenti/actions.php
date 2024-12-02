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
use Models\Group;

switch (post('op')) {
    case 'update':
        $pattern = string_contains(post('pattern'), '#') ? post('pattern') : '####';
        $predefined = post('predefined');
        $module = post('module');

        if (empty(Modules::getSegments($module))) {
            $predefined = 1;
        }

        if ($predefined) {
            $dbo->query('UPDATE `zz_segments` SET `predefined` = 0 WHERE `id_module` = '.prepare($module));
        }

        $predefined_accredito = post('predefined_accredito');
        if ($predefined_accredito) {
            $dbo->query('UPDATE `zz_segments` SET `predefined_accredito` = 0 WHERE `id_module` = '.prepare($module));
        }

        $predefined_addebito = post('predefined_addebito');
        if ($predefined_addebito) {
            $dbo->query('UPDATE `zz_segments` SET `predefined_addebito` = 0 WHERE `id_module` = '.prepare($module));
        }

        $dbo->update('zz_segments', [
            'id_module' => $module,
            'clause' => $_POST['clause'],
            'pattern' => $pattern,
            'note' => post('note'),
            'dicitura_fissa' => post('dicitura_fissa'),
            'position' => post('position'),
            'predefined' => $predefined,
            'is_fiscale' => post('is_fiscale'),
            'predefined_accredito' => $predefined_accredito,
            'predefined_addebito' => $predefined_addebito,
            'autofatture' => post('autofatture'),
            'for_fe' => post('for_fe'),
        ], ['id' => $id_record]);

        $dbo->update('zz_segments_lang', [
            'title' => post('name'),
        ], ['id_record' => $id_record, 'id_lang' => Models\Locale::getDefault()->id]);

        // Aggiornamento dei permessi relativi
        $dbo->sync('zz_group_segment', ['id_segment' => $id_record], ['id_gruppo' => (array) post('gruppi')]);

        flash()->info(tr('Modifiche salvate correttamente'));

        break;

    case 'add':
        $pattern = string_contains(post('pattern'), '#') ? post('pattern') : '####';
        $predefined = post('predefined');
        $module = post('module');

        if (empty(Modules::getSegments($module))) {
            $predefined = 1;
        }

        if ($predefined) {
            $dbo->query('UPDATE `zz_segments` SET `predefined` = 0 WHERE `id_module` = '.prepare($module));
        }

        $dbo->insert('zz_segments', [
            'id_module' => $module,
            'clause' => '1=1',
            'pattern' => $pattern,
            'note' => post('note'),
            'predefined' => $predefined ?: 0,
            'is_sezionale' => post('is_sezionale_add'),
        ]);

        $id_record = $dbo->lastInsertedID();

        $dbo->insert('zz_segments_lang', [
            'title' => post('name'),
            'id_record' => $id_record,
            'id_lang' => Models\Locale::getDefault()->id,
        ]);

        // Aggiunta permessi segmento
        $gruppi = Group::get();
        $array = [];
        foreach ($gruppi as $gruppo) {
            $array[] = [
                'id_gruppo' => $gruppo->id,
                'id_segment' => $id_record,
            ];
        }
        $dbo->insert('zz_group_segment', $array);

        flash()->info(tr('Nuovo segmento aggiunto'));

        break;

    case 'delete':
        $dbo->query('DELETE FROM zz_segments WHERE id='.prepare($id_record));

        // TODO
        // eliminare riferimento sulle fatture eventuali collegate a questo segmento?

        flash()->info(tr('Segmento eliminato'));

        break;
}
