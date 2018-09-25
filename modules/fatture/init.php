<?php

include_once __DIR__.'/../../core.php';

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

if (isset($id_record)) {
    $fattura = Modules\Fatture\Fattura::with('tipo', 'stato')->find($id_record);
    //$fattura->articoli();

    $record = $dbo->fetchOne('SELECT *, co_tipidocumento.reversed AS is_reversed, co_documenti.idagente AS idagente_fattura, co_documenti.note, co_documenti.note_aggiuntive, co_documenti.idpagamento, co_documenti.id AS iddocumento, co_statidocumento.descrizione AS `stato`, co_tipidocumento.descrizione AS `descrizione_tipodoc`, (SELECT descrizione FROM co_ritenutaacconto WHERE id=idritenutaacconto) AS ritenutaacconto_desc, (SELECT descrizione FROM co_rivalsainps WHERE id=idrivalsainps) AS rivalsainps_desc FROM ((co_documenti LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id) INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir = '.prepare($dir).' AND co_documenti.id='.prepare($id_record));

    $note_accredito = $dbo->fetchArray("SELECT co_documenti.id, IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE reversed = 1 AND ref_documento=".prepare($id_record));
}
