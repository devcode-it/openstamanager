<?php

include_once __DIR__.'/../../core.php';

echo '

<form action="" class="text-right" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>

<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questa fattura?\') ){ $(\'#form-copy\').submit(); }">
    <i class="fa fa-copy"></i> '.tr('Duplica fattura').'
</button>';

if ($dir == 'entrata' && empty($record['ref_documento']) && $record['stato'] == 'Emessa') {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-primary unblockable dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-magic"></i> '.tr('Crea').' <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>

    <ul class="dropdown-menu dropdown-menu-right">
        <li><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=nota_addebito&backto=record-edit">
            '.tr('Nota di debito').'
        </a></li>

        <li><a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&iddocumento='.$id_record.'" data-title="Aggiungi nota di credito" data-target="#bs-popup">
            '.tr('Nota di credito').'
        </a></li>
    </ul>
</div>';
}
