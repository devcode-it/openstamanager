<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $id_lang = (int) Models\Locale::getDefault()->id;
    $record = $dbo->fetchOne(
        'SELECT t.*, COALESCE(l.`title`, t.`descrizione`) AS `title` '
        .'FROM `co_note_spese_tipologie` t '
        .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.prepare($id_lang).' '
        .'WHERE t.`id` = '.prepare($id_record).' LIMIT 1'
    );
}
