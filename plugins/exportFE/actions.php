<?php

include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'generate':
        if (!empty($fattura_pa)) {
            $file = $fattura_pa->save($upload_dir);

            flash()->info(tr('Fattura elettronica generata correttamente!'));

            if (!$fattura_pa->isValid()) {
                $errors = $fattura_pa->getErrors();

                flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!').' '.tr('Controllare i seguenti campi: _LIST_', [
                    '_LIST_' => implode(', ', $errors),
                ]).'.');
            }
        } else {
            flash()->error(tr('Impossibile generare la fattura elettronica'));
        }

        break;
}
