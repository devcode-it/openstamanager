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

include_once __DIR__.'/../../core.php';

$id_conto_lvl2 = get('id');

?><form action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get('Piano dei conti')['id']; ?>" method="post">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-list">

    <input type="hidden" name="idpianodeiconti2" value="<?php echo $id_conto_lvl2; ?>">

    <div class="row">

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "000000", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
        </div>
    </div>
    <br>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
    </div>
    <div class="clearfix"></div>
</form>

