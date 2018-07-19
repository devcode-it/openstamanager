<?php

include_once __DIR__.'/../../core.php';

$upload_dir = DOCROOT.'/'.Uploads::getDirectory($id_module, $id_plugin);

switch (filter('op')) {
    case 'generate':
        try {
            $fattura = new Plugins\Fatturazione\FatturaElettronica($id_record);
            $file = $fattura->save($upload_dir);

            flash()->info(tr('Fattura elettronica generata correttamente!'));

            if (!$fattura->isValid()) {
                flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!'));
            }
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Impossibile generare la fattura elettronica'));
        }

        break;

    case 'download':
        try {
            $fattura = new Plugins\Fatturazione\FatturaElettronica($id_record);

            download($upload_dir.'/'.$fattura->getFilename());
        } catch (UnexpectedValueException $e) {
        }
        break;
}
