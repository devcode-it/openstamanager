<?php

include_once __DIR__.'/../../core.php';
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update">

    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><?php echo tr('Tipologia'); ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$title$" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Ordine'); ?>", "name": "ordine", "value": "$ordine$", "decimals": 0, "min-value": "0" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Attiva'); ?>", "name": "enabled", "value": "$enabled$" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$used = $dbo->fetchNum('SELECT `id` FROM `co_note_spese` WHERE `id_tipologia` = '.prepare($id_record));
if (!empty($used)) {
    echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.tr('La tipologia è già utilizzata: può essere disattivata ma non eliminata.').'</div>';
} elseif (!empty($record['can_delete'])) {
    echo '<a class="btn btn-danger ask" data-backto="record-list"><i class="fa fa-trash"></i> '.tr('Elimina').'</a>';
} else {
    echo '<div class="alert alert-light border"><i class="fa fa-lock"></i> '.tr('Questa è una tipologia predefinita: può essere disattivata ma non eliminata.').'</div>';
}
?>
