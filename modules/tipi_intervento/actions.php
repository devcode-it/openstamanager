<?php

use Modules\TipiIntervento\Tipo;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $tipo->descrizione = post('descrizione');
        $tipo->costo_orario = post('costo_orario');
        $tipo->costo_km = post('costo_km');
        $tipo->costo_diritto_chiamata = post('costo_diritto_chiamata');
        $tipo->costo_orario_tecnico = post('costo_orario_tecnico');
        $tipo->costo_km_tecnico = post('costo_km_tecnico');
        $tipo->costo_diritto_chiamata_tecnico = post('costo_diritto_chiamata_tecnico');
        $tipo->tempo_standard = post('tempo_standard');

        $tipo->save();

        flash()->info(tr('Informazioni tipo intervento salvate correttamente!'));

        break;

    case 'add':
        $idtipointervento = post('idtipointervento');
        $descrizione = post('descrizione');
        $tempo_standard = post('tempo_standard');

        $tipo = Tipo::build($idtipointervento, $descrizione, $tempo_standard);

        $id_record = $tipo->id;

        flash()->info(tr('Nuovo tipo di intervento aggiunto!'));

        break;

    case 'delete':
        $query = 'DELETE FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record);
        $dbo->query($query);

        // Elimino anche le tariffe collegate ai vari tecnici
        $query = 'DELETE FROM in_tariffe WHERE idtipointervento='.prepare($id_record);
        $dbo->query($query);

        flash()->info(tr('Tipo di intervento eliminato!'));
        break;

    case 'import':
        $values = [
            'costo_ore' => $record['costo_orario'],
            'costo_km' => $record['costo_km'],
            'costo_dirittochiamata' => $record['costo_diritto_chiamata'],
            'costo_ore_tecnico' => $record['costo_orario_tecnico'],
            'costo_km_tecnico' => $record['costo_km_tecnico'],
            'costo_dirittochiamata_tecnico' => $record['costo_diritto_chiamata_tecnico'],
        ];

        $dbo->update('in_tariffe', $values, [
            'idtipointervento' => $id_record,
        ]);

        break;
}
