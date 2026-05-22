<?php

use Util\Ini;

// Trasposizione contenuto Componenti precedenti al nuovo formato
$componenti_interessati = $database->fetchArray('SELECT `my_componenti`.`id`, `my_componenti`.`id_componente_vecchio`, `my_impianto_componenti`.`contenuto` FROM `my_componenti`
    INNER JOIN `my_impianto_componenti` ON `my_impianto_componenti`.`id` = `my_componenti`.`id_componente_vecchio`
WHERE `id_componente_vecchio` IS NOT NULL');
foreach ($componenti_interessati as $componente) {
    $note = '';

    // Lettura da impostazioni INI
    $array = Ini::read($componente['contenuto']);
    foreach ($array as $nome => $c) {
        $note .= '<p>'.$nome.': '.$array[$nome]['valore'].'</p>\\n';
    }

    // Lettura informazioni interne
    $data_installazione = $array['Data di installazione']['valore'] ?: null;

    // Aggiornmaneto note
    $database->update('my_componenti', [
        'note' => $note,
        'data_installazione' => $data_installazione,
    ], ['id' => $componente['id']]);
}

// Rimozione dati deprecati
// $database->query('ALTER TABLE `my_componenti` DROP `pre_id_articolo`, DROP `id_componente_vecchio`');
// $database->query('DROP TABLE `my_impianto_componenti`');

// Rinomina foreign key dopo RENAME TABLE
$fk_renames = [
    [
        'table' => 'em_list_receiver',
        'old_fk' => 'em_list_anagrafica_ibfk_1',
        'new_fk' => 'em_list_receiver_ibfk_1',
        'column' => 'id_list',
        'ref_table' => 'em_lists',
        'ref_column' => 'id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT',
    ],
    [
        'table' => 'em_newsletter_receiver',
        'old_fk' => 'em_newsletter_anagrafica_ibfk_3',
        'new_fk' => 'em_newsletter_receiver_ibfk_5',
        'column' => 'id_newsletter',
        'ref_table' => 'em_newsletters',
        'ref_column' => 'id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT',
    ],
    [
        'table' => 'em_newsletter_receiver',
        'old_fk' => 'em_newsletter_anagrafica_ibfk_5',
        'new_fk' => 'em_newsletter_receiver_ibfk_1',
        'column' => 'id_email',
        'ref_table' => 'em_emails',
        'ref_column' => 'id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT',
    ],
];

foreach ($fk_renames as $fk) {
    $exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk['table']).' AND CONSTRAINT_NAME = '.prepare($fk['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
    if (!empty($exists)) {
        $on_update = !empty($fk['on_update']) ? ' ON UPDATE '.$fk['on_update'] : '';
        $database->query('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['old_fk'].'`, ADD CONSTRAINT `'.$fk['new_fk'].'` FOREIGN KEY (`'.$fk['column'].'`) REFERENCES `'.$fk['ref_table'].'`(`'.$fk['ref_column'].'`) ON DELETE '.$fk['on_delete'].$on_update);
    }
}

// Aggiunta foreign key em_newsletter_receiver_ibfk_1 se non rinominata da em_newsletter_anagrafica_ibfk_3
$exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'em_newsletter_receiver\' AND CONSTRAINT_NAME = \'em_newsletter_receiver_ibfk_1\' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
if (empty($exists)) {
    $database->query('ALTER TABLE `em_newsletter_receiver` ADD CONSTRAINT `em_newsletter_receiver_ibfk_1` FOREIGN KEY (`id_newsletter`) REFERENCES `em_newsletters`(`id`) ON DELETE CASCADE');
}
