<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        foreach ($post as $id => $value) {
            $results = $dbo->fetchArray('SELECT * FROM `zz_settings` WHERE `idimpostazione`='.prepare($id).' AND editable = 1');
            $result = $results[0];

            $continue = true;

            // integer
            if ($result['tipo'] == 'integer') {
                if (!preg_match('/^\d*$/', $value)) {
                    App::flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un numero intero!', [
                        '_NAME_' => '"'.$result['nome'].'"',
                    ]));

                    $continue = false;
                }
            }

            // list
            // verifico che il valore scelto sia nella lista enumerata nel db
            elseif (preg_match("/list\[(.+?)\]/", $result['tipo'], $m)) {
                $is_valid = false;
                $m = explode(',', $m[1]);
                for ($i = 0; $i < count($m); ++$i) {
                    if ($m[$i] == $value) {
                        $is_valid = true;
                    }
                }

                if (!$is_valid) {
                    App::flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un compreso tra i valori previsti!', [
                        '_NAME_' => '"'.$result['nome'].'"',
                    ]));

                    $continue = false;
                }
            }

            // Boolean (checkbox)
            elseif ($result['tipo'] == 'boolean') {
                $value = (empty($value) || $value == 'off') ? false : true;
            }

            if (!$continue) {
                $dbo->query('UPDATE `zz_settings` SET `valore`='.prepare($value).' WHERE `idimpostazione`='.prepare($id));
            }
        }

        if ($continue) {
            App::flash()->info(tr('Impostazioni aggiornate correttamente!'));
        }

        break;
}
