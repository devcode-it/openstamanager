<?php

include_once __DIR__.'/init.php';

use Plugins\ExportFE\Interaction;

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

    case 'send':
        $result = Interaction::sendXML($id_record);

        // Aggiornamento dello stato
        database()->update('co_documenti', [
            'codice_stato_fe' => 'WAIT',
        ], ['id' => $id_record]);

        echo json_encode([
            'sent' => $result,
        ]);

        break;
}
