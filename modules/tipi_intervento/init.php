<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM in_tipiintervento WHERE id_tipo_intervento='.prepare($id_record));
}
