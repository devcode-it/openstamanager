<?php

$skip_permissions = true;

include_once __DIR__.'/../core.php';

use Plugins\ReceiptFE\Interaction;
use Plugins\ReceiptFE\ReceiptHook;
use Plugins\ReceiptFE\Ricevuta;

$list = Interaction::getReceiptList();

if( count($list) == 0){
    echo 'Nessuna ricevuta da importare';
} else {
    echo count($list)." ricevute da importare:\n";

    foreach ($list as $element) {
        $name = $element['name'];
        echo '[*] '.$name."...";
        Interaction::getReceipt($name);

        $fattura = null;
        try {
            $receipt = new Ricevuta($name, $content);
            $receipt->save();
            
            $fattura = $receipt->getFattura()->numero_esterno;
            
            $receipt->delete();
            
            Interaction::processReceipt($name);
            echo "OK\n";
        } catch (UnexpectedValueException $e) {
            echo "ERRORE\n";
        }

    }
}