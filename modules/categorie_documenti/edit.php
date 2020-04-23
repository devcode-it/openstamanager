<?php

include_once __DIR__.'/../../core.php';

// Presenza di documenti associati
if ($record['doc_associati'] > 0) {
    echo '
<div class="alert alert-warning">'.tr('Non puoi eliminare questo categoria documento!').' '.tr('Ci sono _NUM_ documenti associati!', [
    '_NUM_' => $record['doc_associati'],
]).'</div>';
}

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<div class="row">
		<div class="col-md-9">
			{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Gruppi abilitati'); ?>", "name": "permessi[]", "value": "$permessi$", "values": "query=SELECT `id`, `nome` AS text FROM `zz_groups`", "multiple": 1 ]}
        </div>
	</div>
</form>

<?php

// Presenza di documenti associati
if ($record['doc_associati'] == 0) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}
