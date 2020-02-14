<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

$file = null;
switch (filter('op')) {
    case 'list':
        $list = Interaction::getRemoteList();

        echo json_encode($list);

        break;

    case 'save':
        $temp_name = $_FILES['blob']['tmp_name'];
        $name = $_FILES['blob']['name'];

        if (ends_with($name, '.zip')) {
            $directory = FatturaElettronica::getImportDirectory();

            Util\Zip::extract($temp_name, $directory);

            // Redirect forzato per l'importazione
            echo json_encode([
                'id' => 1,
            ]);
            exit();
        } else {
            $content = file_get_contents($temp_name);

            $file = FatturaElettronica::store($_FILES['blob']['name'], $content);
        }

        // no break
    case 'prepare':
        if (!isset($file)) {
            $name = filter('name');
            $file = Interaction::getInvoiceFile($name);
        }

        try {
            if (!FatturaElettronica::isValid($file)) {
                echo json_encode([
                    'already' => 1,
                ]);

                return;
            }
        } catch (Exception $e) {
        }

        // Individuazione ID fisico
        $files = Interaction::getFileList();
        foreach ($files as $key => $value) {
            if ($value['name'] == $file) {
                $index = $key;

                break;
            }
        }

        echo json_encode([
            'id' => $index + 1,
        ]);

        break;

    case 'delete':
        $file_id = get('file_id');

        $directory = FatturaElettronica::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            delete($directory.'/'.$file['name']);
        }

        break;

    case 'download':
        $file_id = get('file_id');

        $directory = FatturaElettronica::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            download($directory.'/'.$file['name']);
        }

        break;

    case 'generate':
        $filename = post('filename');

        $info = [
            'id_pagamento' => post('pagamento'),
            'id_segment' => post('id_segment'),
            'id_tipo' => post('id_tipo'),
            'ref_fattura' => post('ref_fattura'),
            'data_registrazione' => post('data_registrazione'),
            'articoli' => post('articoli'),
            'iva' => post('iva'),
            'conto' => post('conto'),
            'movimentazione' => post('movimentazione'),
            'crea_articoli' => post('crea_articoli'),
        ];

        $fattura_pa = FatturaElettronica::manage($filename);
        $id_fattura = $fattura_pa->save($info);

        ricalcola_costiagg_fattura($id_fattura);
        elimina_scadenze($id_fattura);
        elimina_movimenti($id_fattura, 0);
        aggiungi_scadenza($id_fattura);
        aggiungi_movimento($id_fattura, 'uscita');

        $fattura_pa->delete();

        //Aggiorno la tipologia di anagrafica fornitore
        $anagrafica = $dbo->fetchOne('SELECT idanagrafica FROM co_documenti WHERE co_documenti.id='.prepare($id_fattura));
        $rs_t = $dbo->fetchOne("SELECT * FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica=(SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche WHERE an_tipianagrafiche.descrizione='Fornitore') AND idanagrafica=".prepare($anagrafica['idanagrafica']));

        //Se non trovo corrispondenza aggiungo all'anagrafica la tipologia fornitore
        if (empty($rs_t)) {
            $dbo->query("INSERT INTO an_tipianagrafiche_anagrafiche (idtipoanagrafica, idanagrafica) VALUES ((SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche WHERE an_tipianagrafiche.descrizione='Fornitore'), ".prepare($anagrafica['idanagrafica']).')');
        }

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processInvoice($filename);
            if ($process_result != '') {
                flash()->error($process_result);
                redirect(ROOTDIR.'/controller.php?id_module='.$id_module);

                return;
            }
        }

        $files = Interaction::getFileList();
        $file = $files[$id_record - 1];

        if (get('sequence') == null) {
            redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_fattura);
        } elseif (!empty($file)) {
            redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&sequence=1');
        } else {
            flash()->info(tr('Tutte le fatture salvate sono state importate!'));
            redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
        }
        break;

    case 'process':
        $name = get('name');

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processInvoice($name);
            if (!empty($process_result)) {
                flash()->error($process_result);
            }
        }

        break;
}
