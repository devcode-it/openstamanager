<?php

namespace API\App\v1;

use API\App\AppResource;
use Auth;
use Carbon\Carbon;
use Intervention\Image\ImageManagerStatic;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Interventi extends AppResource
{
    protected function getCleanupData($last_sync_at)
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
            OR (
                in_interventi.id NOT IN (
                    SELECT idintervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.idintervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                        AND in_interventi_tecnici.idtecnico = :id_tecnico
                )
                AND in_interventi.id NOT IN (
                    SELECT idintervento FROM in_interventi_tecnici
                )
            )';
        $records = database()->fetchArray($query, [
            ':period_end' => $end,
            ':period_start' => $start,
            ':id_tecnico' => $id_tecnico,
        ]);

        return array_column($records, 'id');
    }

    protected function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $query = 'SELECT in_interventi.id FROM in_interventi WHERE
            deleted_at IS NULL AND (
                in_interventi.id IN (
                    SELECT idintervento FROM in_interventi_tecnici
                    WHERE in_interventi_tecnici.idintervento = in_interventi.id
                        AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                        AND in_interventi_tecnici.idtecnico = :id_tecnico
                )
                OR in_interventi.id NOT IN (
                    SELECT idintervento FROM in_interventi_tecnici
                )
            )';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND in_interventi.updated_at > '.prepare($last_sync_at);
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
            idanagrafica AS id_cliente,
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
        $record->richiesta = $data['richiesta'];
        $record->descrizione = $data['descrizione'];
        $record->informazioniaggiuntive = $data['informazioni_aggiuntive'];

        // Salvataggio firma eventuale
        if (empty($record->firma_nome) && !empty($data['firma_nome'])) {
            $record->firma_nome = $data['firma_nome'];
            $record->firma_data = $data['firma_data'];

            // Salvataggio fisico
            $firma_file = $this->salvaFirma($data['firma_contenuto']);
            $record->firma_file = $firma_file;
        }

        // Aggiornamento impianti collegati
        $database->query('DELETE FROM my_impianti_interventi WHERE idintervento = '.prepare($record->id));
        foreach ($data['impianti'] as $id_impianto) {
            $database->insert('my_impianti_interventi', [
                'idimpianto' => $id_impianto,
                'idintervento' => $record->id,
            ]);
        }
    }

    protected function salvaFirma($firma_base64)
    {
        // Salvataggio firma
        $firma_file = 'firma_'.time().'.png';

        $data = explode(',', $firma_base64);

        $img = ImageManagerStatic::make(base64_decode($data[1]));
        $img->resize(680, 202, function ($constraint) {
            $constraint->aspectRatio();
        });

        $img->save(DOCROOT.'/files/interventi/'.$firma_file);

        return $firma_file;
    }
}
