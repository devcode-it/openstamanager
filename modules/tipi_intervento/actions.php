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

use Modules\TipiIntervento\Tipo;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $tipo->descrizione = post('descrizione');
        $tipo->calcola_km = post('calcola_km');
        $tipo->tempo_standard = post('tempo_standard');
        $tipo->costo_orario = post('costo_orario');
        $tipo->costo_km = post('costo_km');
        $tipo->costo_diritto_chiamata = post('costo_diritto_chiamata');
        $tipo->costo_orario_tecnico = post('costo_orario_tecnico');
        $tipo->costo_km_tecnico = post('costo_km_tecnico');
        $tipo->costo_diritto_chiamata_tecnico = post('costo_diritto_chiamata_tecnico');
        $tipo->save();

        $fasce_ore = (array)post('fascia_ore');
        $fascia_km = (array)post('fascia_km');
        $fascia_diritto_chiamata = (array)post('fascia_diritto_chiamata');
        $fascia_orario_tecnico = (array)post('fascia_orario_tecnico');
        $fascia_km_tecnico = (array)post('fascia_km_tecnico');
        $fascia_diritto_chiamata_tecnico = (array)post('fascia_diritto_chiamata_tecnico');

        foreach ($fasce_ore as $key => $fascia_ore) {
            $dbo->update('in_fasceorarie_tipiintervento', [
                'costo_orario' => $fascia_ore,
                'costo_km' => $fascia_km[$key],
                'costo_diritto_chiamata' => $fascia_diritto_chiamata[$key],
                'costo_orario_tecnico' => $fascia_orario_tecnico[$key],
                'costo_km_tecnico' => $fascia_km_tecnico[$key],
                'costo_diritto_chiamata_tecnico' => $fascia_diritto_chiamata_tecnico[$key],
            ], [
                'idfasciaoraria' => $key, 'idtipointervento' => $id_record
            ]);
        }


        flash()->info(tr('Informazioni tipo intervento salvate correttamente!'));

        break;

    case 'add':
        $codice = post('codice');
        $descrizione = post('descrizione');

        $tipo = Tipo::build($codice, $descrizione);

        $tipo->tempo_standard = post('tempo_standard');
        $tipo->calcola_km = post('calcola_km');
        $tipo->costo_orario = post('costo_orario');
        $tipo->costo_km = post('costo_km');
        $tipo->costo_diritto_chiamata = post('costo_diritto_chiamata');
        $tipo->costo_orario_tecnico = post('costo_orario_tecnico');
        $tipo->costo_km_tecnico = post('costo_km_tecnico');
        $tipo->costo_diritto_chiamata_tecnico = post('costo_diritto_chiamata_tecnico');
        $tipo->save();

        // Fix per impostare i valori inziali a tutti i tecnici
        $tipo->fixTecnici();

        $id_record = $tipo->id;

        $fasce_orarie = $dbo->select('in_fasceorarie', '*', ['deleted_at' => null]);
        foreach ($fasce_orarie as $fascia_oraria) {
            $dbo->insert('in_fasceorarie_tipiintervento', [
                'idfasciaoraria' => $fascia_oraria['id'],
                'idtipointervento' => $id_record,
                'costo_orario' => post('costo_orario'),
                'costo_km' => post('costo_km'),
                'costo_diritto_chiamata' => post('costo_diritto_chiamata'),
                'costo_orario_tecnico' => post('costo_orario_tecnico'),
                'costo_km_tecnico' => post('costo_km_tecnico'),
                'costo_diritto_chiamata_tecnico' => post('costo_diritto_chiamata_tecnico'),
            ]);
        }

        flash()->info(tr('Nuovo tipo di intervento aggiunto!'));

        break;

    case 'delete':
        // Permetto eliminazione tipo intervento solo se questo non è utilizzado da nessun'altra parte a gestionale
        // UNION SELECT `in_tariffe`.`idtipointervento` FROM `in_tariffe` WHERE `in_tariffe`.`idtipointervento` = '.prepare($id_record).'
        // UNION SELECT `co_contratti_tipiintervento`.`idtipointervento` FROM `co_contratti_tipiintervento` WHERE `co_contratti_tipiintervento`.`idtipointervento` = '.prepare($id_record).'
        $elementi = $dbo->fetchArray('SELECT `in_interventi`.`idtipointervento`  FROM `in_interventi` WHERE `in_interventi`.`idtipointervento` = '.prepare($id_record).'
        UNION
        SELECT `an_anagrafiche`.`idtipointervento_default` AS `idtipointervento` FROM `an_anagrafiche` WHERE `an_anagrafiche`.`idtipointervento_default` = '.prepare($id_record).'
        UNION
        SELECT `co_preventivi`.`idtipointervento` FROM `co_preventivi` WHERE `co_preventivi`.`idtipointervento` = '.prepare($id_record).'
        UNION
        SELECT `co_promemoria`.`idtipointervento` FROM `co_promemoria` WHERE `co_promemoria`.`idtipointervento` = '.prepare($id_record).'
        UNION
        SELECT `in_interventi_tecnici`.`idtipointervento` FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idtipointervento` = '.prepare($id_record).'
        ORDER BY `idtipointervento`');

        if (empty($elementi)) {
            // Elimino anche le tariffe collegate ai vari tecnici
            $query = 'DELETE FROM in_tariffe WHERE idtipointervento='.prepare($id_record);
            $dbo->query($query);

            // Elimino anche le tariffe collegate ai contratti
            $query = 'DELETE FROM co_contratti_tipiintervento WHERE idtipointervento='.prepare($id_record);
            $dbo->query($query);

            $query = 'DELETE FROM in_fasceorarie_tipiintervento WHERE idtipointervento='.prepare($id_record);
            $dbo->query($query);

            $query = 'DELETE FROM in_tipiintervento WHERE idtipointervento='.prepare($id_record);
            $dbo->query($query);

            flash()->info(tr('Tipo di intervento eliminato!'));
            break;
        }

        // no break
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

    case 'addriga':

        $id_iva = post('idiva');
        $descrizione = post('descrizione');
        $qta = post('qta');
        $um = post('um');
        $id_tipointervento = post('id_tipointervento');
        $prezzo_acquisto = post('prezzo_acquisto');
        $prezzo_vendita = post('prezzo_vendita');
        $subtotale = $qta * $prezzo_vendita;

        $query = 'INSERT INTO in_righe_tipiinterventi(id_tipointervento, prezzo_acquisto, prezzo_vendita, descrizione, qta, um, subtotale, idiva) VALUES ('.prepare($id_tipointervento).', '.prepare($prezzo_acquisto).', '.prepare($prezzo_vendita).', '.prepare($descrizione).', '.prepare($qta).', '.prepare($um).', '.prepare($subtotale).', '.prepare($id_iva).')';
        $dbo->query($query);

        flash()->info(tr('Riga aggiunta!'));

        break;

    case 'editriga':

        $id_iva = post('idiva');
        $descrizione = post('descrizione');
        $qta = post('qta');
        $um = post('um');
        $idriga = post('idriga');
        $id_tipointervento = post('id_tipointervento');
        $prezzo_acquisto = post('prezzo_acquisto');
        $prezzo_vendita = post('prezzo_vendita');
        $subtotale = $qta * $prezzo_vendita;

        $query = 'UPDATE in_righe_tipiinterventi SET'.
            ' descrizione='.prepare($descrizione).','.
            ' qta='.prepare($qta).','.
            ' idiva='.prepare($id_iva).','.
            ' um='.prepare($um).','.
            ' id_tipointervento='.prepare($id_tipointervento).','.
            ' prezzo_acquisto='.prepare($prezzo_acquisto).','.
            ' prezzo_vendita='.prepare($prezzo_vendita).','.
            ' subtotale='.$subtotale.
            ' WHERE id='.prepare($idriga);
        $dbo->query($query);

        flash()->info(tr('Riga modificata!'));

        break;

    case 'delriga':
    
        $idriga = post('idriga');
        $query = "DELETE FROM in_righe_tipiinterventi WHERE id=".prepare($idriga);
        $dbo->query($query);

        flash()->info(tr('Riga eliminata!'));

        break;
}
