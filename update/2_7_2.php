<?php
use Modules\Fatture\Fattura;
use Modules\Anagrafiche\Anagrafica;

include __DIR__.'/../config.inc.php';


$module = \Models\Module::where('name', 'Fatture di vendita')->first();
$directory = 'files/fatture/';
$files = glob($directory.'*.xml');
$new_folder = 'files/'.$module->attachments_directory.'/';
directory($new_folder);

$attachments = database()->fetchArray('SELECT `filename` FROM `zz_files` WHERE `name` = "Fattura Elettronica" AND `id_module` = '.$module->id);
$attachments_filenames = array_column($attachments, 'filename');

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $attachments_filenames)) {
        rename($file, $new_folder.$filename);
    }
}

// Fix conti collegati alle anagrafiche
$anagrafiche =  Anagrafica::all();

foreach ($anagrafiche as $anagrafica) {
    if ($anagrafica->isTipo('Cliente')) {
        Anagrafica::fixCliente($anagrafica);
    }

    if ($anagrafica->isTipo('Fornitore')) {
        Anagrafica::fixFornitore($anagrafica);
    }
}

// Fix conto per registrazione contabile associate ai conti riepilogativi
$riepilogativo_fornitori = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo fornitori"')['id'];
$riepilogativo_clienti = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo clienti"')['id'];
$fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` IN('.$riepilogativo_clienti.', '.$riepilogativo_fornitori.')');
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

// Fix registrazioni contabili associate a conti rimossi
$fatture_senzanome = $dbo->fetchArray('SELECT `iddocumento`, `idconto` FROM `co_movimenti` WHERE `idconto` NOT IN (SELECT `id` FROM `co_pianodeiconti3`)');
foreach ($fatture_senzanome as $fattura) {
    $documento = Fattura::find($fattura['iddocumento']);
    $conto = ($documento->tipo->dir == 'uscita' ? $documento->anagrafica->idconto_fornitore : $documento->anagrafica->idconto_cliente);
    $dbo->query('UPDATE co_movimenti SET idconto = '.$conto.' WHERE iddocumento = '.$documento->id.' AND idconto = '.$fattura['idconto']);
}