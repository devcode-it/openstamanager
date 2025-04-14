<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/core.php';

// Fix conto per registrazione contabile associate ai conti riepilogativi
$riepilogativo_fornitori = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo fornitori"')['id'];
$riepilogativo_clienti = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo clienti"')['id'];
if ($riepilogativo_fornitori && $riepilogativo_clienti) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` IN ('.$riepilogativo_clienti.', '.$riepilogativo_fornitori.')');
} elseif ($riepilogativo_fornitori) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` = '.$riepilogativo_fornitori);
} elseif ($riepilogativo_clienti) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` = '.$riepilogativo_clienti);
}

foreach ($fatture as $fattura) {
    $fattura = Fattura::find($fattura['iddocumento']);
    $conto_cliente = $fattura->anagrafica->idconto_cliente;
    $conto_fornitore = $fattura->anagrafica->idconto_fornitore;

    if ($conto_fornitore) {
        $dbo->query('UPDATE co_movimenti SET idconto = '.$conto_fornitore.' WHERE iddocumento = '.$fattura->id.' AND idconto = '.$riepilogativo_fornitori);
    }
    if ($conto_cliente) {
        $dbo->query('UPDATE co_movimenti SET idconto = '.$conto_cliente.' WHERE iddocumento = '.$fattura->id.' AND idconto = '.$riepilogativo_clienti);
    }
}
