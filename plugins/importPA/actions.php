<?php

include_once __DIR__.'/../../core.php';

$directory = Uploads::getDirectory($id_module);

switch (filter('op')) {
    case 'save':
        $id = Uploads::getFakeID();
        $filename = $upload = Uploads::upload($_FILES['blob'], [
            'name' => tr('Fattura Elettronica'),
            'category' => tr('Fattura Elettronica'),
            'id_module' => $id_module,
            'id_record' => $id,
        ]);

        try {
            $xml = file_get_contents(DOCROOT.'/'.$directory.'/'.$filename);
            $fattura_pa = new Plugins\ImportPA\FatturaElettronica($xml, post('id_segment'));

            echo json_encode([
                'id' => $id,
                'filename' => $filename,
                'id_segment' => post('id_segment'),
            ]);
        } catch (UnexpectedValueException $e) {
            echo json_encode([
                'already' => 1
            ]);
        }

        break;

    case 'generate':
        $id = post('id');
        $filename = post('filename');

        $xml = file_get_contents(DOCROOT.'/'.$directory.'/'.$filename);
        $fattura_pa = new Plugins\ImportPA\FatturaElettronica($xml, post('id_segment'));

        $id_record = $fattura_pa->saveFattura();

        $fattura_pa->saveRighe(post('articoli'), post('iva'));
        $fattura_pa->getFattura()->updateSconto();

        $fattura_pa->saveAllegati($directory);

        Uploads::updateFake($id, $id_record);

        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
        break;
}
