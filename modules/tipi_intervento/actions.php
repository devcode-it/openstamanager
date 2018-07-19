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

        $tempo_standard = (empty(post('tempo_standard'))) ? 'NULL' : prepare(round((force_decimal($_POST['tempo_standard']) / 2.5), 1) * 2.5);

        $query = 'UPDATE in_tipiintervento SET'.
            ' descrizione='.prepare($descrizione).','.
            ' costo_orario='.prepare($costo_orario).','.
            ' costo_km='.prepare($costo_km).','.
            ' costo_diritto_chiamata='.prepare($costo_diritto_chiamata).','.
            ' costo_orario_tecnico='.prepare($costo_orario_tecnico).','.
            ' costo_km_tecnico='.prepare($costo_km_tecnico).','.
            ' costo_diritto_chiamata_tecnico='.prepare($costo_diritto_chiamata_tecnico).','.
            ' tempo_standard='.$tempo_standard.
            ' WHERE idtipointervento='.prepare($id_record);

        $dbo->query($query);
        flash()->info(tr('Informazioni tipo intervento salvate correttamente!'));

        break;

    case 'add':
        $idtipointervento = post('idtipointervento');
        $descrizione = post('descrizione');

        $tempo_standard = (empty(post('tempo_standard'))) ? 'NULL' : prepare(round((force_decimal($_POST['tempo_standard']) / 2.5), 1) * 2.5);

        $query = 'INSERT INTO in_tipiintervento(idtipointervento, descrizione, costo_orario, costo_km, tempo_standard) VALUES ('.prepare($idtipointervento).', '.prepare($descrizione).', 0.00, 0.00, '.$tempo_standard.')';
        $dbo->query($query);

        $id_record = $idtipointervento;

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
}
