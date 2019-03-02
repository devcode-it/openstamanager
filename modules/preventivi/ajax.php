<?php

if (get('op') == 'get_costo_orario') {
    $id_tipo_intervento = get('id_tipo_intervento');

    $rs = $dbo->fetchArray('SELECT costo_orario FROM in_tipiintervento WHERE id_tipo_intervento='.prepare($id_tipo_intervento));
    echo $rs[0]['costo_orario'];
}
