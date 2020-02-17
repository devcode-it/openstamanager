<?php

include_once __DIR__.'/../../core.php';

use Plugins\PianificazioneInterventi\Promemoria;

if (isset($id_record)) {
    $promemoria = Promemoria::find($id_record);
}
