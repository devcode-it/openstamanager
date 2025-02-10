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

use Carbon\Carbon;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\StatoFE;
use Plugins\ExportFE\Interaction;
use Util\XML;

$services_enable = Interaction::isEnabled();

if ($module->name == 'Fatture di vendita' && $services_enable) {
    $documenti_scarto = [];
    $documenti_invio = [];
    $codici_scarto = ['EC02', 'ERR', 'ERVAL', 'NS'];
    $codici_invio = ['GEN', 'QUEUE'];
    $data_limite = (new Carbon())->subMonths(6);
    $data_limite_invio = (new Carbon())->subDays(10);

    // Verifica se la data cade di sabato o domenica
    $giorno_settimana = $data_limite_invio->dayOfWeek;
    if ($giorno_settimana == Carbon::WEDNESDAY) {
        $data_limite_invio->addDays(); // Anticipa la data di 1 giorno se la data limite cade di sabato (con data fattura mercoledì)
    } elseif ($giorno_settimana == Carbon::THURSDAY) {
        $data_limite_invio->addDays(2); // Anticipa la data di 2 giorni se la data limite cade di domenica (con data fattura giovedì)
    }
    $data_setting = Carbon::createFromFormat('d/m/Y', setting('Data inizio controlli su stati FE'))->format('Y-m-d');

    $documenti = Fattura::where('data', '>', $data_limite)->where('data', '>', $data_setting)->whereIn('codice_stato_fe', ['EC02', 'ERR', 'ERVAL', 'NS', 'GEN', 'QUEUE'])->get();

    foreach ($documenti as $documento) {
        $stato_fe = StatoFE::find($documento->codice_stato_fe);
        if (in_array($documento->codice_stato_fe, $codici_scarto)) {
            // In caso di NS verifico che non sia semplicemente un codice 00404 (Fattura duplicata)
            if ($documento->codice_stato_fe == 'NS' && ($documento->stato != Stato::where('name', 'Bozza')->first()->id) && ($documento->stato != Stato::where('name', 'Non valida')->first()->id)) {
                $ricevuta_principale = $documento->getRicevutaPrincipale();

                if (!empty($ricevuta_principale)) {
                    $contenuto_ricevuta = XML::readFile(base_dir().'/files/fatture/'.$ricevuta_principale->filename);
                    $lista_errori = $contenuto_ricevuta['ListaErrori'];
                    if ($lista_errori) {
                        $lista_errori = $lista_errori[0] ? $lista_errori : [$lista_errori];
                        $errore = $lista_errori[0]['Errore'];
                        if ($errore['Codice'] == '00404') {
                            return;
                        }
                    }
                }
            }
            $documenti_scarto[] = Modules::link('Fatture di vendita', $documento->id, tr('_ICON_ Fattura numero _NUM_ del _DATE_ : <b>_STATO_</b>', [
                '_ICON_' => '<i class="'.$stato_fe->icon.'"></i>',
                '_NUM_' => $documento->numero_esterno,
                '_DATE_' => dateFormat($documento->data),
                '_STATO_' => $stato_fe->name,
            ]));

            $show_avviso = $show_avviso ?: ($documento->data_stato_fe < (new Carbon())->subDays(4) ? 1 : 0);
        } elseif (in_array($documento->codice_stato_fe, $codici_invio)) {
            $is_estera = false;

            if (setting('Rimuovi avviso fatture estere')) {
                $is_estera = $database->fetchOne('SELECT `idanagrafica` FROM `an_anagrafiche` INNER JOIN `an_nazioni` ON `an_anagrafiche`.`id_nazione` = `an_nazioni`.`id` LEFT JOIN `an_nazioni_lang` ON (`an_nazioni`.`id` = `an_nazioni_lang`.`id_record` AND `an_nazioni_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `an_nazioni_lang`.`title` != "Italia" AND `an_anagrafiche`.`idanagrafica` = '.prepare($documento->idanagrafica));
            }

            if ($documento->data <= $data_limite_invio && !$is_estera) {
                $documenti_invio[] = Modules::link('Fatture di vendita', $documento->id, tr('_ICON_ Fattura numero _NUM_ del _DATE_ : <b>_STATO_</b> _ANTICIPATA_', [
                    '_ICON_' => '<i class="'.$stato_fe->icon.'"></i>',
                    '_NUM_' => $documento->numero_esterno,
                    '_DATE_' => dateFormat($documento->data),
                    '_STATO_' => $stato_fe->name,
                    '_ANTICIPATA_' => (($documento->data->diffInDays($data_limite_invio) < 10) ? '(Anticipata)' : ''),
                ]));
            }
        }
    }
    // Controllo già presente sul plugin Ricevute FE
    if (sizeof($documenti_scarto) > 0) {
        echo '
        <div class="alert alert-danger">
            <i class="fa fa-warning"></i> '.tr('<b>ATTENZIONE:</b> le seguenti fatture riscontrano problemi').':<ul>';
        foreach ($documenti_scarto as $documento) {
            echo '
                <li><b>'.$documento.'</b></li>';
        }
        echo '
            </ul>';
        if ($show_avviso) {
            echo tr('Cosa fare in caso di fattura elettronica scartata? Dovrai correggere la fattura e inviarla di nuovo al SdI <b>entro 5 giorni dalla data di notifica dello scarto</b>, mantenendo lo stesso numero e data del documento.');
        }
        echo '
        </div>';
    }

    if (sizeof($documenti_invio) > 0) {
        echo '
        <div class="alert push alert-warning">
        <h4><i class="fa fa-clock-o"></i>'.tr('Attenzione').'</h4>'.tr('Le seguenti fatture sono in attesa di essere inviate').':<ul>';
        foreach ($documenti_invio as $documento) {
            echo '
                <li><b>'.$documento.'</b></li>';
        }
        echo '
            </ul>
        </div>';
    }
}
