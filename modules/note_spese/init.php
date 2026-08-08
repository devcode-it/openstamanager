<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $lang = (int) Models\Locale::getDefault()->id;
    $record = $dbo->fetchOne(
        'SELECT n.*, COALESCE(tl.`title`, t.`descrizione`) AS `tipologia`, '
        .'COALESCE(sl.`title`, st.`name`) AS `stato`, st.`name` AS `stato_name`, st.`colore` AS `stato_colore` '
        .'FROM `co_note_spese` n '
        .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
        .'LEFT JOIN `co_note_spese_tipologie_lang` tl ON tl.`id_record` = t.`id` AND tl.`id_lang` = '.prepare($lang).' '
        .'LEFT JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` '
        .'LEFT JOIN `co_note_spese_stati_lang` sl ON sl.`id_record` = st.`id` AND sl.`id_lang` = '.prepare($lang).' '
        .'WHERE n.`id` = '.prepare($id_record)
    );
}
