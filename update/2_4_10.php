<?php

$database = database();

error_reporting(E_ALL & ~E_WARNING & ~E_CORE_WARNING & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_STRICT);

// Fix del calcolo del bollo
$fatture = $database->fetchArray('SELECT id, bollo, split_payment FROM co_documenti');
foreach ($fatture as $fattura) {
    $bollo = $fattura['bollo'];

    if (empty($bollo)) {
        if (empty($fattura['split_payment'])) {
            $totale = 'subtotale - sconto + iva + rivalsainps';
        } else {
            $totale = 'subtotale - sconto + rivalsainps';
        }

        $righe = $database->fetchArray('SELECT ('.$totale.') AS netto FROM co_righe_documenti INNER JOIN co_iva ON co_iva.id = co_righe_documenti.idiva WHERE iddocumento = '.prepare($fattura['id'])." AND codice_natura_fe IN ('N1', 'N2', 'N3', 'N4')");
        $totale = sum(array_column($righe, 'netto'));
        $importo_bollo = setting('Importo marca da bollo');
        if (abs($importo_bollo) > 0 && abs($totale) > setting("Soglia minima per l'applicazione della marca da bollo")) {
            $bollo = $importo_bollo;
        }
    }

    $bollo = floatval($bollo);
    if ($bollo > 0) {
        $fatture = $database->query(
            'insert into `co_righe_documenti` (`iddocumento`, `order`, `descrizione`, `um`, `idiva`, `idconto`, `calcolo_ritenuta_acconto`, `idritenutaacconto`, `ritenuta_contributi`, `idrivalsainps`, `prezzo_unitario_acquisto`, `sconto_unitario`, `tipo_sconto`, `qta`, `data_inizio_periodo`, `data_fine_periodo`, `riferimento_amministrazione`, `tipo_cessione_prestazione`, `ritenutaacconto`, `rivalsainps`, `subtotale`, `sconto`, `iva`, `desc_iva`, `iva_indetraibile`, `created_at`) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                0 => $fattura['id'],
                1 => 0,
                2 => 'Marca da bollo',
                3 => null,
                4 => '110',
                5 => '99',
                6 => null,
                7 => null,
                8 => false,
                9 => null,
                10 => 0,
                11 => 0.0,
                12 => 'PRC',
                13 => 1.0,
                14 => null,
                15 => null,
                16 => '',
                17 => '',
                18 => 0.0,
                19 => 0.0,
                20 => $bollo,
                21 => 0.0,
                22 => 0.0,
                23 => 'Escluso art. 15',
                24 => 0.0,
                25 => '2020-10-17 10:00:00',
            ]);
        $id_riga_bollo = $database->lastInsertedId();

        $database->query('UPDATE co_documenti SET id_riga_bollo = '.prepare($id_riga_bollo).' WHERE id = '.prepare($fattura['id']));
    }
}

// Fix per le relazioni tariffe-tecnici
$tecnici = $database->fetchArray("SELECT DISTINCT(an_anagrafiche.idanagrafica) AS id FROM an_anagrafiche
    INNER JOIN an_tipianagrafiche ON an_anagrafiche.idanagrafica = an_anagrafiche.idanagrafica
    INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica = an_tipianagrafiche_anagrafiche.idtipoanagrafica
WHERE an_tipianagrafiche.descrizione = 'Tecnico'");
foreach ($tecnici as $tecnico) {
    $presenti = $database->fetchArray('SELECT idtipointervento AS id FROM in_tariffe WHERE idtecnico = '.prepare($tecnico['id']));

    // Aggiunta associazioni costi unitari al contratto
    $query = 'SELECT in_tipiintervento.*, in_tipiintervento.idtipointervento AS id FROM in_tipiintervento';
    $elenco_presenti = array_column($presenti, 'id');
    if (!empty($elenco_presenti)) {
        $query .= ' WHERE idtipointervento NOT IN ('.implode(', ', $elenco_presenti).')';
    }
    $tipi = $database->fetchArray($query);

    foreach ($tipi as $tipo) {
        $database->insert('in_tariffe', [
            'idtecnico' => $tecnico['id'],
            'idtipointervento' => $tipo['id'],
            'costo_ore' => $tipo['costo_orario'],
            'costo_km' => $tipo['costo_km'],
            'costo_dirittochiamata' => $tipo['costo_diritto_chiamata'],
            'costo_ore_tecnico' => $tipo['costo_orario_tecnico'],
            'costo_km_tecnico' => $tipo['costo_km_tecnico'],
            'costo_dirittochiamata_tecnico' => $tipo['costo_diritto_chiamata_tecnico'],
        ]);
    }
}

// Spostamento automezzi su sedi
$automezzi = $database->fetchArray('SELECT * FROM dt_automezzi');
foreach ($automezzi as $automezzo) {
    $nomesede = [];

    (!empty($automezzo['nome'])) ? $nomesede[] = $automezzo['nome'] : null;
    (!empty($automezzo['descrizione'])) ? $nomesede[] = $automezzo['descrizione'] : null;
    (!empty($automezzo['targa'])) ? $nomesede[] = $automezzo['targa'] : null;

    $database->insert(
        'an_sedi',
        [
            'nomesede' => implode(' - ', $nomesede),
            'idanagrafica' => setting('Azienda predefinita'),
        ]
    );

    $idsede = $database->lastInsertedId();

    // Aggiornamento sede di partenza su
    $database->update(
        'in_interventi',
        [
            'idsede_partenza' => $idsede,
        ], [
            'idautomezzo' => $automezzo['id'],
        ]
    );
}

// Aggiornamento della sede azienda nei movimenti degli interventi
$database->query('UPDATE mg_movimenti SET idsede_azienda=(SELECT idsede_partenza FROM in_interventi WHERE in_interventi.id=mg_movimenti.idintervento) WHERE idintervento IS NOT NULL');

// Cancellazione idautomezzo da mg_movimenti e in_interventi
$database->query('ALTER TABLE in_interventi DROP idautomezzo');
$database->query('ALTER TABLE mg_movimenti DROP idautomezzo');
$database->query('ALTER TABLE co_promemoria_articoli DROP idautomezzo');
$database->query('ALTER TABLE co_righe_documenti DROP idautomezzo');
$database->query('ALTER TABLE mg_articoli_interventi DROP idautomezzo');

// Eliminazione tabelle degli automezzi non più usate
$database->query('DROP TABLE mg_articoli_automezzi');
$database->query('DROP TABLE dt_automezzi');
$database->query('DROP TABLE dt_automezzi_tecnici');
$database->query('DELETE FROM zz_modules WHERE name="Automezzi"');

// Rimuovo il codice come indice per in_interventi
$database->query('ALTER TABLE `in_interventi` DROP INDEX `codice`');

// File e cartelle deprecate
$files = [
    'modules\anagrafiche\plugins\statistiche.php',
    'modules\interventi\src\TipoSessione.php',
    'templates\registro_iva\body.php',
    'templates\scadenzario\scadenzario.html',
    'templates\scadenzario\scadenzario_body.html',
    'templates\scadenzario\pdfgen.scadenzario.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
