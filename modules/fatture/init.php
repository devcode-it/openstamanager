<?php

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

if (isset($id_record)) {
    $fattura = Fattura::with('tipo', 'stato')->find($id_record);

    $is_fiscale = false;
    if (!empty($fattura)) {
        $is_fiscale = $fattura->isFiscale();
    }

    $record = $dbo->fetchOne('SELECT co_documenti.*,
        co_tipidocumento.reversed AS is_reversed,
        co_documenti.idagente AS idagente_fattura,
        co_documenti.note,
        co_documenti.note_aggiuntive,
        co_documenti.idpagamento,
        co_documenti.id AS iddocumento,
		co_documenti.split_payment AS split_payment,
        co_statidocumento.descrizione AS `stato`,
        co_tipidocumento.descrizione AS `descrizione_tipodoc`,
        co_pagamenti.riba AS `riba`,
        (SELECT is_fiscale FROM zz_segments WHERE id = id_segment) AS is_fiscale,
        (SELECT descrizione FROM co_ritenutaacconto WHERE id=idritenutaacconto) AS ritenutaacconto_desc,
        (SELECT descrizione FROM co_rivalse WHERE id=idrivalsainps) AS rivalsainps_desc,
        (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causale_desc
    FROM co_documenti
        LEFT OUTER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id
        INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica
        INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
        LEFT JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id
    WHERE co_tipidocumento.dir = '.prepare($dir).' AND co_documenti.id='.prepare($id_record));

    $note_accredito = $dbo->fetchArray("SELECT co_documenti.id, IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM co_documenti JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE reversed = 1 AND ref_documento=".prepare($id_record));
}
