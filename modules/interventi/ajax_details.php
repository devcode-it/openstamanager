<?php

include_once __DIR__.'/../../core.php';

$id_anagrafica = get('id_anagrafica');
$op = get('op');

switch ($op) {
    case 'dettagli':
        echo "
        <div class='row'>";

        //Contratti attivi
        $rs_contratti = $dbo->fetchArray("SELECT co_contratti.id AS id, CONCAT('Contratto ', numero, ' del ', DATE_FORMAT(data_bozza, '%d/%m/%Y'), ' - ', co_contratti.nome, ' [', (SELECT `descrizione` FROM `co_staticontratti` WHERE `co_staticontratti`.`id` = `idstato`) , ']') AS descrizione FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica WHERE idstato IN (SELECT `id` FROM `co_staticontratti` WHERE is_pianificabile=1) AND co_contratti.idanagrafica=".prepare($id_anagrafica));

        echo "
            <div class='col-md-4'>
                <b>CONTRATTI:</b><hr style='margin-top:5px;margin-bottom:15px;'>";
        if (sizeof($rs_contratti) > 0) {
            foreach ($rs_contratti as $contratto) {
                echo "
                <div class='alert alert-info' style='margin-bottom: 10px;'>
                    ".$contratto['descrizione'].'
                </div>';
            }
        } else {
            echo 'Nessun contratto per questo cliente...';
        }
        echo '  
            </div>';

        //Fatture emesse o parzialnente pagate
        $rs_documenti = $dbo->fetchArray("SELECT co_documenti.id AS id, CONCAT('Fattura ', numero_esterno, ' del ', DATE_FORMAT(data, '%d/%m/%Y')) AS descrizione FROM co_documenti WHERE idstatodocumento IN(SELECT id FROM co_statidocumento WHERE descrizione IN('Emessa', 'Parzialmente pagato')) AND idanagrafica=".prepare($id_anagrafica));

        echo "
            <div class='col-md-4'>
                <b>Fatture:</b><hr style='margin-top:5px;margin-bottom:15px;'>";
        if (sizeof($rs_documenti) > 0) {
            foreach ($rs_documenti as $documento) {
                $rs_scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento='.prepare($documento['id']));

                echo "
                    <div class='alert alert-info' style='margin-bottom: 10px;'>
                        ".$documento['descrizione'].'<br>';
                foreach ($rs_scadenze as $scadenza) {
                    echo Translator::dateToLocale($scadenza['scadenza']).' - '.Translator::numberToLocale($scadenza['da_pagare']).' €<br>';
                }
                echo '
                    </div>';
            }
        } else {
            echo 'Nessuna fattura per questo cliente...';
        }
        echo '  
            </div>';

        //Note dell'anagrafica
        $rs_anagrafica = $dbo->fetchOne('SELECT note FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica));

        if ($rs_anagrafica['note'] != '') {
            echo "
            <div class='col-md-4'>
                <b>NOTE CLIENTE:</b><hr style='margin-top:5px;margin-bottom:15px;'>
                <div class='alert alert-info' style='margin-bottom: 10px;'>".$rs_anagrafica['note'].'</div>
            </div>';
        } else {
            echo 'Nessuna nota per questo cliente...';
        }

        echo '
        </div>';

        break;
}
