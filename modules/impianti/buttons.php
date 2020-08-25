<?php

include_once __DIR__.'/../../core.php';

echo'
<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo impianto?\') ){ $(\'#copia-impianto\').submit(); }"> <i class="fa fa-copy"></i> '.tr('Duplica impianto').'</button>';

// Duplica impianto
echo '
<form action="" method="post" id="copia-impianto">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>';
