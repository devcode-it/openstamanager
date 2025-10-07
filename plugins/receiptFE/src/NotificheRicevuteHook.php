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

namespace Plugins\ReceiptFE;

use Carbon\Carbon;
use Hooks\Manager;
use Models\Module;
use Models\Plugin;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Util\XML;

/**
 * Hook specializzato per il conteggio e la segnalazione di Fatture senza ricevute oppure con ricevuta in stato di errore.
 *
 * @see MissingReceiptTask,ReceiptTask,ReceiptHook Per procedura automatica di importazione
 */
class NotificheRicevuteHook extends Manager
{
    public function response()
    {
        // Messaggio informativo su fatture con stato di errore
        // Esclusione delle fatture duplicate (codice 00404) dal conteggio degli errori
        // Logica coerente con modules/fatture/controller_before.php
        $fatture_errore = Fattura::vendita()
            ->whereIn('codice_stato_fe', ['NS', 'ERR', 'EC02', 'ERVAL'])
            ->where('data_stato_fe', '>=', $_SESSION['period_start'])
            ->orderBy('data_stato_fe')
            ->get();

        $con_errore = 0;
        foreach ($fatture_errore as $fattura) {
            // Conteggio diretto per ERR, EC02, ERVAL
            if (in_array($fattura->codice_stato_fe, ['ERR', 'EC02', 'ERVAL'])) {
                ++$con_errore;
                continue;
            }

            // In caso di NS verifico che non sia semplicemente un codice 00404 (Fattura duplicata)
            if ($fattura->codice_stato_fe == 'NS' && ($fattura->stato != Stato::where('name', 'Bozza')->first()->id) && ($fattura->stato != Stato::where('name', 'Non valida')->first()->id)) {
                $ricevuta_principale = $fattura->getRicevutaPrincipale();

                // Se non esiste alcuna ricevuta, non conteggio come errore
                if (!empty($ricevuta_principale)) {
                    $contenuto_ricevuta = XML::readFile(base_dir().'/files/fatture/vendite/'.$ricevuta_principale->filename);
                    $lista_errori = $contenuto_ricevuta['ListaErrori'];
                    if ($lista_errori) {
                        $lista_errori = $lista_errori[0] ? $lista_errori : [$lista_errori];
                        $errore = $lista_errori[0]['Errore'];
                        // Escludo le fatture duplicate (codice 00404) dal conteggio
                        if ($errore['Codice'] != '00404') {
                            ++$con_errore;
                        }
                    }
                }
            }
        }

        // Controllo se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora una ricevuta
        $data_limite = (new Carbon())->subDays(7);
        $in_attesa = Fattura::vendita()
            ->where('codice_stato_fe', 'WAIT')
            ->where('data_stato_fe', '>=', $_SESSION['period_start'])
            ->where('data_stato_fe', '<', $data_limite)
            ->orderBy('data_stato_fe')
            ->count();

        // Messaggio di importazione
        if ($in_attesa>0 && $con_errore>0) {
            $message = tr('_ERR_ fattur_B_ elettronic_A_ con ricevut_B_ di scarto o errori di trasmissione, _WAIT_ fattur_D_ elettronic_C_ in attesa di ricevut_D_ da più di 7 giorni', [
                '_ERR_' => (($con_errore > 1) ? tr('Sono presenti') : tr('C\'è')).' '.$con_errore,
                '_A_' => (($con_errore > 1) ? 'he' : 'a'),
                '_B_' => (($con_errore > 1) ? 'e' : 'a'),
                '_WAIT_' => (($in_attesa > 1) ? tr('Sono presenti') : tr('C\'è')).' '.$in_attesa,
                '_C_' => (($in_attesa > 1) ? 'he' : 'a'),
                '_D_' => (($in_attesa > 1) ? 'e' : 'a'),
            ]);
        } elseif ($in_attesa==0 && $con_errore>0) {
            $message = tr('_ERR_ fattur_B_ elettronic_A_ con ricevut_B_ di scarto o errori di trasmissione', [
                '_ERR_' => (($con_errore > 1) ? tr('Sono presenti') : tr('C\'è')).' '.$con_errore,
                '_A_' => (($con_errore > 1) ? 'he' : 'a'),
                '_B_' => (($con_errore > 1) ? 'e' : 'a'),
            ]);
        }
        if ($in_attesa>0 && $con_errore==0) {
            $message = tr('_WAIT_ fattur_B_ elettronic_A_ in attesa di ricevut_B_ da più di 7 giorni', [
                '_WAIT_' => (($in_attesa > 1) ? tr('Sono presenti') : tr('C\'è')).' '.$in_attesa,
                '_A_' => (($in_attesa > 1) ? 'he' : 'a'),
                '_B_' => (($in_attesa > 1) ? 'e' : 'a'),
            ]);
        }

        $id_module = Module::where('name', 'Fatture di vendita')->first()->id;
        $id_plugin = Plugin::where('name', 'Ricevute FE')->first()->id;

        return [
            'icon' => 'fa fa-ticket text-yellow',
            'message' => $message,
            'show' => $con_errore != 0 || $in_attesa != 0,
            'link' => base_path().'/controller.php?id_module='.$id_module.'#tab_'.$id_plugin,
        ];
    }

    public function execute()
    {
    }

    public function needsExecution()
    {
        return false;
    }
}
