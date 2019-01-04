<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    // Aggiunta nuovo conto nel partitario
    case 'add':
        $idpianodeiconti2 = post('idpianodeiconti2');
        $numero = post('numero');
        $descrizione = post('descrizione');

        if (post('idpianodeiconti2') !== null) {
            // Controllo che non sia stato usato un numero non valido del conto
            $query = 'SELECT idpianodeiconti2, numero FROM co_pianodeiconti3 WHERE numero='.prepare($numero).' AND idpianodeiconti2='.prepare($idpianodeiconti2);
            $rs = $dbo->fetchArray($query);

            if (sizeof($rs) == 0) {
                $query = 'INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, dir, can_edit, can_delete) VALUES('.prepare($numero).', '.prepare($descrizione).', '.prepare($idpianodeiconti2).', (SELECT dir FROM co_pianodeiconti2 WHERE id='.prepare($idpianodeiconti2).'), 1, 1)';

                if ($dbo->query($query)) {
                    flash()->info(tr('Nuovo conto aggiunto!'));
                }
            } else {
                flash()->error(tr('Il numero scelto è già esistente!'));
            }
        }
        break;

    // Modifica conto nel partitario
    case 'edit':
        $idconto = post('idconto');
        $idpianodeiconti2 = post('idpianodeiconti2');
        $numero = post('numero');
        $descrizione = post('descrizione');

        if ($idconto != '') {
            // Controllo che non sia stato usato un numero non valido del conto
            $query = 'SELECT idpianodeiconti2, numero FROM co_pianodeiconti3 WHERE numero='.prepare($numero).' AND NOT id='.prepare($idconto).' AND idpianodeiconti2='.prepare($idpianodeiconti2);

            if ($dbo->fetchNum($query) == 0) {
                $query = 'UPDATE co_pianodeiconti3 SET numero='.prepare($numero).', descrizione='.prepare($descrizione).' WHERE id='.prepare($idconto);

                if ($dbo->query($query)) {
                    flash()->info(tr('Descrizione conto modificata!'));
                }
            } else {
                flash()->error(tr('Il numero scelto è già esistente!'));
            }
        }

        break;

    // Eliminazione conto dal partitario
    case 'del':
        $idconto = post('idconto');

        if ($idconto != '') {
            $query = 'DELETE FROM co_pianodeiconti3 WHERE id='.prepare($idconto);

            if ($dbo->query($query)) {
                flash()->info(tr('Conto eliminato!'));
            }
        }
        break;
}
