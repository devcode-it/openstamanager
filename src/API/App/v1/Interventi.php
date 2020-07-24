<?php

namespace API\App\v1;

use API\App\AppResource;
use API\Interfaces\CreateInterface;
use API\Interfaces\UpdateInterface;
use Auth;
use Carbon\Carbon;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Interventi extends AppResource implements CreateInterface, UpdateInterface
{
    protected function getCleanupData()
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $query = 'SELECT in_interventi.id FROM in_interventi WHERE
            deleted_at IS NOT NULL
            OR EXISTS(
                SELECT orario_fine FROM in_interventi_tecnici WHERE
                    in_interventi_tecnici.idintervento = in_interventi.id
                    AND orario_fine NOT BETWEEN :period_start AND :period_end
                    AND idtecnico = :id_tecnico
                )';
        $records = database()->fetchArray($query, [
            ':period_end' => $end,
            ':period_start' => $start,
            ':id_tecnico' => $id_tecnico,
        ]);

        return array_column($records, 'id');
    }

    protected function getData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $query = 'SELECT in_interventi.id FROM in_interventi WHERE
            in_interventi.id IN (
                SELECT idintervento FROM in_interventi_tecnici
                WHERE in_interventi_tecnici.idintervento = in_interventi.id
                    AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                    AND in_interventi_tecnici.idtecnico = :id_tecnico
            )
            AND deleted_at IS NULL';

        // Filtro per data
        if ($last_sync_at) {
            $last_sync = new Carbon($last_sync_at);
            $query .= ' AND in_interventi.updated_at > '.prepare($last_sync);
        }
        $records = database()->fetchArray($query, [
            ':period_start' => $start,
            ':period_end' => $end,
            ':id_tecnico' => $id_tecnico,
        ]);

        return array_column($records, 'id');
    }

    protected function retrieveRecord($id)
    {
        $database = database();

        // Gestione della visualizzazione dei dettagli del record
        $query = "SELECT id,
            codice,
            richiesta,
            data_richiesta,
            descrizione,
            idanagrafica AS id_anagrafica,
            idtipointervento AS id_tipo_intervento,
            idstatointervento AS id_stato_intervento,
            informazioniaggiuntive AS informazioni_aggiuntive,
            IF(idsede_destinazione = 0, NULL, idsede_destinazione) AS id_sede,
            firma_file,
            IF(firma_data = '0000-00-00 00:00:00', '', firma_data) AS firma_data,
            firma_nome
        FROM in_interventi
        WHERE in_interventi.id = ".prepare($id);

        $record = $database->fetchOne($query);

        // Individuazione impianti collegati
        $impianti = $database->fetchArray('SELECT idimpianto AS id FROM my_impianti_interventi WHERE idintervento = '.prepare($id));
        $record['impianti'] = array_column($impianti, 'id');

        return $record;
    }

    protected function createRecord($data)
    {
        $anagrafica = Anagrafica::find($data['id_anagrafica']);
        $tipo = TipoSessione::find($data['id_tipo_intervento']);
        $stato = Stato::find($data['id_stato_intervento']);

        $data_richiesta = new Carbon($data['data_richiesta']);
        $intervento = Intervento::build($anagrafica, $tipo, $stato, $data_richiesta);

        $this->aggiornaRecord($intervento, $data);
        $intervento->save();

        return [
            'id' => $intervento->id,
            'codice' => $intervento->codice,
        ];
    }

    protected function updateRecord($data)
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
        $record->idstatointervento = $data['id_stato_intervento'];
        $record->descrizione = $data['descrizione'];
        $record->informazioniaggiuntive = $data['informazioni_aggiuntive'];

        // Aggiornamento impianti collegati
        $database->query('DELETE FROM my_impianti_interventi WHERE idintervento = '.prepare($record->id));
        foreach ($data['impianti'] as $id_impianto) {
            $database->insert('my_impianti_interventi', [
                'idimpianto' => $id_impianto,
                'idintervento' => $record->id,
            ]);
        }
    }
}
