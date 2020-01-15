<?php

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;

$documento = Intervento::find($id_record);

$preventivo = $dbo->fetchOne('SELECT numero FROM co_preventivi WHERE id = '.prepare($documento['id_preventivo']));
$contratto = $dbo->fetchOne('SELECT nome, numero FROM co_contratti WHERE id = '.prepare($documento['id_contratto']));

$id_cliente = $documento['idanagrafica'];
$id_sede = $documento['idsede'];

if (!empty($documento['idsede_destinazione'])){
    
    $sedi = $dbo->fetchOne('SELECT nomesede, cap, citta, indirizzo, provincia FROM an_sedi WHERE id = '.prepare($documento['idsede_destinazione']));

    $s_citta = $sedi['citta'];
    $s_indirizzo = $sedi['indirizzo'];
    $s_cap = $sedi['cap'];
    $s_provincia = $sedi['provincia'];

}

//Se ho deciso di NON mostrare i prezzi al tencico mi assicuro che non li possa vedere dalla stampa
if (Auth::user()['gruppo'] == 'Tecnici' and $options['pricing'] == true and setting('Mostra i prezzi al tecnico') == 0) {
    $options['pricing'] = false;
}
