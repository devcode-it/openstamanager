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
use Models\Module;

$id_conto = get('id');
$lvl = get('lvl');

?><form action="<?php echo base_path(); ?>/editor.php?id_module=<?php echo Module::where('name', 'Piano dei conti')->first()->id; ?>" method="post">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-list">

    <input type="hidden" name="id_conto" value="<?php echo $id_conto; ?>">
    <input type="hidden" name="lvl" value="<?php echo $lvl; ?>">

    <div class="row">

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "000000", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
        </div>
            <div class="col-md-4 <?php echo intval($lvl != 3) ? 'hidden' : ''; ?>">
                {[ "type": "number", "decimals": 0, "label": "<?php echo tr('Percentuale deducibile'); ?>", "name": "percentuale_deducibile", "value": "<?php echo $info['percentuale_deducibile']; ?>", "icon-after": "<i class='fa fa-percent'></i>", "max-value": "100", "min-value": "0" ]}
            </div>
            <div class="col-md-4 <?php echo intval($lvl != 2) ? 'hidden' : ''; ?>">
                {[ "type": "select", "label": "<?php echo tr('Utilizza come'); ?>", "name": "dir", "value": "<?php echo $info['dir']; ?>", "values": "list=\"entrata\":\"Ricavo\", \"uscita\":\"Costo\", \"entrata/uscita\":\"Ricavo e Costo\", \"\": \"Non usare\"" ]}
            </div>
    </div>
    <br>

    <div class="float-right d-none d-sm-inline">
        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
    </div>
    <div class="clearfix"></div>
</form>

