<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        if (isset($nome) && isset($nota) && isset($colore)) {
            $dbo->query('UPDATE `mg_categorie` SET `nome`='.prepare($nome).', `nota`='.prepare($nota).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'add':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');

        if (isset($nome)) {
            $dbo->query('INSERT INTO `mg_categorie` (`nome`, `colore`, `nota`) VALUES ('.prepare($nome).', '.prepare($colore).', '.prepare($nota).')');

            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE `id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT id FROM `mg_categorie` WHERE `parent`='.prepare($id).')') == 0) {
            $dbo->query('DELETE FROM `mg_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono ancora alcuni articoli sotto questa categoria!'));
        }

        break;

    case 'row':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $original = filter('id_original');

        if (isset($nome) && isset($nota) && isset($colore)) {
            if (isset($id_record)) {
                $dbo->query('UPDATE `mg_categorie` SET `nome`='.prepare($nome).', `nota`='.prepare($nota).', `colore`='.prepare($colore).' WHERE `id`='.prepare($id_record));
            } else {
                $dbo->query('INSERT INTO `mg_categorie` (`nome`,`nota`,`colore`, `parent`) VALUES ('.prepare($nome).', '.prepare($nota).', '.prepare($colore).', '.prepare($original).')');

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $nome]);
                }
            }
            flash()->info(tr('Salvataggio completato!'));
            $id_record = $original;
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        break;
}
