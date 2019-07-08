<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        foreach ($tipi_interventi as $tipo_intervento) {
            $id_tipo_intervento = $tipo_intervento['id'];

            $values = [
                'costo_ore' => post('costo_ore')[$id_tipo_intervento],
                'costo_km' =>  post('costo_km')[$id_tipo_intervento],
                'costo_dirittochiamata' => post('costo_dirittochiamata')[$id_tipo_intervento],
                'costo_ore_tecnico' => post('costo_ore_tecnico')[$id_tipo_intervento],
                'costo_km_tecnico' => post('costo_km_tecnico')[$id_tipo_intervento],
                'costo_dirittochiamata_tecnico' => post('costo_dirittochiamata_tecnico')[$id_tipo_intervento],
            ];

            // Aggiorno il record
            $dbo->update('in_tariffe', $values, [
                'idtipointervento' => $id_tipo_intervento,
                'idtecnico' => $id_record,
            ]);
        }

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'import':
        $id_tipo_intervento = post('idtipointervento');

        $importi = $dbo->fetchOne('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($id_tipo_intervento));
        
        $values = [
            'costo_ore' => $importi['costo_orario'],
            'costo_km' =>  $importi['costo_km'],
            'costo_dirittochiamata' => $importi['costo_diritto_chiamata'],
            'costo_ore_tecnico' => $importi['costo_orario_tecnico'],
            'costo_km_tecnico' => $importi['costo_km_tecnico'],
            'costo_dirittochiamata_tecnico' => $importi['costo_diritto_chiamata_tecnico'],
        ];

        foreach ($tipi_interventi as $tipo_intervento) {
            if($tipo_intervento['id'] == $id_tipo_intervento) break;
        }

        // Aggiorno il record
        $dbo->update('in_tariffe', $values, [
            'idtipointervento' => $id_tipo_intervento,
            'idtecnico' => $id_record,
        ]);

        break;
}
