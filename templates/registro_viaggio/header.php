<?php

include_once __DIR__.'/../../core.php';

echo '
<h4 class="text-center"><b>'.tr('REGISTRO DI VIAGGIO', [], ['upper' => true]).'</b></h4>
<h5 class="text-center"><b>'.Translator::dateToLocale($data_inizio).' - '.Translator::dateToLocale($data_fine).'</b></h5>
<p><b>'.tr('VEICOLO').':</b> '.$automezzo['nome'].'</p>
<p><b>'.tr('TARGA').':</b> '.$automezzo['targa'].'</p>
<br>
';
