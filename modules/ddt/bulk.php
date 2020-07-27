<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
    $module_fatture = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_fatture = 'Fatture di acquisto';
}

// Segmenti
$id_fatture = Modules::get($module_fatture)['id'];
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $numero_totale = 0;

        // Informazioni della fattura
        if ($dir == 'entrata') {
            $descrizione_tipo = 'Fattura immediata di vendita';
        } else {
            $descrizione_tipo = 'Fattura immediata di acquisto';
        }

        $tipo_documento = Tipo::where('descrizione', $descrizione_tipo)->first();

        $stato_documenti_accodabili = Stato::where('descrizione', 'Bozza')->first();
        $accodare = post('accodare');

        $data = date('Y-m-d');
        $id_segment = post('id_segment');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = DDT::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            // Proseguo solo se i documenti scelti sono fatturabili
            if ($documento_import->isImportabile()) {
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
            flash()->info(tr('_NUM_ ddt fatturati!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun ddt fatturato!'));
        }
    break;

    case 'delete-bulk':
        foreach ($id_records as $id) {
            $documento = DDT::find($id);
            try {
                $documento->delete();
            } catch (InvalidArgumentException $e) {
            }
        }

        flash()->info(tr('Ddt eliminati!'));
    break;
}

if (App::debug()) {
    $operations = [
        'delete-bulk' => tr('Elimina selezionati'),
    ];
}

$operations['crea_fattura'] = [
        'text' => tr('Fattura documenti'),
        'data' => [
            'title' => tr('Vuoi davvero fatturare questi documenti?'),
            'msg' => '<br>{[ "type": "checkbox", "placeholder": "'.tr('Aggiungere alle fatture esistenti non ancora emesse?').'", "name": "accodare" ]}
            <br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_fatture.'\' AND is_fiscale = 1 ORDER BY name", "value": "'.$id_segment.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];

return $operations;
