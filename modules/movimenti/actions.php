<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

switch (post('op')) {

    case 'add':
        $idsede_partenza = post('idsede_partenza');
        $idsede_destinazione = post('idsede_destinazione');
        $qta = ( post('direzione') == 'Carico manuale' ) ? post('qta') : -post('qta');

        if( post('direzione') == 'Carico manuale' ){
            if( $idsede_partenza == 0){
                $qta = -post('qta');
            } elseif( $idsede_partenza != 0 && $idsede_destinazione == 0){
                $qta = post('qta');
                $idsede_partenza = post('idsede_destinazione');
                $idsede_destinazione = post('idsede_partenza');
            }
        } else {
            if( $idsede_destinazione == 0){
                $qta = -post('qta');
                $idsede_partenza = post('idsede_destinazione');
                $idsede_destinazione = post('idsede_partenza');
            } elseif( $idsede_partenza == 0 && $idsede_destinazione != 0){
                $qta = post('qta');
            }
        }

        $articolo = Articolo::find(post('idarticolo'));
        $idmovimento = $articolo->movimenta($qta, post('movimento'), post('data'), 1);
        $dbo->query('UPDATE mg_movimenti SET idsede_azienda='.prepare($idsede_partenza).', idsede_controparte='.prepare($idsede_destinazione).' WHERE id='.prepare($idmovimento));
        
        break;

}
