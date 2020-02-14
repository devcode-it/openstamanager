<?php

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
    {[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "da_pagare", "required": 1, "value": "" ]}
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
