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
        $id_conto = post('id_conto');
        $numero = post('numero');
        $descrizione = post('descrizione');
        $lvl = post('lvl');
        $percentuale = post('percentuale_deducibile') ?: 0;
        $dir = post('dir');

        if (post('id_conto') !== null) {
            if ($lvl == '2') {
                // Controllo che non sia stato usato un numero non valido del conto
                $query = 'SELECT idpianodeiconti1, numero FROM co_pianodeiconti2 WHERE numero='.prepare($numero).' AND idpianodeiconti1='.prepare($id_conto);
                $rs = $dbo->fetchArray($query);

                if (sizeof($rs) == 0) {
                    $query = 'INSERT INTO co_pianodeiconti2(numero, descrizione, idpianodeiconti1, dir) VALUES('.prepare($numero).', '.prepare($descrizione).', '.prepare($id_conto).', '.prepare($dir).')';
                }
            } else {
                // Controllo che non sia stato usato un numero non valido del conto
                $query = 'SELECT idpianodeiconti2, numero FROM co_pianodeiconti3 WHERE numero='.prepare($numero).' AND idpianodeiconti2='.prepare($id_conto);
                $rs = $dbo->fetchArray($query);

                if (sizeof($rs) == 0) {
                    $query = 'INSERT INTO co_pianodeiconti3(numero, descrizione, idpianodeiconti2, dir, percentuale_deducibile) VALUES('.prepare($numero).', '.prepare($descrizione).', '.prepare($id_conto).', (SELECT dir FROM co_pianodeiconti2 WHERE id='.prepare($id_conto).'), '.$percentuale.')';
                }
            }

            if ($dbo->query($query)) {
                flash()->info(tr('Nuovo conto aggiunto!'));
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
        $conto_bloccato = post('conto_bloccato');
        $percentuale = post('percentuale_deducibile') ?: 0;

        $lvl = post('lvl');

        if ($conto_bloccato) {
            if ($lvl == 2) {
                $original_query = 'SELECT descrizione FROM co_pianodeiconti2 WHERE id='.prepare($idconto);
            } else {
                $original_query = 'SELECT descrizione FROM co_pianodeiconti3 WHERE id='.prepare($idconto);
            }
            $original = $dbo->fetchOne($original_query);
            $descrizione = $original['descrizione'];
        }

        if ($lvl == 2) {
            $duplicate_query = 'SELECT numero FROM co_pianodeiconti2 WHERE numero='.prepare($numero).' AND NOT id='.prepare($idconto).' AND idpianodeiconti1='.prepare($idpianodeiconti);

            $update_query = 'UPDATE co_pianodeiconti2 SET numero='.prepare($numero).', descrizione='.prepare($descrizione).', dir='.prepare($dir).' WHERE id='.prepare($idconto);
        } else {
            $duplicate_query = 'SELECT idpianodeiconti2, numero FROM co_pianodeiconti3 WHERE numero='.prepare($numero).' AND NOT id='.prepare($idconto).' AND idpianodeiconti2='.prepare($idpianodeiconti);

            $update_query = 'UPDATE co_pianodeiconti3 SET numero='.prepare($numero).', descrizione='.prepare($descrizione).', percentuale_deducibile='.prepare($percentuale).' WHERE id='.prepare($idconto);
        }

        // Controllo che non sia stato usato un numero non valido del conto
        if ($dbo->fetchNum($duplicate_query) == 0) {
            if ($dbo->query($update_query)) {
                if ($conto_bloccato) {
                    flash()->info(tr('Conto speciale aggiornato! La descrizione non è stata modificata.'));
                } else {
                    flash()->info(tr('Descrizione conto modificata!'));
                }
            }
        } else {
            flash()->error(tr('Il numero scelto è già esistente!'));
        }

        break;

        // Eliminazione conto dal partitario
    case 'del':
        $idconto = post('idconto');
        $lvl = post('lvl') ?: 3;
        if ($lvl == 2) {
            // Eliminazione conto di livello 2 (co_pianodeiconti2)
            // Controllo che non esistano movimenti associati ai conti di livello 3 collegati
            $movimenti = $dbo->table('co_movimenti')
                ->join('co_pianodeiconti3', 'co_movimenti.idconto', '=', 'co_pianodeiconti3.id')
                ->where('co_pianodeiconti3.idpianodeiconti2', $idconto)
                ->count();

            if ($idconto != '' and empty($movimenti)) {
                // Prima elimino tutti i conti di livello 3 collegati
                $conti_livello3 = $dbo->table('co_pianodeiconti3')
                    ->where('idpianodeiconti2', $idconto)
                    ->get()
                    ->toArray();

                foreach ($conti_livello3 as $conto3) {
                    // Scollego il conto dalle anagrafiche
                    $dbo->table('an_anagrafiche')
                        ->where('idconto_cliente', $conto3->id)
                        ->update(['idconto_cliente' => null]);
                    $dbo->table('an_anagrafiche')
                        ->where('idconto_fornitore', $conto3->id)
                        ->update(['idconto_fornitore' => null]);
                }

                // Elimino tutti i conti di livello 3 collegati
                $deleted_l3 = $dbo->table('co_pianodeiconti3')
                    ->where('idpianodeiconti2', $idconto)
                    ->delete();

                // Infine elimino il conto di livello 2
                $deleted_l2 = $dbo->table('co_pianodeiconti2')
                    ->where('id', $idconto)
                    ->delete();

                if ($deleted_l2) {
                    flash()->info(tr('Conto e tutti i suoi sottoconti eliminati!'));
                } else {
                    flash()->error(tr('Errore durante l\'eliminazione del conto!'));
                }
            } else {
                flash()->error(tr('Impossibile eliminare il conto: esistono movimenti collegati ai suoi sottoconti!'));
            }
        } else {
            // Eliminazione conto di livello 3 (co_pianodeiconti3) - logica esistente
            // Controllo che non esistano movimenti associati al conto
            $movimenti = $dbo->table('co_movimenti')
                ->where('idconto', $idconto)
                ->count();

            if ($idconto != '' and empty($movimenti)) {
                // Se elimino il conto lo scollego anche da eventuali anagrafiche (cliente e fornitore)
                $dbo->table('an_anagrafiche')
                    ->where('idconto_cliente', $idconto)
                    ->update(['idconto_cliente' => null]);
                $dbo->table('an_anagrafiche')
                    ->where('idconto_fornitore', $idconto)
                    ->update(['idconto_fornitore' => null]);

                $deleted = $dbo->table('co_pianodeiconti3')
                    ->where('id', $idconto)
                    ->delete();

                if ($deleted) {
                    flash()->info(tr('Conto eliminato!'));
                } else {
                    flash()->error(tr('Errore durante l\'eliminazione del conto!'));
                }
            } else {
                flash()->error(tr('Impossibile eliminare il conto: esistono movimenti collegati!'));
            }
        }
        break;

        // Apertura bilancio
    case 'apri-bilancio':
        // Eliminazione eventuali movimenti di apertura fatti finora
        $dbo->table('co_movimenti')
            ->where('is_apertura', 1)
            ->where('data', $_SESSION['period_start'])
            ->delete();

        $idconto_apertura = setting('Conto per Apertura conti patrimoniali');
        $idconto_chiusura = setting('Conto per Chiusura conti patrimoniali');
        $data_inizio = date('Ymd', strtotime($_SESSION['period_start'].' -1 year'));
        $data_fine = $_SESSION['period_start'];

        // Lettura di tutti i conti dello stato patrimoniale con saldo != 0
        $conti = $dbo->fetchArray('SELECT co_pianodeiconti3.id, SUM(co_movimenti.totale) AS totale FROM ((co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id) INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) INNER JOIN co_movimenti ON co_pianodeiconti3.id=co_movimenti.idconto WHERE co_pianodeiconti1.descrizione="Patrimoniale" AND data >= '.prepare($data_inizio).' AND data < '.prepare($data_fine).' AND co_pianodeiconti3.id!='.prepare($idconto_chiusura).' AND is_chiusura=0 GROUP BY co_pianodeiconti3.id HAVING totale != 0');

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
        $dbo->table('co_movimenti')
            ->where('is_chiusura', 1)
            ->where('data', $_SESSION['period_end'])
            ->delete();

        $idconto_apertura = setting('Conto per Apertura conti patrimoniali');
        $idconto_chiusura = setting('Conto per Chiusura conti patrimoniali');

        $data_inizio = $_SESSION['period_start'];
        $data_fine = $_SESSION['period_end'];

        // Lettura di tutti i conti dello stato patrimoniale con saldo != 0
        $conti = $dbo->fetchArray('SELECT co_pianodeiconti3.id, SUM(co_movimenti.totale) AS totale FROM ((co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id) INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) INNER JOIN co_movimenti ON co_pianodeiconti3.id=co_movimenti.idconto WHERE co_pianodeiconti1.descrizione="Patrimoniale" AND data >= '.prepare($data_inizio).' AND data <= '.prepare($data_fine).' AND co_pianodeiconti3.id!='.prepare($idconto_chiusura).' AND is_chiusura=0 GROUP BY co_pianodeiconti3.id HAVING totale != 0');

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
        // $totale = -$totale;

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

        $dbo->table('co_movimenti')
            ->join('co_pianodeiconti3', 'co_pianodeiconti3.id', '=', 'co_movimenti.idconto')
            ->where('co_pianodeiconti3.id', $id_conto)
            ->whereBetween('co_movimenti.data', [$start, $end])
            ->update([
                'co_movimenti.totale_reddito' => $dbo->raw('(co_movimenti.totale * co_pianodeiconti3.percentuale_deducibile / 100)'),
            ]);

        break;

    case 'search':
        $text = post('text');
        $id_conti2 = 0;
        $id_conti3 = 0;

        if (!empty($text)) {
            $id_conti = $dbo->table('co_pianodeiconti2')
                ->select('id AS idpianodeiconti2')
                ->where('descrizione', 'like', '%'.$text.'%')
                ->orWhere('numero', 'like', '%'.$text.'%')
                ->get()
                ->toArray();
            $id_conti2 = array_column($id_conti, 'idpianodeiconti2');

            $id_conti = $dbo->table('co_pianodeiconti3')
                ->select('id AS idpianodeiconti3', 'idpianodeiconti2')
                ->where('descrizione', 'like', '%'.$text.'%')
                ->orWhere('numero', 'like', '%'.$text.'%')
                ->get()
                ->toArray();

            $id_conti3 = array_column($id_conti, 'idpianodeiconti3');
            $id_conti2_3 = array_column($id_conti, 'idpianodeiconti2');
        }

        echo json_encode(['conti2' => $id_conti2, 'conti3' => $id_conti3, 'conti2_3' => $id_conti2_3]);

        break;

    case 'manage_verifica':
        $id_movimento = post('id_movimento');
        $is_verificato = post('is_verificato');
        $response = null;

        try {
            $movimento = Movimento::find($id_movimento);

            if ($is_verificato) {
                $movimento->verified_at = date('Y-m-d H:i:s');
                $movimento->verified_by = $user->id;
            } else {
                $movimento->verified_at = null;
                $movimento->verified_by = 0;
            }
            $movimento->save();

            $response = [
                'result' => true,
            ];
        } catch (Error $e) {
            $response = [
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }

        echo json_encode($response);

        break;
}
