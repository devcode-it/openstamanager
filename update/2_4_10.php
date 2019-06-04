<?php

// Fix del calcolo del bollo
$fatture = \Modules\Fatture\Fattura::all();
foreach ($fatture as $fattura) {
    $fattura->save();
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

// File e cartelle deprecate
$files = [
    'modules/automezzi',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
