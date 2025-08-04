<?php

ob_start();

include_once __DIR__.'/../../core.php';

ob_clean();

// Verifica che l'id_record sia valido
if (empty($id_record) || !is_numeric($id_record)) {
    if (isset($_GET['count_only']) && $_GET['count_only'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'ID record non valido']);
    } else {
        echo '<div class="alert alert-warning">'.tr('ID record non valido').'</div>';
    }
    exit;
}

try {
    // Query per recuperare i documenti collegati (senza duplicare le sessioni di intervento)
    $query = 'SELECT `in_interventi`.`idtipointervento`, id, codice AS numero, data_richiesta AS data, "Attività" AS tipo_documento FROM `in_interventi` WHERE `in_interventi`.`idtipointervento` = '.prepare($id_record).'
    UNION
    SELECT `an_anagrafiche`.`idtipointervento_default` AS `idtipointervento`, idanagrafica AS id, codice, "0000-00-00" AS data, "Anagrafica" AS tipo_documento FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idtipointervento_default` = '.prepare($id_record).'
    UNION
    SELECT `co_preventivi`.`idtipointervento`, id, numero, data_bozza AS data, "Preventivo" AS tipo_documento FROM `co_preventivi` WHERE `co_preventivi`.`idtipointervento` = '.prepare($id_record).'
    UNION
    SELECT `co_promemoria`.`idtipointervento`, idcontratto AS id, numero, data_richiesta AS data, "Promemoria contratto" AS tipo_documento FROM `co_promemoria` LEFT JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id WHERE `co_promemoria`.`idtipointervento` = '.prepare($id_record).'
    ORDER BY `idtipointervento`';

    $elementi = $dbo->fetchArray($query);
} catch (Exception $e) {
    // Gestione errori database
    if (isset($_GET['count_only']) && $_GET['count_only'] == '1') {
        // Pulisci l'output buffer prima di inviare JSON
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'Errore database: '.$e->getMessage()]);
    } else {
        echo '<div class="alert alert-danger">'.tr('Errore nel caricamento dei documenti collegati').': '.$e->getMessage().'</div>';
    }
    exit;
}

// Se è richiesto solo il conteggio
if (isset($_GET['count_only']) && $_GET['count_only'] == '1') {
    // Pulisci completamente l'output buffer
    ob_clean();
    header('Content-Type: application/json');
    $count = count($elementi);
    echo json_encode(['count' => $count]);
    exit;
}

// Se non ci sono elementi, mostra messaggio
if (empty($elementi)) {
    echo '<div class="alert alert-info">'.tr('Nessun documento collegato trovato').'</div>';
    exit;
}

// Rendering della lista documenti
echo '<ul>';

foreach ($elementi as $elemento) {
    $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
        '_DOC_' => $elemento['tipo_documento'],
        '_NUM_' => $elemento['numero'],
        '_DATE_' => Translator::dateToLocale($elemento['data']),
    ]);

    // Determinazione del modulo di destinazione
    $modulo = '';
    switch ($elemento['tipo_documento']) {
        case 'Attività':
            $modulo = 'Interventi';
            break;
        case 'Anagrafica':
            $modulo = 'Anagrafiche';
            break;
        case 'Preventivo':
            $modulo = 'Preventivi';
            break;
        case 'Promemoria contratto':
            $modulo = 'Contratti';
            break;
    }

    $id = $elemento['id'];

    echo '<li>'.Modules::link($modulo, $id, $descrizione).'</li>';
}

echo '</ul>';
