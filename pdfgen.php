<?php

include_once __DIR__.'/core.php';

$directory = !empty($directory) ? $directory : null;
$id_print = get('id_print');

// Retrocompatibilitaà
$ptype = get('ptype');
if (!empty($ptype)) {
    $print = $dbo->fetchArray('SELECT id, previous FROM zz_prints WHERE directory = '.prepare($ptype).' ORDER BY predefined DESC LIMIT 1');
    $id_print = $print[0]['id'];

    $id_record = !empty($id_record) ? $id_record : get($print[0]['previous']);
}

$result = Prints::render($id_print, $id_record, $directory);

if(empty($result)){
    echo '
        <div class="text-center">
    		<h3 class="text-muted">
    		    <i class="fa fa-question-circle"></i> '.tr('Record non trovato').'
                <br><br>
                <small class="help-block">'.tr('Stai cercando di accedere ad un record eliminato o non presente').'.</small>
            </h3>
            <br>

            <a class="btn btn-default" href="'.ROOTDIR.'/index.php">
                <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
            </a>
        </div>';
}
