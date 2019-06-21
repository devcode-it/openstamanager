<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($nome)) {
            if ($dbo->fetchNum('SELECT * FROM `co_tipiscadenze` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {
                $predefined = post('predefined');
                if (!empty($predefined)) {
                    $dbo->query('UPDATE co_tipiscadenze SET predefined = 0');
                }

                $dbo->update('co_tipiscadenze', [
                    'nome' => $nome,
                    'descrizione' => $descrizione,
                    'predefined' => $predefined,
                ], ['id' => $id_record]);

                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso nome", [
                    '_TYPE_' => 'scadenza',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio.'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($descrizione)) {
            if ($dbo->fetchNum('SELECT * FROM `co_tipiscadenze` WHERE `descrizione`='.prepare($descrizione)) == 0) {
                $dbo->insert('co_tipiscadenze', [
                    'nome' => $nome,
                    'descrizione' => $descrizione,
                ]);
                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $nome, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'scadenza',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con lo stesso nome", [
                    '_TYPE_' => 'scadenza',
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':

        $documenti = $dbo->fetchNum('SELECT id FROM co_scadenzario WHERE tipo='.prepare($id_record));

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `co_tipiscadenze` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'scadenza',
            ]));
        } else {
            flash()->error(tr('Sono presenti delle scadenze collegate a questo tipo di scadenza'));
        }

        break;
}
