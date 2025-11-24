<?php

include __DIR__.'/../config.inc.php';

// Ottiene tutte le righe con dati aggiuntivi FE
$dati_agiuntivi_fe_query = '
    SELECT
        dati_aggiuntivi_fe, id
    FROM
        `co_righe_documenti`
    WHERE
        dati_aggiuntivi_fe IS NOT NULL
';

$dati_agiuntivi_fe = $dbo->fetchArray($dati_agiuntivi_fe_query);

// Per ogni riga, migra i dati di competenza dai campi JSON ai nuovi campi dedicati
foreach ($dati_agiuntivi_fe as $dato) {
    // Decodifica il JSON dei dati aggiuntivi FE
    $dati = json_decode((string) $dato['dati_aggiuntivi_fe'], true);

    // Caso 1: Entrambe le date di periodo sono presenti
    if (!empty($dati['data_inizio_periodo']) && !empty($dati['data_fine_periodo'])) {
        // Migra entrambe le date nei nuovi campi dedicati
        $query1 = 'UPDATE `co_righe_documenti` SET `data_inizio_competenza` = '.prepare($dati['data_inizio_periodo']).', `data_fine_competenza` = '.prepare($dati['data_fine_periodo']).' WHERE `id` = '.prepare($dato['id']);

        // Rimuove entrambi i periodi dal JSON per evitare duplicazione
        $dati_aggiuntivi_aggiornati = json_encode(array_diff_key($dati, ['data_inizio_periodo' => '', 'data_fine_periodo' => '']));
        $query2 = 'UPDATE `co_righe_documenti` SET `dati_aggiuntivi_fe` = '.prepare($dati_aggiuntivi_aggiornati).' WHERE `id` = '.prepare($dato['id']);

        try {
            $dbo->query($query1);
            $dbo->query($query2);
        } catch (Exception) {
            // Continua con il prossimo record in caso di errore
            continue;
        }
    }
    // Caso 2: Solo data inizio periodo presente
    elseif (!empty($dati['data_inizio_periodo'])) {
        // Migra solo la data di inizio competenza
        $query1 = 'UPDATE `co_righe_documenti` SET `data_inizio_competenza` = '.prepare($dati['data_inizio_periodo']).' WHERE `id` = '.prepare($dato['id']);

        // Rimuove solo data_inizio_periodo dal JSON
        $dati_aggiuntivi_aggiornati = json_encode(array_diff_key($dati, ['data_inizio_periodo' => '']));
        $query2 = 'UPDATE `co_righe_documenti` SET `dati_aggiuntivi_fe` = '.prepare($dati_aggiuntivi_aggiornati).' WHERE `id` = '.prepare($dato['id']);

        try {
            $dbo->query($query1);
            $dbo->query($query2);
        } catch (Exception) {
            // Continua con il prossimo record in caso di errore
            continue;
        }
    }
    // Caso 3: Solo data fine periodo presente
    elseif (!empty($dati['data_fine_periodo'])) {
        // Migra solo la data di fine competenza
        $query1 = 'UPDATE `co_righe_documenti` SET `data_fine_competenza` = '.prepare($dati['data_fine_periodo']).' WHERE `id` = '.prepare($dato['id']);

        // Rimuove solo data_fine_periodo dal JSON
        $dati_aggiuntivi_aggiornati = json_encode(array_diff_key($dati, ['data_fine_periodo' => '']));
        $query2 = 'UPDATE `co_righe_documenti` SET `dati_aggiuntivi_fe` = '.prepare($dati_aggiuntivi_aggiornati).' WHERE `id` = '.prepare($dato['id']);

        try {
            $dbo->query($query1);
            $dbo->query($query2);
        } catch (Exception) {
            // Continua con il prossimo record in caso di errore
            continue;
        }
    }
}
