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
use Plugins\ExportFE\Interaction;
use Util\XML;

$services_enable = Interaction::isEnabled();

if ($module->name == 'Fatture di vendita' && $services_enable) {
    $documenti_scarto = [];
    $documenti_invio = [];
    $codici_scarto = ['EC02','ERR','ERVAL','NS'];
    $codici_invio = ['GEN','QUEUE'];
    $data_limite = (new Carbon())->subMonths(6);
    $data_limite_invio = (new Carbon())->subDays(10);
    $data_setting = Carbon::createFromFormat('d/m/Y', setting('Data inizio controlli su stati FE'))->format('Y-m-d');


    $documenti = Fattura::where('data', '>', $data_limite)->where('data', '>', $data_setting)->whereIn('codice_stato_fe', ['EC02','ERR','ERVAL','NS','GEN','QUEUE'])->get();

    foreach ($documenti as $documento) {
        
        $stato_fe = $database->fetchOne('SELECT descrizione, icon FROM fe_stati_documento WHERE codice = '.prepare($documento->codice_stato_fe));
        
        if (in_array($documento->codice_stato_fe, $codici_scarto)) {

            // In caso di NS verifico che non sia semplicemente un codice 00404 (Fattura duplicata)
            if ($documento->codice_stato_fe == 'NS'){

                $ricevuta_principale = $documento->getRicevutaPrincipale();
               
                if (!empty($ricevuta_principale)) {
                    $contenuto_ricevuta = XML::readFile($ricevuta_principale->filepath);
                    $lista_errori = $contenuto_ricevuta['ListaErrori'];
                    if ($lista_errori) {
                        $lista_errori = $lista_errori[0] ? $lista_errori : [$lista_errori];
                        $errore = $lista_errori[0]['Errore'];
                        if ($errore['Codice'] == '00404'){
                            return;
                        }
                    }
                }
            }
            $documenti_scarto[] = Modules::link('Fatture di vendita', $documento->id, tr('_ICON_ Fattura numero _NUM_ del _DATE_ : <b>_STATO_</b>', [
                '_ICON_' => '<i class="'.$stato_fe['icon'].'"></i>',
                '_NUM_' => $documento->numero_esterno,
                '_DATE_' => dateFormat($documento->data),
                '_STATO_' => $stato_fe['descrizione'],
            ]));
            

        } elseif (in_array($documento->codice_stato_fe, $codici_invio)) {
            if ($documento->data <= $data_limite_invio) {
                $documenti_invio[] = Modules::link('Fatture di vendita', $documento->id, tr('_ICON_ Fattura numero _NUM_ del _DATE_ : <b>_STATO_</b>', [
                    '_ICON_' => '<i class="'.$stato_fe['icon'].'"></i>',
                    '_NUM_' => $documento->numero_esterno,
                    '_DATE_' => dateFormat($documento->data),
                    '_STATO_' => $stato_fe['descrizione'],
                ]));
            }
        }
    }
    //Controllo già presente sul plugin Ricevute FE
    if (sizeof($documenti_scarto) > 0) {
        echo '
        <div class="alert alert-danger">
            <i class="fa fa-warning"></i> '.tr("<b>ATTENZIONE:</b> le seguenti fatture riscontrano problemi").':<ul>';
            foreach ($documenti_scarto as $documento) {
                echo '
                <li><b>'.$documento.'</b></li>';
            }
        echo '
            </ul>
        </div>';
    }

    if (sizeof($documenti_invio) > 0) {
        echo '
        <div class="alert alert-warning">
            <i class="fa fa-clock-o"></i> '.tr("Le seguenti fatture sono in attesa di essere inviate").':<ul>';
            foreach ($documenti_invio as $documento) {
                echo '
                <li><b>'.$documento.'</b></li>';
            }
        echo '
            </ul>
        </div>';
    }
}
