<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'save':
        $id = Uploads::getFakeID();
        $filename = $upload = Uploads::upload($_FILES['blob'], [
            'name' => tr('Fattura Elettronica'),
            'category' => tr('Fattura Elettronica'),
            'id_module' => $id_module,
            'id_record' => $id,
        ]);

        echo json_encode([
            'id' => $id,
            'filename' => $filename,
            'id_segment' => post('id_segment'),
        ]);

        break;

    case 'generate':
        $id = post('id');
        $filename = post('filename');

        $directory = Uploads::getDirectory($id_module);

        $xml = file_get_contents(DOCROOT.'/'.$directory.'/'.$filename);
        $fattura_pa = new Plugins\ImportPA\FatturaElettronica($xml);

        $id_record = $fattura_pa->saveFattura(post('id_segment'), post('articoli'));

        $fattura_pa->saveRighe(post('articoli'));

        $fattura_pa->saveAllegati($directory);

        Uploads::updateFake($id, $id_record);

        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
        break;
}
