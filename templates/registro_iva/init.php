<?php

include_once __DIR__.'/../../core.php';

$dir = $_GET['dir'];

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

$tipo = $dir == 'entrata' ? 'vendite': 'acquisti';
$report_name = 'registro_iva_'.$tipo.'.pdf';

$v_iva = [];
$v_totale = [];

$query = 'SELECT *,
       co_documenti.id AS id,
       IF(numero = "", numero_esterno, numero) AS numero,
       SUM(subtotale - sconto) AS subtotale,
       (SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + co_documenti.iva_rivalsainps AS totale,
       SUM(iva) AS iva, an_anagrafiche.ragione_sociale,
       an_anagrafiche.codice AS codice_anagrafica
FROM co_documenti
    INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
    INNER JOIN co_iva ON co_righe_documenti.idiva=co_iva.id
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_documenti.idanagrafica
WHERE dir = '.prepare($dir).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data >= '.prepare($date_start).' AND co_documenti.data <= '.prepare($date_end).'
GROUP BY co_documenti.id, co_righe_documenti.idiva
ORDER BY co_documenti.id, co_documenti.'.(($dir == 'entrata') ? 'data' : 'numero');
$records = $dbo->fetchArray($query);
