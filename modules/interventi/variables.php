<?php

$rs = $dbo->fetchArray('SELECT *, (SELECT MAX(orario_fine) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data_fine FROM in_interventi WHERE id='.prepare($id_record))[0];

return [
    'codice' => $rs['codice'],
    'richiesta' => $rs['richiesta'],
    'descrizione' => $rs['descrizione'],
    'data richiesta' => date( 'd/m/Y', strtotime($rs['data_richiesta']) ),
    'data fine intervento' => ( empty($rs['data_fine']) ? date('d/m/Y', strtotime($rs['data_richiesta'])) : date('d/m/Y', strtotime($rs['data_fine'])) ),
];
