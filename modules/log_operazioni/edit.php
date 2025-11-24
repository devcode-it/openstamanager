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

?>

<style>
    #save-buttons{
        display: none;
    }
</style>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo tr('Dettagli'); ?></h3>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-2">
                {[ "type": "select", "label": "<?php echo tr('Utente'); ?>", "name": "id_utente", "value": "<?php echo $record['id_utente']; ?>", "values": "query=SELECT `zz_users`.`id`, `zz_users`.`username` AS descrizione FROM `zz_users`", "icon-before":"<i <?php echo $record['id_api'] ? "class='fa fa-plug' title='API'" : "class='fa fa-user' title='OpenStaManager'"; ?>'></i>", "readonly": 1 ]}
            </div>
            <div class="col-md-2">
                {[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_module", "value": "<?php echo $record['id_module']; ?>", "values": "query=SELECT `zz_modules`.`id`, `title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE `enabled` = 1", "readonly": 1 ]}
            </div>
            <div class="col-md-2">
                {[ "type": "select", "label": "<?php echo tr('Plugin'); ?>", "name": "id_plugin", "value": "<?php echo $record['id_plugin']; ?>", "values": "query=SELECT `zz_plugins`.`id`, `title` AS descrizione FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE `enabled` = 1", "readonly": 1 ]}
            </div>
            <div class="col-md-3">
                {[ "type": "span", "label": "<?php echo tr('Operazione'); ?>", "name": "op", "value": "<?php echo $record['op']; ?>" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                {[ "type": "textarea", "label": "<?php echo tr('Richiesta'); ?>", "name": "context", "value": "<?php echo $record['context']; ?>", "extra": "rows='5'", "readonly": 1 ]}
            </div>
            <div class="col-md-12">
                {[ "type": "textarea", "label": "<?php echo tr('Risposta'); ?>", "name": "message", "value": "<?php echo $record['message']; ?>", "extra": "rows='5'", "readonly": 1 ]}
            </div>
        </div>
    </div>
</div>