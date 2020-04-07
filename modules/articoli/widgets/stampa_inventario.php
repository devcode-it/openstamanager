<?php

include_once __DIR__.'/../../../core.php';

use Carbon\Carbon;

// Trovo id_print della stampa
$id_print = $dbo->fetchOne('SELECT zz_prints.id FROM zz_prints INNER JOIN zz_modules ON zz_prints.id_module=zz_modules.id WHERE zz_modules.name="Articoli" AND zz_prints.name="Inventario magazzino"')['id'];

echo '
<form action="'.$rootdir.'/pdfgen.php?id_print='.$id_print.'" method="post" target="_blank">

	<div class="row">

        <div class="col-md-3">
			{[ "type": "select", "label": "'.tr('Articoli da stampare').'", "name": "tipo", "required": "1", "values": "list=\"full\": \"'.tr('Tutti').'\", \"nozero\": \"'.tr('Solo esistenti').'\"", "value": "nozero" ]}
		</div>

		<div class="col-md-2">
			<p style="line-height:14px;">&nbsp;</p>
			
			<button type="submit" class="btn btn-primary btn-block">
			<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>

	</div>

</form>

<script>$(document).ready(init)</script>';
