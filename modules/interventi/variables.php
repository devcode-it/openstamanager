<?php

$rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE id='.prepare($id_record))[0];

return [
    'id_anagrafica' => $rs['idanagrafica'],
];
