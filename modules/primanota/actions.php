<?php

include_once __DIR__.'/../../core.php';

use Modules\PrimaNota\Movimento;
use Modules\PrimaNota\PrimaNota;
use Modules\Scadenzario\Scadenza;

switch (post('op')) {
    case 'add':
        $data = post('data');
        $descrizione = post('descrizione');
        $is_insoluto = post('is_insoluto');

        $prima_nota = PrimaNota::build($descrizione, $data, $is_insoluto, true);

        $conti = post('idconto');
        foreach ($conti as $i => $id_conto) {
            $id_scadenza = post('id_scadenza')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            $scadenza = Scadenza::find($id_scadenza);

            $movimento = Movimento::build($prima_nota, $id_conto, $scadenza);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        $prima_nota->aggiornaScadenzario();

        $id_record = $prima_nota->id;

        flash()->info(tr('Movimento aggiunto in prima nota!'));

        // Creo il modello di prima nota
        if (!empty(post('crea_modello'))) {
            if (empty(post('idmastrino'))) {
                $idmastrino = get_new_idmastrino('co_movimenti_modelli');
            } else {
                $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare(post('idmastrino')));
                $idmastrino = post('idmastrino');
            }

            for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
                $idconto = post('idconto')[$i];
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($descrizione).', '.prepare($idconto).')';
                $dbo->query($query);
            }
        }

        break;

    case 'update':
        $data = post('data');
        $descrizione = post('descrizione');

        $prima_nota->descrizione = $descrizione;
        $prima_nota->data = $data;

        $prima_nota->cleanup();

        $conti = post('idconto');
        foreach ($conti as $i => $id_conto) {
            $id_scadenza = post('id_scadenza')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            $scadenza = Scadenza::find($id_scadenza);

            $movimento = Movimento::build($prima_nota, $id_conto, $scadenza);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        $prima_nota->aggiornaScadenzario();

        flash()->info(tr('Movimento modificato in prima nota!'));
        break;

    // eliminazione movimento prima nota
    case 'delete':
        $prima_nota->delete();
        break;
}
