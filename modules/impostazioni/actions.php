<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        foreach ($post as $id => $value) {
            $results = $dbo->fetchArray('SELECT * FROM `zz_settings` WHERE `idimpostazione`='.prepare($id).' AND editable = 1');
            $result = $results[0];

            // integer
            if ($result['tipo'] == 'integer') {
                if (!preg_match('/^\d*$/', $value)) {
                    $_SESSION['errors'][] = str_replace('_NAME_', '"'.$result['nome'].'"', tr('Il valore inserito del parametro _NAME_ deve essere un numero intero!'));
                }
            }

            // list
            // verifico che il valore scelto sia nella lista enumerata nel db
            elseif (preg_match("/list\[(.+?)\]/", $result['tipo'], $m)) {
                $continue = false;
                $m = explode(',', $m[1]);
                for ($i = 0; $i < count($m); ++$i) {
                    if ($m[$i] == $value) {
                        $continue = true;
                    }
                }

                if (!$continue) {
                    $_SESSION['errors'][] = str_replace('_NAME_', '"'.$result['nome'].'"', tr('Il valore inserito del parametro _NAME_ deve essere un compreso tra i valori previsti!'));
                }
            }

            // Boolean (checkbox)
            elseif ($result['tipo'] == 'boolean') {
                $value = (empty($value) || $value == 'off') ? false : true;
            }

            if (empty($_SESSION['errors'])) {
                $dbo->query('UPDATE `zz_settings` SET `valore`='.prepare($value).' WHERE `idimpostazione`='.prepare($id));
            }
        }

        if (count($_SESSION['errors']) <= 0) {
            $_SESSION['infos'][] = tr('Impostazioni aggiornate correttamente!');
        }

        break;
}
