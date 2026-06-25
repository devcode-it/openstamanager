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

use Models\User;

$dbo = database();

$groups = $dbo->fetchArray('SELECT `id` FROM `zz_groups` ORDER BY `id`');

$groups_string = implode(',', array_column($groups, 'id'));

$setting_id = $dbo->fetchOne('SELECT `id` FROM `zz_settings` WHERE `nome` = "Gruppi abilitati alla modifica CC e CCN"');

if (empty($setting_id)) {
    $setting_id = $dbo->fetchOne('SELECT `id` FROM `zz_settings` ORDER BY `id` DESC LIMIT 1') + 1;

    $dbo->query('INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
    ('.prepare($setting_id).', \'Gruppi abilitati alla modifica CC e CCN\', '.prepare($groups_string).', \'query=SELECT `zz_groups`.`id`, `zz_groups_lang`.`title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) ORDER BY `zz_groups`.`id`\', 1, \'Mail\', 10, 0)');

    $dbo->query('INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
    (1, '.prepare($setting_id).', \'Gruppi abilitati alla modifica CC e CCN\', \'Seleziona i gruppi di utenti che possono modificare i campi CC e CCN durante l\'invio di email.\'),
    (2, '.prepare($setting_id).', \'Groups enabled to edit CC and BCC\', \'Select the user groups that can edit CC and BCC fields when sending emails.\')');
}