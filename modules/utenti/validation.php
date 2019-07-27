<?php

include_once __DIR__.'/../../core.php';

use Models\User;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'username':
        $disponibile = User::where([
            ['username', $value],
            ['idanagrafica', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr("L'username è disponbile") : tr("L'username è  già utilizzato");
        $result = $disponibile;

        // Lunghezza minima del nome utente (username)
        $min_length_username = 4;
        if (strlen($value) < $min_length_username) {
            $message .= '. '.tr("Lunghezza dell'username non sufficiente").'.';
            $result = false;
        }

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;
}
