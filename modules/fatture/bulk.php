<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'export-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $numero = !empty($r['numero_esterno']) ? $r['numero_esterno'] : $r['numero'];
                $numero = str_replace(['/', '\\'], '-', $numero);

                // Gestione della stampa
                $rapportino_nome = sanitizeFilename($numero.' '.$r['data'].' '.$r['ragione_sociale'].'.pdf');
                $filename = slashes($dir.'tmp/'.$rapportino_nome);

                $print = Prints::getModuleMainPrint($id_module);

                Prints::render($print['id'], $r['id'], $filename);
            }

            $dir = slashes($dir);
            $file = slashes($dir.'fatture_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                create_zip($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;

        case 'delete-bulk':

            if ($debug) {
                foreach ($id_records as $id) {
                    $dbo->query('DELETE  FROM co_documenti  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                    $dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
                    $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
                    $dbo->query('DELETE FROM mg_movimenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
                }

                flash()->info(tr('Fatture eliminate!'));
            } else {
                flash()->warning(tr('Procedura in fase di sviluppo. Nessuna modifica apportata.'));
            }

        break;
}

return [
    'delete-bulk' => tr('Elimina selezionati'),

    'export-bulk' => [
        'text' => tr('Esporta stampe'),
        'data' => [
            'msg' => tr('Vuoi davvero esportare tutte le stampe in un archivio?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],
];
