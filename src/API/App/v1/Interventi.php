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

namespace API\App\v1;

use API\App\AppResource;
use Carbon\Carbon;
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Interventi extends AppResource
{
    public function getDateDiInteresse()
    {
        // Periodo per selezionare gli interventi
        $today = new Carbon();
        $mesi_precedenti = intval(setting('Mesi per lo storico delle Attività'));
        $start = $today->copy()->subMonths($mesi_precedenti);
        $end = $today->copy()->addMonth();

        return [
            'today' => $today,
            'start' => $start,
            'end' => $end,
        ];
    }

    public function getCleanupData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $date = $this->getDateDiInteresse();
        $start = $date['start'];
        $end = $date['end'];

        $remove_end = $start->copy();
        $remove_start = $remove_end->copy()->subMonths(2);

        // Informazioni sull'utente
        $id_tecnico = auth_osm()->getUser()->id_anagrafica;

        if (auth_osm()->getUser()->is_admin) {
            $query = 'SELECT in_interventi.id FROM in_interventi WHERE
            deleted_at IS NOT NULL
            OR (
                in_interventi.id NOT IN (
                    SELECT id_intervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.id_intervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                )
                AND in_interventi.id IN (
                    SELECT id_intervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.id_intervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :remove_period_start AND :remove_period_end
                )
            )';

            $records = database()->fetchArray($query, [
                ':period_end' => $end,
                ':period_start' => $start,
                ':remove_period_end' => $remove_end,
                ':remove_period_start' => $remove_start,
            ]);
        } else {
            $query = 'SELECT in_interventi.id FROM in_interventi WHERE
            deleted_at IS NOT NULL
            OR (
                in_interventi.id NOT IN (
                    SELECT id_intervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.id_intervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                        AND in_interventi_tecnici.id_tecnico = :id_tecnico_q1
                )
                AND in_interventi.id IN (
                    SELECT id_intervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.id_intervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :remove_period_start AND :remove_period_end
                        AND in_interventi_tecnici.id_tecnico = :id_tecnico_q2
                )
            )';

            $records = database()->fetchArray($query, [
                ':period_end' => $end,
                ':period_start' => $start,
                ':remove_period_end' => $remove_end,
                ':remove_period_start' => $remove_start,
                ':id_tecnico_q1' => $id_tecnico,
                ':id_tecnico_q2' => $id_tecnico,
            ]);
        }

        $interventi = array_column($records, 'id');
        $mancanti = $this->getMissingIDs('in_interventi', 'id', $last_sync_at);

        return array_merge($mancanti, $interventi);
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $date = $this->getDateDiInteresse();
        $start = $date['start'];
        $end = $date['end'];

        // Informazioni sull'utente
        $id_tecnico = auth_osm()->getUser()->id_anagrafica;

        if (setting('Visualizza solo promemoria assegnati') == 1) {
            if (auth_osm()->getUser()->is_admin) {
                $query = '
                SELECT
                    `in_interventi`.`id`,
                    `in_interventi`.`updated_at`
                FROM
                    `in_interventi`
                WHERE
                    `deleted_at` IS NULL AND (`in_interventi`.`id` IN (
                        SELECT `id_intervento` FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id` AND `in_interventi_tecnici`.`orario_fine` BETWEEN :period_start AND :period_end)
                        OR 
                        (`in_interventi`.`id` NOT IN (SELECT `id_intervento` FROM `in_interventi_tecnici`) AND `in_interventi`.`id_stato` IN (SELECT `id` FROM `in_stati_intervento` WHERE `is_bloccato` = 0)
                        )
                    )';
            } else {
                $query = '
                    SELECT
                        `in_interventi`.`id`,
                        `in_interventi`.`updated_at`
                    FROM 
                        `in_interventi` 
                    WHERE
                        `deleted_at` IS NULL AND (
                        `in_interventi`.`id` IN (
                            SELECT `id_intervento` FROM `in_interventi_tecnici`
                            WHERE `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id`
                                AND `in_interventi_tecnici`.`orario_fine` BETWEEN :period_start AND :period_end
                                AND `in_interventi_tecnici`.`id_tecnico` = :id_tecnico_q1
                        )
                        OR (
                            `in_interventi`.`id` NOT IN (
                                SELECT `id_intervento` FROM `in_interventi_tecnici`
                            )
                            AND `in_interventi`.`id_stato` IN (SELECT `id` FROM `in_stati_intervento` WHERE `is_bloccato` = 0) AND `in_interventi`.`id` IN (
                                SELECT `id_intervento` FROM `in_interventi_tecnici_assegnati` WHERE `in_interventi_tecnici_assegnati`.`id_tecnico` = :id_tecnico_q2
                            )
                        )
                    )';
            }
        } else {
            if (auth_osm()->getUser()->is_admin) {
                $query = '
                    SELECT
                        `in_interventi`.`id`,
                        `in_interventi`.`updated_at`
                    FROM 
                        `in_interventi` 
                    WHERE
                        `deleted_at` IS NULL AND (
                            `in_interventi`.`id` IN (
                                SELECT `id_intervento` FROM `in_interventi_tecnici`
                                WHERE `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id`
                                    AND `in_interventi_tecnici`.`orario_fine` BETWEEN :period_start AND :period_end
                            )
                            OR (
                                `in_interventi`.`id` NOT IN (
                                    SELECT `id_intervento` FROM `in_interventi_tecnici`
                                )
                                AND `in_interventi`.`id_stato` IN (SELECT `id` FROM `in_stati_intervento` WHERE `is_bloccato` = 0)
                            )
                        )';
            } else {
                $query = '
                    SELECT
                        `in_interventi`.`id`,
                        `in_interventi`.`updated_at`
                    FROM 
                        `in_interventi` 
                    WHERE
                        `deleted_at` IS NULL AND (
                            `in_interventi`.`id` IN (
                                SELECT `id_intervento` FROM `in_interventi_tecnici`
                                WHERE `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id`
                                    AND `in_interventi_tecnici`.`orario_fine` BETWEEN :period_start AND :period_end
                                    AND `in_interventi_tecnici`.`id_tecnico` = :id_tecnico_q1
                            )
                            OR (
                                `in_interventi`.`id` NOT IN (
                                    SELECT `id_intervento` FROM `in_interventi_tecnici`
                                )
                                AND `in_interventi`.`id_stato` IN (SELECT `id` FROM `in_stati_intervento` WHERE `is_bloccato` = 0)
                            )
                        )';
            }
        }

        // Filtro per data
        // Gestione di tecnici assegnati o impianti modificati
        // Possibile problematica: in caso di rimozione di un tecnico assegnato o impianto collegato, la modifica non viene rilevata
        if ($last_sync_at) {
            $query .= ' AND (
                `in_interventi`.`updated_at` > '.prepare($last_sync_at).' OR
                `in_interventi`.`id` IN (
                    SELECT `id_intervento` FROM `my_impianti_interventi` WHERE `my_impianti_interventi`.`created_at` > '.prepare($last_sync_at).'
                    UNION SELECT `id_intervento` FROM `in_interventi_tecnici_assegnati` WHERE `in_interventi_tecnici_assegnati`.`created_at` > '.prepare($last_sync_at).'
                )
            )';
        }

        if (setting('Visualizza solo promemoria assegnati') == 1) {
            if (auth_osm()->getUser()->is_admin) {
                $records = database()->fetchArray($query, [
                    ':period_start' => $start,
                    ':period_end' => $end,
                ]);
            } else {
                $records = database()->fetchArray($query, [
                    ':period_start' => $start,
                    ':period_end' => $end,
                    ':id_tecnico_q1' => $id_tecnico,
                    ':id_tecnico_q2' => $id_tecnico,
                ]);
            }
        } else {
            if (auth_osm()->getUser()->is_admin) {
                $records = database()->fetchArray($query, [
                    ':period_start' => $start,
                    ':period_end' => $end,
                ]);
            } else {
                $records = database()->fetchArray($query, [
                    ':period_start' => $start,
                    ':period_end' => $end,
                    ':id_tecnico_q1' => $id_tecnico,
                ]);
            }
        }

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        $database = database();

        // Gestione della visualizzazione dei dettagli del record
        $query = "SELECT id,
            codice,
            richiesta,
            data_richiesta,
            data_scadenza,
            descrizione,
            id_anagrafica AS id_cliente,
            id_contratto,
            id_preventivo,
            id_tipo_intervento AS id_tipo_intervento,
            id_stato AS id_stato,
            id_pagamento AS id_pagamento,
            informazioni_aggiuntive AS informazioni_aggiuntive,
            IF(id_sede_destinazione = 0, NULL, id_sede_destinazione) AS id_sede,
            IF(firma_data = '0000-00-00 00:00:00', '', firma_data) AS firma_data,
            firma_nome
        FROM in_interventi
        WHERE in_interventi.id = ".prepare($id);

        $record = $database->fetchOne($query);

        // Individuazione degli impianti collegati
        $impianti = $database->fetchArray('SELECT id_impianto AS id FROM my_impianti_interventi WHERE id_intervento = '.prepare($id));
        $record['impianti'] = array_column($impianti, 'id');

        // Individuazione dei tecnici assegnati
        $tecnici = $database->fetchArray('SELECT id_tecnico AS id FROM in_interventi_tecnici_assegnati WHERE id_intervento = '.prepare($id));
        $record['tecnici_assegnati'] = array_column($tecnici, 'id');

        return $record;
    }

    public function createRecord($data)
    {
        $anagrafica = Anagrafica::find($data['id_cliente']);
        $tipo = TipoSessione::find($data['id_tipo_intervento']);
        $stato = Stato::find($data['id_stato']);

        $data_richiesta = new Carbon($data['data_richiesta']);
        $intervento = Intervento::build($anagrafica, $tipo, $stato, $data_richiesta);

        $this->aggiornaRecord($intervento, $data);
        $intervento->save();

        return [
            'id' => $intervento->id,
            'codice' => $intervento->codice,
        ];
    }

    public function updateRecord($data)
    {
        $intervento = Intervento::find($data['id']);

        $this->aggiornaRecord($intervento, $data);
        $intervento->save();

        return [];
    }

    protected function aggiornaRecord($record, $data)
    {
        $database = database();

        // Aggiornamento intervento
        $record->id_stato = $data['id_stato'];
        $record->id_contratto = $data['id_contratto'] ?: null;
        $record->id_preventivo = $data['id_preventivo'] ?: null;
        $record->richiesta = $data['richiesta'];
        $record->descrizione = $data['descrizione'];
        $record->informazioni_aggiuntive = $data['informazioni_aggiuntive'];
        $record->id_sede_destinazione = $data['id_sede'] ?: 0;
        $record->id_pagamento = $data['id_pagamento'] ?: 0;

        // Salvataggio firma eventuale
        if (empty($record->firma_file) && !empty($data['firma_contenuto'])) {
            $record->firma_nome = $data['firma_nome'];
            $record->firma_data = $data['firma_data'];

            $this->salvaFirma($data['firma_contenuto'], $record->id);
        }

        // Aggiornamento degli impianti collegati
        $database->query('DELETE FROM my_impianti_interventi WHERE id_intervento = '.prepare($record->id));
        foreach ($data['impianti'] as $id_impianto) {
            if (!empty($id_impianto)) {
                $database->insert('my_impianti_interventi', [
                    'id_impianto' => $id_impianto,
                    'id_intervento' => $record->id,
                ]);
            }
        }

        // Aggiornamento dei tecnici assegnati
        $database->query('DELETE FROM in_interventi_tecnici_assegnati WHERE id_intervento = '.prepare($record->id));
        $tecnici_assegnati = (array) $data['tecnici_assegnati'];
        $database->sync('in_interventi_tecnici_assegnati', [
            'id_intervento' => $record->id,
        ], [
            'id_tecnico' => $tecnici_assegnati,
        ]);

        if (!empty($data['idrichiesta'])) {
            database()->query('UPDATE in_richieste SET id_intervento = '.prepare($record->id).', updated_at=NOW() WHERE id = '.prepare($data['idrichiesta']));
        }
    }

    protected function salvaFirma($firma_base64, $id_intervento)
    {
        $data = explode(',', (string) $firma_base64);
        $img = getImageManager()->decodeBinary(base64_decode($data[1]));
        $img->scaleDown(680, 202);
        $encoded_image = $img->encodeUsingMediaType('image/jpeg');
        $file_content = $encoded_image->toString();

        // Upload del file in zz_files
        \Uploads::upload($file_content, [
            'name' => 'firma.jpg',
            'category' => 'Firme',
            'id_module' => Module::where('name', 'Interventi')->first()->id,
            'id_record' => $id_intervento,
            'key' => 'signature',
        ]);
    }

    protected function authorizeRecord($id, $user)
    {
        if ($user->is_admin) {
            return true;
        }

        // Verifica che questo tecnico sia assegnato all'intervento
        $count = database()->fetchOne(
            'SELECT COUNT(*) AS cnt FROM in_interventi_tecnici 
             WHERE idintervento = '.prepare($id).' AND idtecnico = '.prepare($user->id_anagrafica)
        );

        return $count['cnt'] > 0;
    }
}
