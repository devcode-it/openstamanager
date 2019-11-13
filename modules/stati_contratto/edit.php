<?php
include_once __DIR__.'/../../core.php';

if ($record['is_cancellabile']) {
    $attr = '';
} else {
    $attr = 'readonly';
    echo '<div class="alert alert-warning">'.tr('Alcune impostazioni non possono essere modificate per questo stato.').'</div>';
}

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<div class="row">


		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "extra": "<?php echo $attr; ?>" ]}
		</div>

        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Stato completato?'); ?>", "name": "is_completato", "value": "$is_completato$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come completati'); ?>", "placeholder": "<?php echo tr('Completato'); ?>", "extra": "" ]}
		</div>

		 <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Stato pianificabile?'); ?>", "name": "is_pianificabile", "value": "$is_pianificabile$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come pianificabili'); ?>", "placeholder": "<?php echo tr('Pianificabile'); ?>", "extra": "" ]}
		</div>

		 <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Stato fatturabile?'); ?>", "name": "is_fatturabile", "value": "$is_fatturabile$", "help": "<?php echo tr('I contratti che si trovano in questo stato verranno considerati come fatturabili'); ?>", "placeholder": "<?php echo tr('Fatturabile'); ?>", "extra": "" ]}
		</div>

	</div>

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "<?php echo tr('Icona'); ?>", "name": "icona", "required": 1, "class": "text-center", "value": "$icona$", "extra": "", "icon-after": "<?php echo (!empty($record['icona'])) ? '<i class=\"'.$record['icona'].'\"></i>' : ''; ?>"  ]}
		</div>

	</div>

</form>


<?php

if (!empty($contratti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ contratti collegati', [
        '_NUM_' => $contratti,
    ]).'.
</div>';
}



if (!empty($record['is_cancellabile'])) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
	<i class="fa fa-trash"></i>'.tr('Elimina').'
</a>';
}

?>