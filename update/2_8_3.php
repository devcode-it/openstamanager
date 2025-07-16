<?php

include __DIR__.'/../config.inc.php';

$dbo->query('ALTER TABLE `zz_categorie_lang` CHANGE `note` `note` VARCHAR(255) NULL');

// Verifica se la colonna 'name' esiste già nella tabella zz_categorie
$column_exists = $dbo->fetchOne('
    SELECT COUNT(*) as cont
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = "zz_categorie"
    AND COLUMN_NAME = "name"
');

// Se la colonna 'name' non esiste, significa che il sistema è stato aggiornato prima del fix
// Procediamo con la correzione
if ($column_exists['cont'] == 0) {
    $dbo->query('ALTER TABLE `zz_categorie` ADD `name` VARCHAR(255) NULL');


    // Estrai tutte le categorie senza colore con is_impianto = 1, ordinate per ID
    $categorie_senza_colore = $dbo->fetchArray('
        SELECT `id`
        FROM `zz_categorie`
        WHERE (`colore` IS NULL OR `colore` = "")
            AND `is_impianto` = 1
            AND `id_parent` IS NULL
        ORDER BY `id` ASC
    ');

    if (!empty($categorie_senza_colore)) {
        // Estrai tutte le traduzioni disponibili per queste categorie, ordinate per come vengono incontrate
        $traduzioni_disponibili = $dbo->fetchArray('
            SELECT DISTINCT `title`, `id_lang`
            FROM `zz_categorie_lang` `lang`
            INNER JOIN `zz_categorie` `cat` ON `lang`.`id_record` = `cat`.`id`
            WHERE (`cat`.`colore` IS NULL OR `cat`.`colore` = "")
                AND `cat`.`is_impianto` = 1
                AND `id_lang` = 1
        ');

        // Cicla ogni categoria senza colore e assegna una traduzione unica in ordine sequenziale
        $indice_traduzione = 0;
        $total_categorie = count($categorie_senza_colore);

        foreach ($categorie_senza_colore as $index => $categoria) {
            $id_categoria = $categoria['id'];
            $nome_categoria = $categoria['name'];

            // Se abbiamo esaurito le traduzioni disponibili, ricomincia dal primo
            if ($indice_traduzione >= count($traduzioni_disponibili)) {
                $indice_traduzione = 0;
            }

            $traduzione_assegnata = $traduzioni_disponibili[$indice_traduzione];
            $title_assegnato = $traduzione_assegnata['title'];
            $id_lang = $traduzione_assegnata['id_lang'];

            // Rimuovi tutte le traduzioni esistenti per questa categoria
            $dbo->query('DELETE FROM `zz_categorie_lang` WHERE `id_record` = '.prepare($id_categoria));

            // Aggiorna anche il campo name nella tabella principale se necessario
            if (empty($nome_categoria) || $nome_categoria != $title_assegnato) {
                $dbo->update('zz_categorie', [
                    'name' => $title_assegnato,
                ], [
                    'id' => $id_categoria,
                ]);

            }

            // Passa alla traduzione successiva
            $indice_traduzione++;
        }
    }
}

