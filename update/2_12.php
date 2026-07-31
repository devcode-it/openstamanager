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

// Migrazione della filigrana stampe esistente dal vecchio sistema (file in anagrafiche) al nuovo sistema (zz_files con key watermark)
$id_module_impostazioni = $dbo->fetchOne('SELECT `id` FROM `zz_modules` WHERE `directory` = "impostazioni"')['id'];
$filigrana = $dbo->fetchOne('SELECT `id`, `valore` FROM `zz_settings` WHERE `nome` = "Filigrana stampe"');
$id_setting = $filigrana['id'];

if (!empty($filigrana['valore']) && !is_numeric($filigrana['valore'])) {
    $filename = $filigrana['valore'];
    $source_path = base_dir().'/files/anagrafiche/'.$filename;

    if (file_exists($source_path)) {
        // Verifica che non esista già un file watermark migrato
        $existing = $dbo->fetchOne('SELECT `id` FROM `zz_files` WHERE `key` = "watermark" AND `id_module` = '.prepare($id_module_impostazioni).' AND `id_record` = '.prepare($id_setting));

        if (empty($existing)) {
            // Copia del file nella cartella del modulo impostazioni
            $dest_dir = base_dir().'/files/impostazioni';
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0777, true);
            }

            $new_filename = 'watermark_'.$filename;
            copy($source_path, $dest_dir.'/'.$new_filename);

            $dbo->insert('zz_files', [
                'name' => 'Watermark',
                'filename' => $new_filename,
                'original' => $filename,
                'id_module' => $id_module_impostazioni,
                'id_record' => $id_setting,
                'id_adapter' => $dbo->fetchOne('SELECT `id` FROM `zz_storage_adapters` WHERE `is_default` = 1')['id'] ?? 1,
                'key' => 'watermark',
            ]);

            $file_id = $dbo->lastInsertedID();
            $dbo->update('zz_settings', ['valore' => $file_id], ['nome' => 'Filigrana stampe']);
        }
    } else {
        // File non più presente, reset del valore
        $dbo->update('zz_settings', ['valore' => ''], ['nome' => 'Filigrana stampe']);
    }
}