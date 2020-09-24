<?php

use Update\v2_4_10\Anagrafica;
use Update\v2_4_10\Fattura;

error_reporting(E_ALL & ~E_WARNING & ~E_CORE_WARNING & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_STRICT);

// Fix del calcolo del bollo
$fatture = Fattura::all();
foreach ($fatture as $fattura) {
    $fattura->manageRigaMarcaDaBollo();
}

// Fix per le relazioni tariffe-tecnici
$tecnici = Anagrafica::fromTipo('Tecnico')->get();
foreach ($tecnici as $tecnico) {
    Anagrafica::fixTecnico($tecnico);
}

// Spostamento automezzi su sedi
$automezzi = $dbo->fetchArray('SELECT * FROM dt_automezzi');
foreach ($automezzi as $automezzo) {
    $nomesede = [];

    (!empty($automezzo['nome'])) ? $nomesede[] = $automezzo['nome'] : null;
    (!empty($automezzo['descrizione'])) ? $nomesede[] = $automezzo['descrizione'] : null;
    (!empty($automezzo['targa'])) ? $nomesede[] = $automezzo['targa'] : null;

    $dbo->insert(
        'an_sedi',
        [
            'nomesede' => implode(' - ', $nomesede),
            'idanagrafica' => setting('Azienda predefinita'),
        ]
    );

    $idsede = $dbo->lastInsertedId();

    // Aggiornamento sede di partenza su
    $dbo->update(
        'in_interventi',
        [
            'idsede_partenza' => $idsede,
        ], [
            'idautomezzo' => $automezzo['id'],
        ]
    );
}

// Aggiornamento della sede azienda nei movimenti degli interventi
$dbo->query('UPDATE mg_movimenti SET idsede_azienda=(SELECT idsede_partenza FROM in_interventi WHERE in_interventi.id=mg_movimenti.idintervento) WHERE idintervento IS NOT NULL');

// Cancellazione idautomezzo da mg_movimenti e in_interventi
$dbo->query('ALTER TABLE in_interventi DROP idautomezzo');
$dbo->query('ALTER TABLE mg_movimenti DROP idautomezzo');
$dbo->query('ALTER TABLE co_promemoria_articoli DROP idautomezzo');
$dbo->query('ALTER TABLE co_righe_documenti DROP idautomezzo');
$dbo->query('ALTER TABLE mg_articoli_interventi DROP idautomezzo');

// Eliminazione tabelle degli automezzi non più usate
$dbo->query('DROP TABLE mg_articoli_automezzi');
$dbo->query('DROP TABLE dt_automezzi');
$dbo->query('DROP TABLE dt_automezzi_tecnici');
$dbo->query('DELETE FROM zz_modules WHERE name="Automezzi"');

//Rimuovo il codice come indice per in_interventi
$dbo->query('ALTER TABLE `in_interventi` DROP INDEX `codice`');

// File e cartelle deprecate
$files = [
    'modules\automezzi',
    'modules\anagrafiche\plugins\statistiche.php',
    'modules\interventi\src\TipoSessione.php',
    'templates\registro_iva\body.php',
    'templates\registro_iva\header.php',
    'templates\scadenzario\scadenzario.html',
    'templates\scadenzario\scadenzario_body.html',
    'templates\scadenzario\pdfgen.scadenzario.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
