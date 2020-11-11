<?php

use Modules\Contratti\Components\Riga AS RigaContratto;
use Modules\Contratti\Components\Articolo AS ArticoloContratto;
use Modules\Preventivi\Components\Riga AS RigaPreventivo;
use Modules\Preventivi\Components\Articolo AS ArticoloPreventivo;
use Modules\Ordini\Components\Riga AS RigaOrdine;
use Modules\Ordini\Components\Articolo AS ArticoloOrdine;
use Modules\DDT\Components\Riga AS RigaDDT;
use Modules\DDT\Components\Articolo AS ArticoloDDT;


/**
 * Procedura per aggiustare alcuni campi di sconto ivato nei documenti prima della fattura
 * in quanto veniva calcolato lo sconto ivato erroneamente
 */

// Fix sconti contratti
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, prezzo_unitario FROM co_righe_contratti WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach( $righe as $riga ){
    if( empty($riga['idarticolo']) ){
        $this_riga = RigaContratto::find( $riga['id'] );
    } else {
        $this_riga = ArticoloContratto::find( $riga['id'] );
    }

    if( $this_riga !== null ){
        $this_riga->setPrezzoUnitario( $riga['prezzo_unitario'], $riga['idiva'] );
        $this_riga->setSconto( $riga['sconto_percentuale'], 'PRC' );
        $this_riga->save();
    }
}

// Fix sconti preventivi
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, prezzo_unitario FROM co_righe_preventivi WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach( $righe as $riga ){
    if( empty($riga['idarticolo']) ){
        $this_riga = RigaPreventivo::find( $riga['id'] );
    } else {
        $this_riga = ArticoloPreventivo::find( $riga['id'] );
    }

    if( $this_riga !== null ){
        $this_riga->setPrezzoUnitario( $riga['prezzo_unitario'], $riga['idiva'] );
        $this_riga->setSconto( $riga['sconto_percentuale'], 'PRC' );
        $this_riga->save();
    }
}

// Fix sconti ordini
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, prezzo_unitario FROM or_righe_ordini WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach( $righe as $riga ){
    if( empty($riga['idarticolo']) ){
        $this_riga = RigaOrdine::find( $riga['id'] );
    } else {
        $this_riga = ArticoloOrdine::find( $riga['id'] );
    }

    if( $this_riga !== null ){
        $this_riga->setPrezzoUnitario( $riga['prezzo_unitario'], $riga['idiva'] );
        $this_riga->setSconto( $riga['sconto_percentuale'], 'PRC' );
        $this_riga->save();
    }
}

// Fix sconti ddt
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, prezzo_unitario FROM dt_righe_ddt WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach( $righe as $riga ){
    if( empty($riga['idarticolo']) ){
        $this_riga = RigaDDT::find( $riga['id'] );
    } else {
        $this_riga = ArticoloDDT::find( $riga['id'] );
    }

    if( $this_riga !== null ){
        $this_riga->setPrezzoUnitario( $riga['prezzo_unitario'], $riga['idiva'] );
        $this_riga->setSconto( $riga['sconto_percentuale'], 'PRC' );
        $this_riga->save();
    }
}