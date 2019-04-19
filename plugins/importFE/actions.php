<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

switch (filter('op')) {
    case 'save':
        $content = file_get_contents($_FILES['blob']['tmp_name']);
        $file = FatturaElettronica::store($_FILES['blob']['name'], $content);

        if (FatturaElettronica::isValid($file)) {
            echo json_encode([
                'filename' => $file,
            ]);
        } else {
            echo json_encode([
                'already' => 1,
            ]);
        }

        break;

    case 'prepare':
        $name = get('name');
        $file = Interaction::getImportXML($name);

        if (FatturaElettronica::isValid($file)) {
            echo json_encode([
                'filename' => $file,
            ]);
        } else {
            echo json_encode([
                'already' => 1,
            ]);
        }

        break;

    case 'delete':
        $directory = FatturaElettronica::getImportDirectory();

        delete($directory.'/'.get('name'));

        break;

    case 'generate':
        $filename = post('filename');

        $info = [
            'id_pagamento' => post('pagamento'),
            'id_segment' => post('id_segment'),
            'id_tipo' => post('id_tipo'),
            'articoli' => post('articoli'),
            'iva' => post('iva'),
            'conto' => post('conto'),
            'movimentazione' => post('movimentazione'),
        ];

        $fattura_pa = FatturaElettronica::manage($filename);
        $id_record = $fattura_pa->save($info);

        ricalcola_costiagg_fattura($id_record);
        elimina_scadenza($id_record);
        elimina_movimento($id_record, 0);
        aggiungi_scadenza($id_record);
        aggiungi_movimento($id_record, 'uscita');

        $fattura_pa->delete();

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processXML($filename);
            if ($process_result != '') {
                flash()->error($process_result);
                redirect(ROOTDIR.'/controller.php?id_module='.$id_module);
                exit;
            }
        }

        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
        break;

    case 'list':
        include __DIR__.'/rows.php';

        break;

    case 'process':
        $name = get('name');

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processXML($name);
            if (!empty($process_resul)) {
                flash()->error($process_result);
            }
        }

        break;

}
