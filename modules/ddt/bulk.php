<?php

include_once __DIR__.'/../../core.php';

use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
    $module_name = 'Fatture di vendita';
} else {
    $dir = 'uscita';
    $module_name = 'Fatture di acquisto';
}

// Segmenti
$id_fatture = Modules::get($module_name)['id'];
if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
    $segments = Modules::getSegments($id_fatture);
    $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
}
$id_segment = $_SESSION['module_'.$id_fatture]['id_segment'];

switch (post('op')) {
    case 'crea_fattura':
        $documenti = collect();
        $id_documento_cliente = [];
        $numero_totale = 0;

        // Informazioni della fattura
        if ($dir == 'entrata') {
            $tipo_documento = 'Fattura immediata di vendita';
        } else {
            $tipo_documento = 'Fattura immediata di acquisto';
        }

        $tipo_documento = Tipo::where('descrizione', $tipo_documento)->first();

        $data = date('Y-m-d');
        $id_segment = post('id_segment');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $documento_import = DDT::find($id);
            $anagrafica = $documento_import->anagrafica;
            $id_anagrafica = $anagrafica->id;

            // Proseguo solo se i documenti scelti sono fatturabili
            $righe = $documento_import->getRighe();
            if (!empty($righe)) {
                ++$numero_totale;

                // Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
                $fattura = $documenti->first(function ($item, $key) use ($id_anagrafica) {
                    return $item->anagrafica->id == $id_anagrafica;
                });
                if (empty($fattura)) {
                    $fattura = Fattura::build($anagrafica, $tipo_documento, $data, $id_segment);
                    $documenti->push($fattura);
                }

                // Inserimento righe
                foreach ($righe as $riga) {
                    $qta = $riga['qta'] - $riga['qta_evasa'];

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
            $dbo->query('DELETE  FROM dt_ddt  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM mg_movimenti WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
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
        'text' => tr('Crea fattura'),
        'data' => [
            'title' => tr('Vuoi davvero creare una fattura per questi interventi?'),
            'msg' => '<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_fatture.'\' AND is_fiscale = 1 ORDER BY name", "value": "'.$id_segment.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];

return $operations;
