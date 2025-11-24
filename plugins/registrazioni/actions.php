<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Fatture\Components\Articolo as RigaArticolo;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Movimenti as GestoreMovimenti;
use Util\Generator;

$operazione = filter('op');

switch ($operazione) {
    case 'change-conto':
        $conti = (array) post('idconto');
        $conti_cespiti = (array) post('idconto_cespiti');
        $cespiti = (array) post('is_cespite');

        $errori = [];
        $righe_aggiornate = 0;

        try {
            foreach ($conti as $id_riga => $conto) {
                $riga = RigaArticolo::find($id_riga) ?: Riga::find($id_riga);
                $riga = $riga ?: Sconto::find($id_riga);

                if (!$riga) {
                    $errori[] = tr('Riga _ID_ non trovata', ['_ID_' => $id_riga]);
                    continue;
                }

                $is_cespite = !empty($cespiti[$id_riga]);
                $conto_selezionato = $is_cespite ? ($conti_cespiti[$id_riga] ?? null) : $conto;

                // Generazione codice cespite
                if ($is_cespite) {
                    if (!$riga->codice_cespite) {
                        $maschera = setting('Formato codice cespite');
                        $ultimo = Generator::getPreviousFrom($maschera, 'co_righe_documenti', 'codice_cespite', [
                            'codice_cespite IS NOT NULL',
                        ]);
                        $codice = Generator::generate($maschera, $ultimo);
                        $riga->codice_cespite = $codice;
                    }
                } else {
                    $riga->codice_cespite = null;
                    $riga->is_smaltito = 0;
                }

                // Validazione lato server
                if (empty($conto_selezionato)) {
                    $tipo_conto = $is_cespite ? tr('conto cespite') : tr('conto');
                    $errori[] = tr('Riga _ID_: selezionare un _TIPO_', [
                        '_ID_' => $id_riga,
                        '_TIPO_' => $tipo_conto,
                    ]);
                    continue;
                }

                // Verifica che il conto esista nel database
                $conto_exists = $dbo->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE id = ?', [$conto_selezionato]);
                if (!$conto_exists) {
                    $errori[] = tr('Riga _ID_: il conto selezionato non è valido', ['_ID_' => $id_riga]);
                    continue;
                }

                // Aggiorna la riga
                $riga->id_conto = $conto_selezionato;
                $riga->is_cespite = $is_cespite ? 1 : 0;
                $riga->save();

                ++$righe_aggiornate;
            }

            if (!empty($errori)) {
                flash()->error(tr('Errori durante il salvataggio:').'<ul><li>'.implode('</li><li>', $errori).'</li></ul>');
            } else {
                $fattura = Fattura::find($id_record);

                if ($fattura->stato->getTranslation('title') != 'Bozza') {
                    try {
                        $fattura->gestoreMovimenti = new GestoreMovimenti($fattura);
                        $fattura->gestoreMovimenti->registra();
                    } catch (Exception $e) {
                        flash()->error(tr('Errore durante la registrazione dei movimenti contabili: _ERROR_', ['_ERROR_' => $e->getMessage()]));
                        break;
                    }
                }

                if ($righe_aggiornate > 0) {
                    flash()->info(tr('Salvataggio completato! _NUM_ righe aggiornate.', ['_NUM_' => $righe_aggiornate]));
                } else {
                    flash()->warning(tr('Nessuna riga è stata aggiornata.'));
                }
            }
        } catch (Exception $e) {
            flash()->error(tr('Errore durante il salvataggio: _ERROR_', ['_ERROR_' => $e->getMessage()]));
        }

        break;
}
