<?php

include_once __DIR__.'/../../core.php';

use Plugins\ReceiptFE\Ricevuta;
use Plugins\ReceiptFE\Interaction;

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
            $file = Interaction::getReceipt($name);

            $result = [];
            if (!empty($file)) {
                try {
                    $receipt = new Ricevuta($file['content']);

                    $result = [
                        'fattura' => $receipt->getFattura()->numero_esterno,
                    ];
                } catch (UnexpectedValueException $e) {
                }
            }

            $results[] = array_merge([
                'file' => $name,
            ], $result);
        }

        echo json_encode($results);

        break;

    case 'list':
        $list = Interaction::getReceiptList();

        echo json_encode($list);

        break;
}
