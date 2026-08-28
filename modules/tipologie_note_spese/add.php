<?php

include_once __DIR__.'/../../core.php';
?>
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
        </div>
        <div class="col-md-4">
            {[ "type": "number", "label": "<?php echo tr('Ordine'); ?>", "name": "ordine", "value": "100", "decimals": 0, "min-value": "0" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
        </div>
    </div>
</form>
