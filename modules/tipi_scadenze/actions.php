<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($nome)) {
            //Se non esiste già una tipo di scadenza con lo stesso nome
            if ($dbo->fetchNum('SELECT * FROM `co_tipi_scadenze` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {
                //nome_prev
                $nome_prev = $dbo->fetchOne('SELECT nome AS nome_prev FROM `co_tipi_scadenze` WHERE `id`='.prepare($id_record))['nome_prev'];

                $dbo->update('co_tipi_scadenze', [
                    'nome' => $nome,
                    'descrizione' => $descrizione,
                ], ['id' => $id_record]);

                //aggiorno anche il segmento
                $dbo->update('zz_segments', [
                    'clause' => 'co_scadenziario.tipo="'.$nome.'"',
                    'name' => 'Scadenzario '.$nome,
                ], [
                    'clause' => 'co_scadenziario.tipo="'.$nome_prev.'"',
                    'name' => 'Scadenzario '.$nome_prev,
                    'id_module' => Modules::get('Scadenzario')['id'],
                ]);

                flash()->info(tr('Salvataggio completato!'));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con nome: _NOME_", [
                    '_TYPE_' => 'scadenza',
                    '_NOME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio.'));
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');
        $nome = filter('nome');

        if (isset($nome)) {
            //Se non esiste già un tipo di scadenza con lo stesso nome
            if ($dbo->fetchNum('SELECT * FROM `co_tipi_scadenze` WHERE `nome`='.prepare($nome)) == 0) {
                $dbo->insert('co_tipi_scadenze', [
                    'nome' => $nome,
                    'descrizione' => $descrizione,
                ]);
                $id_record = $dbo->lastInsertedID();

                //Aggiungo anche il segmento
                $dbo->insert('zz_segments', [
                    'id_module' => Modules::get('Scadenzario')['id'],
                    'name' => 'Scadenzario '.$nome,
                    'clause' => 'co_scadenziario.tipo="'.$nome.'"',
                    'position' => 'WHR',
                ]);

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $nome, 'text' => $descrizione]);
                }

                flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                    '_TYPE_' => 'scadenza',
                ]));
            } else {
                flash()->error(tr("E' già presente una tipologia di _TYPE_ con nome: _NOME_", [
                    '_TYPE_' => 'scadenza',
                    '_NOME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':

        $documenti = $dbo->fetchNum('SELECT id FROM co_scadenziario WHERE tipo = (SELECT nome FROM co_tipi_scadenze WHERE id = '.prepare($id_record).')');

        if (isset($id_record) && empty($documenti)) {
            $dbo->query('DELETE FROM `co_tipi_scadenze` WHERE `can_delete` = 1 AND `id`='.prepare($id_record));
            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo.', [
                '_TYPE_' => 'scadenza',
            ]));
        } else {
            flash()->error(tr('Sono presenti delle scadenze collegate a questo tipo di scadenza'));
        }

        break;
}
