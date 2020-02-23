<?php

include_once __DIR__.'/../../core.php';

echo '

<form action="" class="text-right" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>

<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo articolo?\') ){ $(\'#form-copy\').submit(); }">
    <i class="fa fa-copy"></i> '.tr('Duplica articolo').'
</button>';
