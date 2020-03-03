<?php

include_once __DIR__.'/../../core.php';

use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

switch (filter('op')) {
    case 'import':
        $list = Interaction::getReceiptList();

        $results = [];
        foreach ($list as $element) {
            $name = $element['name'];
            Interaction::getReceipt($name);

            $fattura = null;
            try {
                $receipt = new Ricevuta($name, $content);
                $receipt->save();

                $fattura = $receipt->getFattura()->numero_esterno;

                $receipt->delete();

                Interaction::processReceipt($name);
            } catch (UnexpectedValueException $e) {
            }

            $results[] = [
                'file' => $name,
                'fattura' => $fattura,
            ];
        }

        echo json_encode($results);

        break;

    case 'save':
        $content = file_get_contents($_FILES['blob']['tmp_name']);
        $file = Ricevuta::store($_FILES['blob']['name'], $content);

        $name = $file;

        // no break
    case 'prepare':
        $name = $name ?: get('name');
        Interaction::getReceipt($name);

        $fattura = null;
        try {
            $receipt = new Ricevuta($name, $content);
            $receipt->save();

            $fattura = $receipt->getFattura()->numero_esterno;

            $receipt->delete();

            Interaction::processReceipt($name);
        } catch (UnexpectedValueException $e) {
        }

        echo json_encode([
            'file' => $name,
            'fattura' => $fattura,
        ]);

        break;

    case 'list':
        include __DIR__.'/rows.php';

        break;

    case 'delete':
        $file_id = get('file_id');

        $directory = Ricevuta::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            delete($directory.'/'.$file['name']);
        }

        break;

    case 'process':
        $name = get('name');

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processReceipt($name);
            if (!empty($process_result)) {
                flash()->error($process_result);
            }
        }

        break;
}
