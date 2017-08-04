<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $costo_orario = post('costo_orario');
        $costo_km = post('costo_km');
        $costo_diritto_chiamata = post('costo_diritto_chiamata');

        $costo_orario_tecnico = post('costo_orario_tecnico');
        $costo_km_tecnico = post('costo_km_tecnico');
        $costo_diritto_chiamata_tecnico = post('costo_diritto_chiamata_tecnico');

        $query = 'UPDATE in_tipiintervento SET'.
            ' descrizione='.prepare($descrizione).','.
            ' costo_orario='.prepare($costo_orario).','.
            ' costo_km='.prepare($costo_km).','.
            ' costo_diritto_chiamata='.prepare($costo_diritto_chiamata).','.
            ' costo_orario_tecnico='.prepare($costo_orario_tecnico).','.
            ' costo_km_tecnico='.prepare($costo_km_tecnico).','.
            ' costo_diritto_chiamata_tecnico='.prepare($costo_diritto_chiamata_tecnico).
            ' WHERE idtipointervento='.prepare($id_record);

        $dbo->query($query);
        $_SESSION['infos'][] = _('Informazioni tipo intervento salvate correttamente!');

        break;

    case 'add':
        $idtipointervento = post('idtipointervento');
        $descrizione = post('descrizione');

        $query = 'INSERT INTO in_tipiintervento(idtipointervento, descrizione, costo_orario, costo_km) VALUES ('.prepare($idtipointervento).', '.prepare($descrizione).', 0.00, 0.00)';
        $dbo->query($query);

        $id_record = $idtipointervento;

        $_SESSION['infos'][] = _('Nuovo tipo di intervento aggiunto!');

        break;

    case 'delete':
        $query = 'DELETE FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record);
        $dbo->query($query);

        // Elimino anche le tariffe collegate ai vari tecnici
        $query = 'DELETE FROM in_tariffe WHERE idtipointervento='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = _('Tipo di intervento eliminato!');
        break;
}
