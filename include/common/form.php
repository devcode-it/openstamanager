<?php

include_once __DIR__.'/../../core.php';

/*
    Form di inserimento riga documento
*/
echo '
<form action="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="idriga" value="'.$result['id'].'">
    <input type="hidden" name="dir" value="'.$options['dir'].'">';

echo '
    |response|';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> '.$options['button'].'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.ROOTDIR.'/lib/init.js"></script>';
