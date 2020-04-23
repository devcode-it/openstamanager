<?php

include_once __DIR__.'/../../core.php';

if (get('op') == 'get_costo_orario') {
    $idtipointervento = get('idtipointervento');

    $rs = $dbo->fetchArray('SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento='.prepare($idtipointervento));
    echo $rs[0]['costo_orario'];
}
