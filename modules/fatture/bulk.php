<?php

include_once __DIR__.'/../../core.php';

use Util\Zip;
use Modules\Fatture\Fattura;

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

                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $filename);
            }

            $dir = slashes($dir);
            $file = slashes($dir.'fatture_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;

    case 'delete-bulk':
        if (App::debug()) {
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

    case 'export-xml-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir);
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');
        $dir = slashes($dir);

        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $id_module = Modules::getCurrent()["id"];
                $upload_dir = DOCROOT . '/' . Uploads::getDirectory($id_module);
                if ($id_module == 14) {
                    try {
                        $fe = new \Plugins\ExportFE\FatturaElettronica($fattura->id);
                    } catch (UnexpectedValueException $e) {
                        flash()->warning("La fattura elettronica " . $fattura->numero_esterno . " creata in data " . $fattura->data . " indirizzata al cliente " . $fattura->anagrafica->ragione_sociale . " non è ancora stata generata, pertanto non è stata inclusa nell'archivio");
                        continue;
                    }

                    $file = slashes($upload_dir . '/' . $fe->getFilename());
                    $dest = slashes($dir . '/tmp/' . $fe->getFilename());
                } else {
                    $data = $dbo->fetchOne("SELECT filename, original FROM zz_files WHERE name='Fattura Elettronica' AND id_module=15 AND id_record=" . prepare($fattura->id));
                    $file = slashes($upload_dir . '/' . $data['filename']);
                    $dest = slashes($dir . '/tmp/' . $data["original"]);
                }
                switch (copy($file, $dest)) {
                    case FALSE:
                        flash()->error("Impossibile salvare il file XML della fattura " . $fattura->numero_esterno);
                        break;
                    case TRUE:
                        operationLog("export-xml-bulk", ["id_record" => $r["id"]]);
                        break;
                }
            }

            if (!empty(glob($dir . '/tmp/*.{xml,p7m}', GLOB_BRACE))) {
                $file = slashes($dir . 'fatture_' . time() . '.zip');

                // Creazione zip
                if (extension_loaded('zip')) {
                    Zip::create($dir . 'tmp/', $file);

                    // Invio al browser il file zip
                    download($file);

                    // Rimozione dei contenuti
                    delete($dir . 'tmp/');
                }
            }
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

    'export-xml-bulk' => [
        'text' => tr('Esporta XML'),
        'data' => [
            'msg' => tr('Vuoi davvero esportare tutte le fatture elettroniche in un archivio?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],
];
