<?php

include_once __DIR__.'/../../core.php';

use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\Ricevuta;

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

    case 'import':
        $list = Interaction::getReceiptList();

        $results = [];
        foreach ($list as $name) {
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

    case 'list':
        $list = Interaction::getReceiptList();

        echo json_encode($list);

        break;
}
