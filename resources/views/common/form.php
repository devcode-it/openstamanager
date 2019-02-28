<?php

$result['id'] = isset($result['id']) ? $result['id'] : null;

// Form di inserimento riga documento
echo '
<form action="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="hash" value="tab_'.$id_plugin.'">
    <input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="idriga" value="'.$result['id'].'">
    <input type="hidden" name="dir" value="'.$options['dir'].'">';

echo '
    |response|';

$button = $options['action'] == 'add' ? tr('Aggiungi') : tr('Modifica');
$icon = $options['action'] == 'add' ? 'fa-plus' : 'fa-pencil';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right"><i class="fa '.$icon.'"></i> '.$button.'</button>
		</div>
    </div>
</form>';

echo '
	<script src="'.ROOTDIR.'/lib/init.js"></script>';
