<?php

include_once __DIR__.'/init.php';

use Plugins\ExportFE\Interaction;
use Plugins\ReceiptFE\Interaction as RecepitInteraction;
use Plugins\ReceiptFE\Ricevuta;

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
        $result = Interaction::sendInvoice($id_record);

        echo json_encode($result);

        break;

    case 'verify':
        $result = Interaction::getInvoiceRecepits($id_record);
        $last_recepit = $result['results'][0];

        // Messaggi relativi
        if (empty($last_recepit)) {
            echo json_encode($result);

            return;
        }

        // Importazione ultima ricevuta individuata
        RecepitInteraction::getReceipt($last_recepit);

        $fattura = null;
        try {
            $receipt = new Ricevuta($last_recepit);
            $receipt->save();

            $fattura = $receipt->getFattura()->numero_esterno;

            $receipt->delete();

            RecepitInteraction::processReceipt($name);
        } catch (UnexpectedValueException $e) {
        }

        echo json_encode([
            'file' => $last_recepit,
            'fattura' => $fattura,
        ]);

        break;
}
