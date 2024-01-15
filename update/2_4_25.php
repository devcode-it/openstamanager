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
