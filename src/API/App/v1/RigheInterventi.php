<?php

namespace API\App\v1;

use API\App\AppResource;
use API\Exceptions\InternalError;
use Auth;
use Carbon\Carbon;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Intervento;
use UnexpectedValueException;

class RigheInterventi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $query = 'SELECT in_righe_interventi.id FROM in_righe_interventi WHERE in_righe_interventi.idintervento IN (
            SELECT in_interventi.id FROM in_interventi WHERE
            deleted_at IS NOT NULL
            OR EXISTS(
                SELECT orario_fine FROM in_interventi_tecnici WHERE
                    in_interventi_tecnici.idintervento = in_interventi.id
                    AND orario_fine NOT BETWEEN :period_start AND :period_end
                    AND idtecnico = :id_tecnico
            )
        )';
        $records = database()->fetchArray($query, [
            ':period_end' => $end,
            ':period_start' => $start,
            ':id_tecnico' => $id_tecnico,
        ]);

        $da_interventi = array_column($records, 'id');
        $mancanti = $this->getMissingIDs('in_righe_interventi', 'id', $last_sync_at);

        $results = array_unique(array_merge($da_interventi, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth(1);

        // Informazioni sull'utente
        $user = Auth::user();
        $id_tecnico = $user->id_anagrafica;

        $query = 'SELECT in_righe_interventi.id FROM in_righe_interventi WHERE in_righe_interventi.idintervento IN (
            SELECT in_interventi.id FROM in_interventi WHERE
            in_interventi.id IN (
                SELECT idintervento FROM in_interventi_tecnici
                WHERE in_interventi_tecnici.idintervento = in_interventi.id
                    AND in_interventi_tecnici.orario_fine BETWEEN :period_start AND :period_end
                    AND in_interventi_tecnici.idtecnico = :id_tecnico
            )
            AND deleted_at IS NULL
        )';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND in_righe_interventi.updated_at > '.prepare($last_sync_at);
        }
        $records = database()->fetchArray($query, [
            ':period_start' => $start,
            ':period_end' => $end,
            ':id_tecnico' => $id_tecnico,
        ]);

        return array_column($records, 'id');
    }

    public function retrieveRecord($id)
    {
        // Individuazione riga tramite classi
        $riga = $this->getRecord($id);

        // Generazione del record ristretto ai campi di interesse
        $record = [
            'id' => $riga->id,
            'id_intervento' => $riga->idintervento,
            'descrizione' => $riga->descrizione,
            'qta' => $riga->qta,
            'um' => $riga->um,
            'ordine' => $riga->order,

            // Caratteristiche della riga
            'id_articolo' => $riga->idarticolo,
            'is_articolo' => intval($riga->isArticolo()),
            'is_riga' => intval($riga->isRiga()),
            'is_descrizione' => intval($riga->isDescrizione()),
            'is_sconto' => intval($riga->isSconto()),

            // Campi contabili
            'costo_unitario' => $riga->costo_unitario,
            'prezzo_unitario' => $riga->prezzo_unitario,
            'tipo_sconto' => $riga->tipo_sconto,
            'sconto_percentuale' => $riga->sconto_percentuale,
            'sconto_unitario' => $riga->sconto_unitario,
            'id_iva' => $riga->idiva,
            'iva_unitaria' => $riga->iva_unitaria,
            'prezzo_unitario_ivato' => $riga->prezzo_unitario_ivato,
            'sconto_iva_unitario' => $riga->sconto_iva_unitario,
            'sconto_unitario_ivato' => $riga->sconto_unitario_ivato,

            // Campi contabili di riepilogo
            'imponibile' => $riga->imponibile,
            'sconto' => $riga->sconto,
            'totale_imponibile' => $riga->totale_imponibile,
            'iva' => $riga->iva,
            'totale' => $riga->totale,
        ];

        return $record;
    }

    public function createRecord($data)
    {
        $intervento = Intervento::find($data['id_intervento']);
        if ($data['is_articolo']) {
            $originale = ArticoloOriginale::find($data['id_articolo']);
            $riga = Articolo::build($intervento, $originale);
        } elseif ($data['is_sconto']) {
            // TODO: sconti
        } else {
            $riga = Riga::build($intervento);
        }

        $this->aggiornaRecord($riga, $data);
        $riga->save();

        return [
            'id' => $riga->id,
        ];
    }

    public function updateRecord($data)
    {
        $riga = $this->getRecord($data['id']);

        $this->aggiornaRecord($riga, $data);
        $riga->save();

        return [];
    }

    public function deleteRecord($id)
    {
        $riga = $this->getRecord($id);
        $riga->delete();
    }

    protected function getRecord($id)
    {
        // Individuazione delle caratteristiche del record
        $data = database()->fetchOne('SELECT idintervento AS id_intervento,
           IF(idarticolo IS NULL OR idarticolo = 0, 0, 1) AS is_articolo,
           is_descrizione,
           is_sconto
        FROM in_righe_interventi WHERE in_righe_interventi.id = '.prepare($id));

        // Individuazione riga tramite classi
        $type = $this->getType($data);
        $intervento = Intervento::find($data['id_intervento']);

        return $intervento->getRiga($type, $id);
    }

    protected function getType($data)
    {
        if (!empty($data['is_sconto'])) {
            $type = Sconto::class;
        } elseif (!empty($data['is_articolo'])) {
            $type = Articolo::class;
        } else {
            $type = Riga::class;
        }

        return $type;
    }

    protected function aggiornaRecord($record, $data)
    {
        $record->descrizione = $data['descrizione'];
        $record->um = $data['um'] ?: null;

        //$record->costo_unitario = $data['costo_unitario'] ?: 0;
        $record->setPrezzoUnitario($data['prezzo_unitario'], $data['id_iva']);
        $record->setSconto($data['sconto_percentuale'] ?: $data['sconto_unitario'], $data['tipo_sconto']);

        try {
            $record->qta = $data['qta'];
        } catch (UnexpectedValueException $e) {
            throw new InternalError();
        }
    }
}
