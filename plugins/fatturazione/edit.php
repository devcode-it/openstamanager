<?php

include_once __DIR__.'/../../core.php';

if ($download) {
    echo '
<div class="row">
    <div class="col-md-6">';
}

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="generate">

    <button type="submit" class="btn btn-primary btn-lg btn-block'.($disabled ? ' disabled' : null).'" '.($disabled ? ' disabled' : null).'>
        <i class="fa fa-file"></i> '.tr('Genera fattura elettronica').'
    </button>
</form>';

if ($download) {
    echo '
    </div>

    <div class="col-md-6">
        <a href="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&op=download" class="btn btn-success btn-lg btn-block" target="_blank">
            <i class="fa fa-download "></i> '.tr('Scarica fattura elettronica').'
        </a>
    </div>
</div>';
}
