<?php

// Ricalcolo ordine riga per ciascun documento
$campi['co_righe_preventivi'] = 'idpreventivo';
$campi['co_righe_documenti'] = 'iddocumento';
$campi['co_righe_contratti'] = 'idcontratto';
$campi['or_righe_ordini'] = 'idordine';
$campi['dt_righe_ddt'] = 'idddt';

foreach ($campi as $tabella => $campo) {
    $documenti = $dbo->fetchArray('SELECT '.$campo.' FROM '.$tabella.' GROUP BY '.$campo);
    foreach ($documenti as $documento) {
        reorderRows($tabella, $campo, $documento);
    }
}
