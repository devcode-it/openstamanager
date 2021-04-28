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

use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;
use Modules\Ordini\Ordine;
use Modules\Articoli\Articolo as ArticoloOriginale;

$module_fatture = 'Fatture di vendita';

// Segmenti
$id_fatture = Modules::get($module_fatture)['id'];
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];
$idconto = setting('Conto predefinito fatture di vendita');

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $numero_totale = 0;
        $descrizione_tipo = 'Fattura immediata di vendita';

        $tipo_documento = Tipo::where('descrizione', $descrizione_tipo)->first();

        $stato_documenti_accodabili = Stato::where('descrizione', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = Ordine::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            // Proseguo solo se i documenti scelti sono fatturabili
            $ordine = $dbo->fetchOne('SELECT or_statiordine.descrizione AS stato FROM or_ordini LEFT JOIN or_statiordine ON or_ordini.idstatoordine=or_statiordine.id WHERE or_ordini.id='.prepare($id))['stato'];
            if (!in_array($ordine, ['Fatturato', 'Evaso', 'Bozza', 'In attesa di conferma', 'Annullato'])) {
                $righe = $documento_import->getRighe();
                if (!empty($righe)) {
                    ++$numero_totale;

                    // Ricerca fattura per anagrafica tra le registrate
                    $fattura = $documenti->first(function ($item, $key) use ($id_anagrafica) {
                        return $item->anagrafica->id == $id_anagrafica;
                    });

                    // Ricerca fattura per anagrafica se l'impostazione di accodamento è selezionata
                    if (!empty($accodare) && empty($fattura)) {
                        $fattura = Fattura::where('idanagrafica', $id_anagrafica)
                            ->where('idstatodocumento', $stato_documenti_accodabili->id)
                            ->where('idtipodocumento', $tipo_documento->id)
                            ->first();

                        if (!empty($fattura)) {
                            $documenti->push($fattura);
                        }
                    }

                    // Creazione fattura per anagrafica
                    if (empty($fattura)) {
                        $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                        $documenti->push($fattura);
                    }

                    // Inserimento righe
                    foreach ($righe as $riga) {
                        $qta = $riga->qta_rimanente;

                        if ($qta > 0) {
                            $copia = $riga->copiaIn($fattura, $qta);

                            //Fix per idconto righe fattura
                            $articolo = ArticoloOriginale::find($copia->idarticolo);
                            $copia->id_conto = ($articolo->idconto_vendita ? $articolo->idconto_vendita : $idconto);

                            // Aggiornamento seriali dalla riga dell'ordine
                            if ($copia->isArticolo()) {
                                $copia->serials = $riga->serials;
                            }
                        }
                    }
                }
            }
        }

        if ($numero_totale > 0) {
            flash()->info(tr('_NUM_ ordini fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun ordine fatturato!'));
        }
    break;

    case 'cambia_stato':
        $id_stato = post('id_stato');

        $n_ordini = 0;

        foreach ($id_records as $id) {
            $ordine = Ordine::find($id);
            $ordine->idstatoordine = $id_stato;
            $ordine->save();

            ++$n_ordini;
        }

        if ($n_ordini > 0) {
            flash()->info(tr('Stato cambiato a _NUM_ ordini!', [
                '_NUM_' => $n_ordini,
            ]));
        } else {
            flash()->warning(tr('Nessun ordine modificato!'));
        }

    break;
}
if ($module['name'] == 'Ordini cliente') {
    $operations['crea_fattura'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Fattura _TYPE_', ['_TYPE_' => strtolower($module['name'])]),
        'data' => [
            'title' => tr('Fatturare i _TYPE_ selezionati?', ['_TYPE_' => strtolower($module['name'])]),
            'msg' => '{[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle _TYPE_ non ancora emesse?', ['_TYPE_' => strtolower($module_fatture)]).'", "placeholder": "'.tr('Aggiungere alle _TYPE_ nello stato bozza?', ['_TYPE_' => strtolower($module_fatture)]).'</small>", "name": "accodare" ]}
            <br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_fatture.'\' AND is_fiscale = 1 ORDER BY name", "value": "'.$id_segment.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];
}

$operations['cambia_stato'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Cambia stato'),
    'data' => [
        'title' => tr('Vuoi davvero cambiare lo stato per questi ordini?'),
        'msg' => tr('Seleziona lo stato in cui spostare tutti gli ordini').'.<br>
        <br>{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT id, descrizione FROM or_statiordine" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

    return $operations;
