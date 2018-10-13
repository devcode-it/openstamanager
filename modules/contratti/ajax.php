<?php

include_once __DIR__.'/../../core.php';

if (get('op') == 'get_costo_orario') {
    $id_tipo_intervento = get('id_tipo_intervento');

    $rs = $dbo->fetchArray('SELECT costo_orario FROM in_tipiintervento WHERE id_tipo_intervento='.prepare($id_tipo_intervento));
    echo $rs[0]['costo_orario'];
}

// Copia ordine di servizio da un impianto ad un altro
elseif (get('op') == 'get_pianificazione_os') {
    $idcontratto = get('idcontratto');
    $matricola_src = get('matricola_src');

    $ordiniservizio = [];

    if (!empty($matricola_src)) {
        // Leggo tutti gli ordini di servizio creati per matricola_src
        $rs = $dbo->fetchArray('SELECT * FROM co_ordiniservizio WHERE idcontratto='.prepare($idcontratto).' AND idimpianto='.prepare($matricola_src));

        for ($i = 0; $i < sizeof($rs); ++$i) {
            // Leggo tutte le voci di servizio
            $rs2 = $dbo->fetchArray('SELECT (SELECT id FROM in_vociservizio WHERE descrizione=voce) AS idvoce FROM co_ordiniservizio_vociservizio WHERE idordineservizio='.prepare($rs[$i]['id']));

            for ($v = 0; $v < sizeof($rs2); ++$v) {
                $ordiniservizio[] = date('Ym', strtotime($rs[$i]['data_scadenza'])).':'.$rs2[$v]['idvoce'];
            }
        }

        // Ritorno l'array con le combinazioni di voce e mese pianificato
        echo implode(',', $ordiniservizio);
        exit();
    }
}
