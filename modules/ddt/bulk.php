<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
use Modules\Anagrafiche\Anagrafica;

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
        $id_documento_cliente = [];
        $totale_n_ddt = 0;

        // Informazioni della fattura
        if ($dir == 'entrata') {
            $tipo_documento = 'Fattura immediata di vendita';
        } else {
            $tipo_documento = 'Fattura immediata di acquisto';
        }

        $tipo_documento = Tipo::where('descrizione', $tipo_documento)->first();

        $idiva = setting('Iva predefinita');
        $data = date('Y-m-d');
        $id_segment = post('id_segment');

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $id_anagrafica = $dbo->selectOne('dt_ddt', 'idanagrafica', ['id' => $id])['idanagrafica'];

            $righe = $dbo->fetchArray('SELECT * FROM dt_righe_ddt WHERE idddt='.prepare($id).' AND idddt NOT IN (SELECT idddt FROM co_righe_documenti WHERE idddt IS NOT NULL)');

            // Proseguo solo se i ddt scelti sono fatturabili
            if (!empty($righe)) {
                $id_documento = $id_documento_cliente[$id_anagrafica];
                ++$totale_n_ddt;

                // Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
                if (empty($id_documento)) {
                    $anagrafica = Anagrafica::find($id_anagrafica);
                    $fattura = Fattura::make($anagrafica, $tipo_documento, $data, $id_segment);

                    $id_documento = $fattura->id;
                    $id_documento_cliente[$id_anagrafica] = $id_documento;
                }

                // Inserimento righe
                foreach ($righe as $riga) {
                    $qta = $riga['qta'] - $riga['qta_evasa'];

                    if ($qta > 0) {
                        $dbo->insert('co_righe_documenti', [
                            'iddocumento' => $id_documento,
                            'idarticolo' => $riga['idarticolo'],
                            'idddt' => $id,
                            'idiva' => $riga['idiva'],
                            'desc_iva' => $riga['desc_iva'],
                            'iva' => $riga['iva'],
                            'iva_indetraibile' => $riga['iva_indetraibile'],
                            'descrizione' => $riga['descrizione'],
                            'is_descrizione' => $riga['is_descrizione'],
                            'subtotale' => $riga['subtotale'],
                            'sconto' => $riga['sconto'],
                            'sconto_unitario' => $riga['sconto_unitario'],
                            'tipo_sconto' => $riga['tipo_sconto'],
                            'um' => $riga['um'],
                            'qta' => $qta,
                            'abilita_serial' => $riga['abilita_serial'],
                            'order' => orderValue('co_righe_documenti', 'iddocumento', $id_documento),
                        ]);
                        $id_riga_documento = $dbo->lastInsertedID();

                        // Copia dei serial tra le righe
                        if (!empty($riga['idarticolo'])) {
                            $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($id_riga_documento).', '.prepare($riga['idarticolo']).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ddt='.prepare($riga['id']));
                        }

                        // Aggiorno la quantità evasa
                        $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta WHERE id='.prepare($riga['id']));

                        // Aggiorno lo stato ddt
                        $dbo->query('UPDATE dt_ddt SET idstatoddt = (SELECT id FROM dt_statiddt WHERE descrizione="Fatturato") WHERE id='.prepare($id));
                    }

                    // Ricalcolo inps, ritenuta e bollo
                    ricalcola_costiagg_fattura($id_documento);
                }
            }
        }

        if ($totale_n_ddt > 0) {
            flash()->info(tr('_NUM_ ddt fatturati!', [
                '_NUM_' => $totale_n_ddt,
            ]));
        } else {
            flash()->warning(tr('Nessun ddt fatturato!'));
        }

    break;

    case 'delete-bulk':

        if (App::debug()) {
            foreach ($id_records as $id) {
                $dbo->query('DELETE  FROM dt_ddt  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM mg_movimenti WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
            }

            flash()->info(tr('Ddt eliminati!'));
        } else {
            flash()->warning(tr('Procedura in fase di sviluppo. Nessuna modifica apportata.'));
        }

    break;
}

$operations = [
    'delete-bulk' => tr('Elimina selezionati'),
    'crea_fattura' => [
        'text' => tr('Crea fattura'),
        'data' => [
            'title' => tr('Vuoi davvero creare una fattura per questi interventi?'),
            'msg' => '<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_fatture.'\' AND is_fiscale = 1 ORDER BY name", "value": "'.$id_segment.'" ]}',
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],
];

return $operations;
