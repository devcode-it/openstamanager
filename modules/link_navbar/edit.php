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

$assets_pretty = '';
if (!empty($record['assets'])) {
    $decoded = json_decode($record['assets'], true);
    if (is_array($decoded)) {
        $assets_pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $assets_pretty = $record['assets'];
    }
}
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><?php echo tr('Informazioni base'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Nome interno'); ?>", "name": "name", "required": 1, "value": "$name$", "help": "<?php echo tr('Identificatore macchina univoco (slug). Usato come riferimento dai moduli consumer.'); ?>" ]}
                        </div>
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Etichetta'); ?>", "name": "label", "required": 1, "value": "$label$" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            {[ "type": "text", "label": "<?php echo tr('Tooltip'); ?>", "name": "title", "value": "$title$" ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><?php echo tr('Stato'); ?></h3>
                </div>
                <div class="card-body">
                    {[ "type": "checkbox", "label": "<?php echo tr('Abilitato'); ?>", "name": "enabled", "value": "$enabled$" ]}

                    {[ "type": "number", "label": "<?php echo tr('Ordine'); ?>", "name": "order", "value": "$order$" ]}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><?php echo tr('Comportamento'); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Tipo'); ?>", "name": "type", "required": 1, "value": "$type$", "values": "list=\"link\": \"<?php echo tr('Link URL'); ?>\", \"javascript\": \"<?php echo tr('JavaScript'); ?>\", \"module\": \"<?php echo tr('Modulo'); ?>\", \"plugin\": \"<?php echo tr('Plugin'); ?>\"" ]}
                </div>
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Valore'); ?>", "name": "value", "required": 1, "value": "$value$", "help": "<?php echo tr('Link: URL. JavaScript: nome funzione globale (regex ^[a-zA-Z_$][a-zA-Z0-9_$.]*$). Modulo/Plugin: nome registrato in zz_modules / zz_plugins.'); ?>" ]}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><?php echo tr('Aspetto'); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icon", "value": "$icon$", "icon-after": "<i class=\"<?php echo addslashes((string) ($record['icon'] ?? '')); ?>\"></i>" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Colore'); ?>", "name": "color", "value": "$color$", "values": "list=\"\": \"<?php echo tr('Nessuno'); ?>\", \"text-primary\": \"<?php echo tr('Primary'); ?>\", \"text-success\": \"<?php echo tr('Success'); ?>\", \"text-warning\": \"<?php echo tr('Warning'); ?>\", \"text-danger\": \"<?php echo tr('Danger'); ?>\", \"text-info\": \"<?php echo tr('Info'); ?>\", \"text-muted\": \"<?php echo tr('Muted'); ?>\"" ]}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><?php echo tr('Struttura e permessi'); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Voce padre'); ?>", "name": "parent", "value": "$parent$", "values": "query=SELECT zz_links.id AS id, CONCAT(zz_links.name, ' (', COALESCE(zz_links_lang.label, ''), ')') AS descrizione FROM zz_links LEFT JOIN zz_links_lang ON (zz_links.id = zz_links_lang.id_record AND zz_links_lang.id_lang = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE zz_links.id != <?php echo (int) $id_record; ?> AND zz_links.parent IS NULL ORDER BY zz_links.name", "placeholder": "<?php echo tr('Top level'); ?>", "help": "<?php echo tr('Se valorizzato, voce appare come elemento di un dropdown.'); ?>" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "select", "label": "<?php echo tr('Modulo associato'); ?>", "name": "id_module", "value": "$id_module$", "values": "query=SELECT zz_modules.id AS id, COALESCE(zz_modules_lang.title, zz_modules.name) AS descrizione FROM zz_modules LEFT JOIN zz_modules_lang ON (zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE zz_modules.enabled = 1 ORDER BY descrizione", "placeholder": "<?php echo tr('Nessuno'); ?>", "help": "<?php echo tr('Se valorizzato per type=link/javascript: voce visibile solo se utente ha permessi sul modulo. Determina anche directory per asset shorthand.'); ?>" ]}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><?php echo tr('Asset JS'); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Lista asset (JSON array)'); ?>", "name": "assets", "value": "<?php echo addslashes($assets_pretty); ?>", "rows": "5", "help": "<?php echo tr('Esempio: [\"file.js\", \"modules/altro/assets/dist/js/x.js\"]. Shorthand (solo filename) richiede modulo associato e cerca in modules/{dir}/assets/dist/js/. Path con / è interpretato da OSM root (cross-module).'); ?>" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
