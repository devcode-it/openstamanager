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

$idconto = get('id');
$lvl = get('lvl');

// Info conto
if ($lvl == 2) {
    $query = 'SELECT *, idpianodeiconti1 AS idpianodeiconti FROM co_pianodeiconti2 WHERE id='.prepare($idconto);
} else {
    $query = 'SELECT *, idpianodeiconti2 AS idpianodeiconti, (SELECT dir FROM co_pianodeiconti2 WHERE co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2) AS dir FROM co_pianodeiconti3 WHERE id='.prepare($idconto);
}

$info = $dbo->fetchOne($query);

?><form action="<?php echo base_path(); ?>/editor.php?id_module=<?php echo Modules::get('Piano dei conti')['id']; ?>" method="post">
    <input type="hidden" name="op" value="edit">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="lvl" value="<?php echo $lvl; ?>">

    <input type="hidden" name="idpianodeiconti" value="<?php echo $info['idpianodeiconti']; ?>">
    <input type="hidden" name="idconto" value="<?php echo $info['id']; ?>">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "<?php echo $info['numero']; ?>", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": <?php echo json_encode($info['descrizione']); ?> ]}
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 <?php echo intval($lvl != 3 || $info['dir'] != 'uscita') ? 'hidden' : ''; ?>">
            {[ "type": "number", "decimals": 0, "label": "<?php echo tr('Percentuale deducibile'); ?>", "name": "percentuale_deducibile", "value": "<?php echo $info['percentuale_deducibile']; ?>", "icon-after": "<i class=\"fa fa-percent\"></i>", "max-value": "100", "min-value": "0" ]}
        </div>

        <div class="col-md-4 <?php echo intval($lvl != 2) ? 'hidden' : ''; ?>">
            {[ "type": "select", "label": "<?php echo tr('Utilizza come'); ?>", "name": "dir", "value": "<?php echo $info['dir']; ?>", "values": "list=\"entrata\":\"Ricavo\", \"uscita\":\"Costo\", \"\": \"Non usare\"" ]}
        </div>
    </div>
    <br>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-edit"></i> <?php echo tr('Modifica'); ?>
        </button>
    </div>
    <div class="clearfix"></div>
</form>

<script>$(document).ready(init)</script>

