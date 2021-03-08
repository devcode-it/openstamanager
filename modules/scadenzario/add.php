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

<form action="" method="post" id="add-form">
  <input type="hidden" name="op" value="add">
  <input type="hidden" name="backto" value="record-edit">
  <input type="hidden" name="dir" value="<?php echo $dir; ?>">

  <div class="row">
    <div class="col-md-4">
    {[ "type": "select", "label": "<?php echo tr('Tipo'); ?>", "name": "tipo", "required": 1, "ajax-source": "tipi_scadenze", "icon-after": "add|<?php echo Modules::get('Tipi scadenze')['id']; ?>" ]}
    </div>

    <div class="col-md-4">
    {[ "type": "date", "label": "<?php echo tr('Data scadenza'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
    </div>

    <div class="col-md-4">
    {[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "da_pagare", "required": 1, "value": "", "help": "Le scadenze inserite con importo positivo indicano un credito da un cliente, le scadenze inserite con importo negativo indicano un debito verso un fornitore" ]}
    </div>
  </div>

  <div class='row'>
    <div class='col-md-12'>
      {[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "" ]}
    </div>
  </div>

  <div class='pull-right'>
    <button type='submit' class='btn btn-primary'><i class='fa fa-plus'></i> Aggiungi</button>
  </div>

  <div class='clearfix'></div>
</form>