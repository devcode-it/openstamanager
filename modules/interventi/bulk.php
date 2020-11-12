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

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Util\Zip;

// Segmenti
$id_fatture = Modules::get('Fatture di vendita')['id'];
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];

switch (post('op')) {
    case 'export-bulk':
        $dir = base_dir().'/files/export_interventi/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione degli interventi da stampare
        $interventi = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, data_richiesta, ragione_sociale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id IN('.implode(',', $id_records).')');

        if (!empty($interventi)) {
            foreach ($interventi as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $dir.'tmp/');
            }

            $dir = slashes($dir);
            $file = slashes($dir.'interventi_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

    break;

    case 'crea_fattura':
        $id_documento_cliente = [];
        $n_interventi = 0;

        $data = date('Y-m-d');
        $dir = 'entrata';
        $tipo_documento = Tipo::where('descrizione', 'Fattura immediata di vendita')->first();

        $id_iva = setting('Iva predefinita');
        $id_conto = setting('Conto predefinito fatture di vendita');

        $accodare = post('accodare');
        $id_segment = post('id_segment');

        $interventi = $dbo->fetchArray('SELECT *, IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id), in_interventi.data_richiesta) AS data, in_statiintervento.descrizione AS stato, in_interventi.codice AS codice_intervento FROM in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento WHERE in_statiintervento.is_completato=1 AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND in_interventi.id_preventivo IS NULL AND in_interventi.id NOT IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL) AND in_interventi.id IN ('.implode(',', $id_records).')');

        // Lettura righe selezionate
        foreach ($interventi as $intervento) {
            $id_anagrafica = $intervento['idanagrafica'];

            $id_documento = $id_documento_cliente[$id_anagrafica];

            // Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
            if (empty($id_documento)) {
                if (!empty($accodare)) {
                    $documento = $dbo->fetchOne('SELECT co_documenti.id FROM co_documenti INNER JOIN co_statidocumento ON co_documenti.idstatodocumento = co_statidocumento.id WHERE co_statidocumento.descrizione = \'Bozza\' AND idanagrafica = '.prepare($id_anagrafica));

                    $id_documento = $documento['id'];
                    $id_documento_cliente[$id_anagrafica] = $id_documento;
                }

                if (empty($id_documento)) {
                    $anagrafica = Anagrafica::find($id_anagrafica);
                    $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);

                    $id_documento = $fattura->id;
                    $id_documento_cliente[$id_anagrafica] = $id_documento;
                }
            }

            $descrizione = tr('Intervento numero _NUM_ del _DATE_ [_STATE_]', [
                '_NUM_' => $intervento['codice_intervento'],
                '_DATE_' => Translator::dateToLocale($intervento['data']),
                '_STATE_' => $intervento['stato'],
            ]);

            aggiungi_intervento_in_fattura($intervento['id'], $id_documento, $descrizione, $id_iva, $id_conto);
            ++$n_interventi;
        }

        if ($n_interventi > 0) {
            flash()->info(tr('_NUM_ interventi fatturati.', [
                '_NUM_' => $n_interventi,
            ]));
        } else {
            flash()->warning(tr('Nessuna attività fatturata!'));
        }

    break;

    case 'cambia_stato':
        $id_stato = post('id_stato');

        $n_interventi = 0;
        $stato = Stato::find($id_stato);

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $intervento = Intervento::find($id);

            if (!$intervento->stato->is_completato) {
                $intervento->stato()->associate($stato);
                $intervento->save();

                ++$n_interventi;
            }
        }

        if ($n_interventi > 0) {
            flash()->info(tr('Stato cambiato a _NUM_ attività!', [
                '_NUM_' => $n_interventi,
            ]));
        } else {
            flash()->warning(tr('Nessuna attività modificata!'));
        }

        break;

    case 'copy-bulk':
        $id_stato = post('idstatointervento');
        $data_richiesta = post('data_richiesta');
        $copia_sessioni = post('sessioni');
        $copia_righe = post('righe');

        foreach ($id_records as $idintervento) {
            $intervento = Intervento::find($idintervento);

            $new = $intervento->replicate();
            $new->idstatointervento = $id_stato;

            // Calcolo del nuovo codice sulla base della data di richiesta
            $new->codice = Intervento::getNextCodice($data_richiesta);

            $new->save();

            $id_record = $new->id;

            // Copio le righe
            if (!empty($copia_righe)) {
                $righe = $intervento->getRighe();
                foreach ($righe as $riga) {
                    $new_riga = $riga->replicate();
                    $new_riga->setDocument($new);

                    $new_riga->qta_evasa = 0;
                    $new_riga->save();
                }
            }

            // Copia delle sessioni
            $numero_sessione = 0;
            if (!empty($copia_sessioni)) {
                $sessioni = $intervento->sessioni;
                foreach ($sessioni as $sessione) {
                    // Se è la prima sessione che copio importo la data con quella della richiesta
                    if ($numero_sessione == 0) {
                        $orario_inizio = date('Y-m-d', strtotime($data_richiesta)).' '.date('H:i:s', strtotime($sessione->orario_inizio));
                    } else {
                        $diff = strtotime($sessione->orario_inizio) - strtotime($inizio_old);
                        $orario_inizio = date('Y-m-d H:i:s', (strtotime($sessione->orario_inizio) + $diff));
                    }

                    $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                    $orario_fine = date('Y-m-d H:i:s', (strtotime($orario_inizio) + $diff_fine));

                    $new_sessione = $sessione->replicate();
                    $new_sessione->idintervento = $new->id;

                    $new_sessione->orario_inizio = $orario_inizio;
                    $new_sessione->orario_fine = $orario_fine;
                    $new_sessione->save();

                    ++$numero_sessione;
                    $inizio_old = $sessione->orario_inizio;
                }
            }
        }

        flash()->info(tr('Attività duplicate correttamente!'));

        break;
}

return [
    'export-bulk' => [
        'text' => '<span><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe'),
        'data' => [
            'title' => tr('Vuoi davvero esportare queste stampe in un archivio ZIP?'),
            'msg' => '',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],

    'crea_fattura' => [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower($module['name'])]),
        'data' => [
           'title' => tr('Fatturare gli _TYPE_ selezionati?', ['_TYPE_' => strtolower($module['name'])]).' <small><i class="fa fa-question-circle-o tip" title="'.tr('Verranno fatturati solo gli interventi completati non collegati a contratti o preventivi').'."></i></small>',
            'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture di vendita nello stato bozza?').'", "name": "accodare" ]}<br>
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_fatture.'\' AND is_fiscale = 1 ORDER BY name", "value": "'.$id_segment.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],

    'cambia_stato' => [
        'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
        'data' => [
            'title' => tr('Vuoi davvero cambinare le stato per questi interventi?'),
            'msg' => tr('Seleziona lo stato in cui spostare tutti gli interventi non completati').'.<br>
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],

    'copy-bulk' => [
        'text' => '<span><i class="fa fa-clone"></i> '.tr('Duplica attività'),
        'data' => [
            'title' => tr('Vuoi davvero fare una copia degli interventi selezionati?'),
            'msg' => '<br>{[ "type": "timestamp", "label": "'.tr('Data/ora richiesta').'", "name": "data_richiesta", "required": 0, "value": "-now-", "required":1 ]}
            <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatointervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL", "value": "" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica righe').'", "name":"righe", "value":"" ]}
            <br>{[ "type":"checkbox", "label":"'.tr('Duplica sessioni').'", "name":"sessioni", "value":"" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],
];
