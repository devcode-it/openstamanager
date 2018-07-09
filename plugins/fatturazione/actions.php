<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'generate':
        try {
            $fattura = new Plugins\Fatturazione\FatturaElettronica($id_record);
            $file = $fattura->save($upload_dir);

            App::flash()->info(tr('Fattura elettronica generata correttamente!'));

            if (!$fattura->isValid()) {
                App::flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!'));
            }
        } catch (UnexpectedValueException $e) {
            App::flash()->error(tr('Impossibile generare la fattura elettronica'));
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
