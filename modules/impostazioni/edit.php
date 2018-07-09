<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
            <h3 class="panel-title">'.tr('Impostazioni _SEZIONE_', [
                '_SEZIONE_' => $records[0]['sezione'],
            ]).'</h3>
		</div>

		<div class="panel-body">';

foreach ($records as $record) {
    echo '
            <div class="col-md-6">
                '.Settings::input($record['id']).'
            </div>';
}

echo '
			<div class="clearfix"></div><hr>
            <div class="pull-right">
				<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>
			</div>
		</div>
	</div>

</form>';
