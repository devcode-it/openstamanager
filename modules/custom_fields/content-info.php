<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Istruzioni per il campo _FIELD_', [
            '_FIELD_' => tr('Contenuto'),
        ]).'</h3>
    </div>

    <div class="box-body">
        <p>'.tr('Le seguenti sequenze di testo vengono sostituite nel seguente modo').':</p>
        <ul>';

$list = [
    'label' => tr('Nome'),
    'name' => tr('Nome HTML'),
];

foreach ($list as $key => $value) {
    echo '
            <li>'.tr('_TEXT_ con il valore del campo "_FIELD_"', [
                '_TEXT_' => '<code>|'.$key.'|</code>',
                '_FIELD_' => $value,
            ]).'</li>';
}

echo '
            <li>'.tr('_TEXT_ con il valore impostato per il record', [
                '_TEXT_' => '<code>|value|</code>',
            ]).'</li>';

echo '
        </ul>
    </div>
</div>';
