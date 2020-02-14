<?php

include_once __DIR__.'/../../core.php';

if (empty($dbo->fetchArray('SELECT * FROM co_scadenziario'))) {
    $class = 'muted';
    $disabled = 'disabled';
} else {
    $class = 'primary';
    $disabled = '';
}

?><br>

<!-- STAMPA TOTALE -->
<div class="row">
	<div class="col-md-4 col-md-offset-4">

		<button type="button" onclick="window.open('<?php echo Prints::getHref('Scadenzario', null, null); ?>');" <?php echo $disabled; ?> class="btn btn-<?php echo $class; ?> btn-block btn-lg text-center"><i class="fa fa-print"></i> <?php echo tr('Stampa scadenzario'); ?></button>

	</div>
</div>
<br>