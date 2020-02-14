<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $is_all_valid = true;

        foreach (post('setting') as $id => $value) {
            $result = Settings::get($id);

            if (preg_match("/multiple\[(.+?)\]/", $result['tipo'], $m)) {
                $value = implode(',', $value);
            }

            //Se è un'impostazione editabile
            if ($result->editable) {
                $is_valid = Settings::setValue($id, $value);

                if (!$is_valid) {
                    // integer
                    if ($result['tipo'] == 'integer') {
                        flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un numero intero!', [
                            '_NAME_' => '"'.$result['nome'].'"',
                        ]));
                    }

                    // list
                    // verifico che il valore scelto sia nella lista enumerata nel db
                    elseif (preg_match("/list\[(.+?)\]/", $result['tipo'], $m)) {
                        flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un compreso tra i valori previsti!', [
                            '_NAME_' => '"'.$result['nome'].'"',
                        ]));
                    }
                }
            }

            $is_all_valid &= $is_valid;
        }

        if ($is_all_valid) {
            flash()->info(tr('Impostazioni aggiornate correttamente!'));
        }

        break;
}
