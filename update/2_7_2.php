<?php

include __DIR__.'/../config.inc.php';

$dbo = database();

$module = $dbo->fetchOne('SELECT * FROM zz_modules WHERE name = ?', ['Fatture di vendita']);
$directory = 'files/fatture/';
$files = glob($directory.'*.xml');
$new_folder = 'files/'.$module['attachments_directory'].'/';
directory($new_folder);

$attachments = $dbo->fetchArray('SELECT `filename` FROM `zz_files` WHERE `name` = "Fattura Elettronica" AND `id_module` = ?', [$module['id']]);
$attachments_filenames = array_column($attachments, 'filename');

foreach ($files as $file) {
    $filename = basename($file);
    if (in_array($filename, $attachments_filenames)) {
        rename($file, $new_folder.$filename);
    }
}

// Rimozione file e cartelle deprecate
$files = [
    'assets/src/js/wacom/modules/protobufjs/bin/',
    'assets/src/js/wacom/modules/protobufjs/cli/',
    'assets/src/js/wacom/modules/protobufjs/CHANGELOG.md',
    'assets/src/js/wacom/modules/protobufjs/scripts/changelog.js',
    'assets/src/js/wacom/modules/protobufjs/dist/minimal/README.md',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

// Fix conti collegati alle anagrafiche
$anagrafiche = $dbo->fetchArray('SELECT * FROM an_anagrafiche');

foreach ($anagrafiche as $anagrafica) {
    $tipologie = explode(',', $anagrafica['tipo']);
    
    if (in_array('Cliente', $tipologie)) {
        $idconto = $dbo->fetchOne('SELECT idconto_cliente FROM an_anagrafiche WHERE idanagrafica = ?', [$anagrafica['idanagrafica']])['idconto_cliente'];
        if (!$idconto) {
            $conto_default = setting('Conto predefinito per fatture di vendita');
            if ($conto_default) {
                $dbo->query('UPDATE an_anagrafiche SET idconto_cliente = ? WHERE idanagrafica = ?', [$conto_default, $anagrafica['idanagrafica']]);
            }
        }
    }

    if (in_array('Fornitore', $tipologie)) {
        $idconto = $dbo->fetchOne('SELECT idconto_fornitore FROM an_anagrafiche WHERE idanagrafica = ?', [$anagrafica['idanagrafica']])['idconto_fornitore'];
        if (!$idconto) {
            $conto_default = setting('Conto predefinito per fatture di acquisto');
            if ($conto_default) {
                $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore = ? WHERE idanagrafica = ?', [$conto_default, $anagrafica['idanagrafica']]);
            }
        }
    }
}

// Fix conto per registrazione contabile associate ai conti riepilogativi
$riepilogativo_fornitori = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo fornitori"');
$riepilogativo_fornitori = $riepilogativo_fornitori['id'] ?? null;

$riepilogativo_clienti = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE descrizione = "Riepilogativo clienti"');
$riepilogativo_clienti = $riepilogativo_clienti['id'] ?? null;

$fatture = [];
if ($riepilogativo_fornitori && $riepilogativo_clienti) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` IN (?, ?)', [$riepilogativo_clienti, $riepilogativo_fornitori]);
} elseif ($riepilogativo_fornitori) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` = ?', [$riepilogativo_fornitori]);
} elseif ($riepilogativo_clienti) {
    $fatture = $dbo->fetchArray('SELECT iddocumento FROM `co_movimenti` WHERE `idconto` = ?', [$riepilogativo_clienti]);
}

foreach ($fatture as $fattura) {
    $documento = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id = ?', [$fattura['iddocumento']]);
    
    if ($documento) {
        $anagrafica = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica = ?', [$documento['idanagrafica']]);
        
        if ($anagrafica) {
            $conto_cliente = $anagrafica['idconto_cliente'];
            $conto_fornitore = $anagrafica['idconto_fornitore'];

            if ($conto_fornitore) {
                $dbo->query('UPDATE co_movimenti SET idconto = ? WHERE iddocumento = ? AND idconto = ?', [$conto_fornitore, $documento['id'], $riepilogativo_fornitori]);
            }
            if ($conto_cliente) {
                $dbo->query('UPDATE co_movimenti SET idconto = ? WHERE iddocumento = ? AND idconto = ?', [$conto_cliente, $documento['id'], $riepilogativo_clienti]);
            }
        }
    }
}

// Fix registrazioni contabili associate a conti rimossi
$fatture_senzanome = $dbo->fetchArray('SELECT `iddocumento`, `idconto` FROM `co_movimenti` WHERE `idconto` NOT IN (SELECT `id` FROM `co_pianodeiconti3`) AND `iddocumento` != 0');

foreach ($fatture_senzanome as $fattura) {
    $documento = $dbo->fetchOne('SELECT * FROM co_documenti WHERE id = ?', [$fattura['iddocumento']]);
    
    if ($documento) {
        $anagrafica = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica = ?', [$documento['idanagrafica']]);
        
        if ($anagrafica) {
            $dir = $documento['idtipodocumento'] ? $dbo->fetchOne('SELECT dir FROM co_tipidocumento WHERE id = ?', [$documento['idtipodocumento']])['dir'] : null;
            $conto = ($dir == 'uscita' ? $anagrafica['idconto_fornitore'] : $anagrafica['idconto_cliente']);
            
            if ($conto) {
                $dbo->query('UPDATE co_movimenti SET idconto = ? WHERE iddocumento = ? AND idconto = ?', [$conto, $documento['id'], $fattura['idconto']]);
            }
        }
    } else {
        $dbo->query('DELETE FROM co_movimenti WHERE iddocumento = ?', [$fattura['iddocumento']]);
    }
}
