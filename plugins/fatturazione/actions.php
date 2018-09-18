<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'generate':
        if (!empty($fattura)) {
            $file = $fattura->save($upload_dir);

            flash()->info(tr('Fattura elettronica generata correttamente!'));

            if (!$fattura->isValid()) {
                flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!'));
            }
        } else {
            flash()->error(tr('Impossibile generare la fattura elettronica'));
        }

        break;

    case 'download':
        download($upload_dir.'/'.$fattura->getFilename());
        break;
}
