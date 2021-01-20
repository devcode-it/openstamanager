<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;

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
                $query = 'INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, dir) VALUES('.prepare($numero).', '.prepare($descrizione).', '.prepare($idpianodeiconti2).', (SELECT dir FROM co_pianodeiconti2 WHERE id='.prepare($idpianodeiconti2).'))';

                if ($dbo->query($query)) {
                    flash()->info(tr('Nuovo conto aggiunto!'));
                }
            } else {
                flash()->error(tr('Il numero scelto è già esistente!'));
            }
        }

        break;

    // Modifica conto di livello 2 e 3 nel partitario
    case 'edit':
        $idconto = post('idconto');
        $idpianodeiconti = post('idpianodeiconti');
        $numero = post('numero');
        $descrizione = post('descrizione');
        $dir = post('dir');

        $lvl = post('lvl');
        if ($lvl == 2) {
            $duplicate_query = 'SELECT numero FROM co_pianodeiconti2 WHERE numero='.prepare($numero).' AND NOT id='.prepare($idconto).' AND idpianodeiconti1='.prepare($idpianodeiconti);

            $update_query = 'UPDATE co_pianodeiconti2 SET numero='.prepare($numero).', descrizione='.prepare($descrizione).', dir='.prepare($dir).' WHERE id='.prepare($idconto);
        } else {
            $duplicate_query = 'SELECT idpianodeiconti2, numero FROM co_pianodeiconti3 WHERE numero='.prepare($numero).' AND NOT id='.prepare($idconto).' AND idpianodeiconti2='.prepare($idpianodeiconti);

            $update_query = 'UPDATE co_pianodeiconti3 SET numero='.prepare($numero).', descrizione='.prepare($descrizione).', percentuale_deducibile='.prepare(post('percentuale_deducibile')).' WHERE id='.prepare($idconto);
        }

        // Controllo che non sia stato usato un numero non valido del conto
        if ($dbo->fetchNum($duplicate_query) == 0) {
            if ($dbo->query($update_query)) {
                flash()->info(tr('Descrizione conto modificata!'));
            }
        } else {
            flash()->error(tr('Il numero scelto è già esistente!'));
        }

        break;

    // Eliminazione conto dal partitario
    case 'del':
        $idconto = post('idconto');

        //Controllo che non esistano movimenti associati al conto
        $movimenti = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE idconto = '.prepare($idconto));

        if ($idconto != '' and empty($movimenti)) {
            //Se elimino il conto lo scollego anche da eventuali anagrafiche (cliente e fornitore)
            $dbo->query('UPDATE an_anagrafiche SET idconto_cliente = NULL WHERE idconto_cliente = '.prepare($idconto));
            $dbo->query('UPDATE an_anagrafiche SET idconto_fornitore = NULL WHERE idconto_fornitore = '.prepare($idconto));

            $query = 'DELETE FROM co_pianodeiconti3 WHERE id='.prepare($idconto);

            if ($dbo->query($query)) {
                flash()->info(tr('Conto eliminato!'));
            }
        }
        break;

    // Apertura bilancio
    case 'apri-bilancio':
        // Eliminazione eventuali movimenti di apertura fatti finora
        $dbo->query('DELETE FROM co_movimenti WHERE is_apertura=1 AND data='.prepare($_SESSION['period_start']));

        $idconto_apertura = setting('Conto per Apertura conti patrimoniali');
        $idconto_chiusura = setting('Conto per Chiusura conti patrimoniali');

        // Lettura di tutti i conti dello stato patrimoniale con saldo != 0
        $conti = $dbo->fetchArray('SELECT co_pianodeiconti3.id, SUM(co_movimenti.totale) AS totale FROM ((co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id) INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) INNER JOIN co_movimenti ON co_pianodeiconti3.id=co_movimenti.idconto WHERE co_pianodeiconti1.descrizione="Patrimoniale" AND data < '.prepare($_SESSION['period_start']).' AND co_pianodeiconti3.id NOT IN('.prepare($idconto_apertura).', '.prepare($idconto_chiusura).') AND is_apertura=0 AND is_chiusura=0 GROUP BY co_pianodeiconti3.id HAVING totale != 0');

        $mastrino = Mastrino::build(tr('Apertura conto'), $_SESSION['period_start'], 0, true);

        $totale = 0;

        foreach ($conti as $conto) {
            if ($conto['totale'] >= 0) {
                $dare = abs($conto['totale']);
                $avere = 0;
            } else {
                $dare = 0;
                $avere = abs($conto['totale']);
            }

            $movimento = Movimento::build($mastrino, $conto['id']);
            $movimento->setTotale($avere, $dare);
            $movimento->is_apertura = true;
            $movimento->save();

            $totale += $conto['totale'];
        }

        // Movimento sul conto di apertura
        $totale = -$totale;

        if ($totale >= 0) {
            $dare = abs($totale);
            $avere = 0;
        } else {
            $dare = 0;
            $avere = abs($totale);
        }

        $movimento = Movimento::build($mastrino, $idconto_apertura);
        $movimento->setTotale($avere, $dare);
        $movimento->is_apertura = true;
        $movimento->save();

        flash()->info(tr('Apertura bilancio completata!'));

        break;

    // Chiusura bilancio
    case 'chiudi-bilancio':
        // Eliminazione eventuali movimenti di chiusura fatti finora
        $dbo->query('DELETE FROM co_movimenti WHERE is_chiusura=1 AND data='.prepare($_SESSION['period_end']));

        $idconto_apertura = setting('Conto per Apertura conti patrimoniali');
        $idconto_chiusura = setting('Conto per Chiusura conti patrimoniali');

        // Lettura di tutti i conti dello stato patrimoniale con saldo != 0
        $conti = $dbo->fetchArray('SELECT co_pianodeiconti3.id, SUM(co_movimenti.totale) AS totale FROM ((co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id) INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) INNER JOIN co_movimenti ON co_pianodeiconti3.id=co_movimenti.idconto WHERE co_pianodeiconti1.descrizione="Patrimoniale" AND data <= '.prepare($_SESSION['period_end']).' AND co_pianodeiconti3.id NOT IN('.prepare($idconto_apertura).', '.prepare($idconto_chiusura).') AND is_apertura=0 AND is_chiusura=0 GROUP BY co_pianodeiconti3.id HAVING totale != 0');

        $mastrino = Mastrino::build(tr('Chiusura conto'), $_SESSION['period_end'], 0, true);

        $totale = 0;

        foreach ($conti as $conto) {
            if ($conto['totale'] < 0) {
                $dare = abs($conto['totale']);
                $avere = 0;
            } else {
                $dare = 0;
                $avere = abs($conto['totale']);
            }

            $movimento = Movimento::build($mastrino, $conto['id']);
            $movimento->setTotale($avere, $dare);
            $movimento->is_chiusura = true;
            $movimento->save();

            $totale += $conto['totale'];
        }

        // Movimento sul conto di chiusura
        //$totale = -$totale;

        if ($totale >= 0) {
            $dare = abs($totale);
            $avere = 0;
        } else {
            $dare = 0;
            $avere = abs($totale);
        }

        $movimento = Movimento::build($mastrino, $idconto_chiusura);
        $movimento->setTotale($avere, $dare);
        $movimento->is_chiusura = true;
        $movimento->save();

        flash()->info(tr('Chiusura bilancio completata!'));

        break;

    case 'aggiorna_reddito':
        $start = post('start');
        $end = post('end');
        $id_conto = post('id_conto');

        $dbo->query('UPDATE co_movimenti
            INNER JOIN co_pianodeiconti3 ON co_pianodeiconti3.id = co_movimenti.idconto
        SET co_movimenti.totale_reddito = (co_movimenti.totale * co_pianodeiconti3.percentuale_deducibile / 100)
        WHERE co_pianodeiconti3.id = '.prepare($id_conto).' AND
            co_movimenti.data BETWEEN '.prepare($start).' AND '.prepare($end));

        break;
}
