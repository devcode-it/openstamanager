<?php

use Modules\AttributiCombinazioni\Attributo;
use Modules\AttributiCombinazioni\ValoreAttributo;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $nome = post('nome');
        $esistente = Attributo::where('nome', '=', $nome)->count() !== 0;

        if (!$esistente) {
            $attributo = Attributo::build();
            $attributo->nome = $nome;
            $attributo->titolo = post('titolo');
            $attributo->save();

            $id_record = $attributo->id;

            flash()->info(tr('Nuovo attributo creato correttamente!'));
        } else {
            flash()->error(tr('Attributo esistente con lo stesso nome!'));
        }

        break;

    case 'update':
        $attributo->titolo = post('titolo');
        $attributo->save();

        flash()->info(tr('Attributo aggiornato correttamente!'));

        break;

    case 'delete':
        $attributo->delete();

        flash()->info(tr('Attributo rimosso correttamente!'));

        break;

    case 'gestione-valore':
        $id_valore = filter('id_valore');
        $nome = post('nome');

        if (!empty($id_valore)) {
            $valore = ValoreAttributo::find($id_valore);
            $valore->nome = $nome;
            $valore->save();
        } else {
            $valore = ValoreAttributo::build($attributo, $nome);
        }

        flash()->info(tr('Valore aggiornato correttamente!'));

        break;

    case 'rimuovi-valore':
        $id_valore = filter('id_valore');

        if (!empty($id_valore)) {
            $valore = ValoreAttributo::find($id_valore);
            $valore->delete();
        }

        flash()->info(tr('Valore rimosso correttamente!'));

        break;
}
