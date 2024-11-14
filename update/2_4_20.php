<?php

use Modules\Contratti\Components\Articolo as ArticoloContratto;
use Modules\Contratti\Components\Riga as RigaContratto;
use Modules\DDT\Components\Articolo as ArticoloDDT;
use Modules\DDT\Components\Riga as RigaDDT;
use Modules\Ordini\Components\Articolo as ArticoloOrdine;
use Modules\Ordini\Components\Riga as RigaOrdine;
use Modules\Preventivi\Components\Articolo as ArticoloPreventivo;
use Modules\Preventivi\Components\Riga as RigaPreventivo;

// File e cartelle deprecate
$files = [
    'modules/listini/src/',
    'modules/listini/actions.php',
    'modules/listini/add.php',
    'modules/listini/edit.php',
    'modules/listini/init.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

/**
 * Procedura per aggiustare alcuni campi di sconto ivato nei documenti prima della fattura
 * in quanto veniva calcolato lo sconto ivato erroneamente.
 */

// Fix sconti contratti
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, sconto_unitario, tipo_sconto, prezzo_unitario FROM co_righe_contratti WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach ($righe as $riga) {
    if (empty($riga['idarticolo'])) {
        $this_riga = RigaContratto::find($riga['id']);
    } else {
        $this_riga = ArticoloContratto::find($riga['id']);
    }

    if ($this_riga !== null) {
        $this_riga->setPrezzoUnitario($riga['prezzo_unitario'], $riga['idiva']);

        if ($riga['tipo_sconto'] == 'PRC') {
            $this_riga->setSconto($riga['sconto_percentuale'], $riga['tipo_sconto']);
        } else {
            $this_riga->setSconto($riga['sconto_unitario'], $riga['tipo_sconto']);
        }

        $this_riga->save();
    }
}

// Fix sconti preventivi
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, sconto_unitario, tipo_sconto, prezzo_unitario FROM co_righe_preventivi WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach ($righe as $riga) {
    if (empty($riga['idarticolo'])) {
        $this_riga = RigaPreventivo::find($riga['id']);
    } else {
        $this_riga = ArticoloPreventivo::find($riga['id']);
    }

    if ($this_riga !== null) {
        $this_riga->setPrezzoUnitario($riga['prezzo_unitario'], $riga['idiva']);

        if ($riga['tipo_sconto'] == 'PRC') {
            $this_riga->setSconto($riga['sconto_percentuale'], $riga['tipo_sconto']);
        } else {
            $this_riga->setSconto($riga['sconto_unitario'], $riga['tipo_sconto']);
        }

        $this_riga->save();
    }
}

// Fix sconti ordini
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, sconto_unitario, tipo_sconto, prezzo_unitario FROM or_righe_ordini WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach ($righe as $riga) {
    if (empty($riga['idarticolo'])) {
        $this_riga = RigaOrdine::find($riga['id']);
    } else {
        $this_riga = ArticoloOrdine::find($riga['id']);
    }

    if ($this_riga !== null) {
        $this_riga->setPrezzoUnitario($riga['prezzo_unitario'], $riga['idiva']);

        if ($riga['tipo_sconto'] == 'PRC') {
            $this_riga->setSconto($riga['sconto_percentuale'], $riga['tipo_sconto']);
        } else {
            $this_riga->setSconto($riga['sconto_unitario'], $riga['tipo_sconto']);
        }

        $this_riga->save();
    }
}

// Fix sconti ddt
$righe = $dbo->fetchArray('SELECT id, idiva, sconto_percentuale, sconto_unitario, tipo_sconto, prezzo_unitario FROM dt_righe_ddt WHERE sconto_percentuale != 0 AND tipo_sconto="PRC"');

foreach ($righe as $riga) {
    if (empty($riga['idarticolo'])) {
        $this_riga = RigaDDT::find($riga['id']);
    } else {
        $this_riga = ArticoloDDT::find($riga['id']);
    }

    if ($this_riga !== null) {
        $this_riga->setPrezzoUnitario($riga['prezzo_unitario'], $riga['idiva']);

        if ($riga['tipo_sconto'] == 'PRC') {
            $this_riga->setSconto($riga['sconto_percentuale'], $riga['tipo_sconto']);
        } else {
            $this_riga->setSconto($riga['sconto_unitario'], $riga['tipo_sconto']);
        }

        $this_riga->save();
    }
}
