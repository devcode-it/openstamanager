<?php

include_once __DIR__.'/../../core.php';

use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

$directory = Uploads::getDirectory($id_module);

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

    case 'generate':
        $fattura_pa = new FatturaElettronica(post('filename'));

        $id_record = $fattura_pa->saveFattura(post('pagamento'), post('id_segment'), post('id_tipo') );
        $fattura_pa->saveRighe(post('articoli'), post('iva'), post('conto'));
        $fattura_pa->getFattura()->updateSconto();

        $fattura_pa->saveAllegati();

        $idrivalsainps = 0;
        $idritenutaacconto = 0;
        $bollo = 0;

        ricalcola_costiagg_fattura($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
        elimina_scadenza($id_record);
        elimina_movimento($id_record, 0);
        aggiungi_scadenza($id_record, post('pagamento'));
        aggiungi_movimento($id_record, 'uscita');

        $fattura_pa->delete();

        redirect(ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record);
        break;

    case 'list':
        include __DIR__.'/rows.php';

        break;
}
