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
use API\Exceptions\InternalError;
use Carbon\Carbon;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Descrizione;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Intervento;

class RigheInterventi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth();

        // Informazioni sull'utente
        $user = \Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getCleanupData($last_sync_at);

        // Elenco sessioni degli interventi da rimuovere
        $da_interventi = [];
        if (!empty($interventi)) {
            $query = 'SELECT in_righe_interventi.id
        FROM in_righe_interventi
            INNER JOIN in_interventi ON in_righe_interventi.idintervento = in_interventi.id
        WHERE
            in_interventi.id IN ('.implode(',', $interventi).')';
            $records = database()->fetchArray($query);

            $da_interventi = array_column($records, 'id');
        }

        $mancanti = $this->getMissingIDs('in_righe_interventi', 'id', $last_sync_at);
        $results = array_unique(array_merge($da_interventi, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Periodo per selezionare interventi
        $today = new Carbon();
        $start = $today->copy()->subMonths(2);
        $end = $today->copy()->addMonth();

        // Informazioni sull'utente
        $user = \Auth::user();
        $id_tecnico = $user->id_anagrafica;

        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getModifiedRecords(null);
        if (empty($interventi)) {
            return [];
        }

        $id_interventi = array_keys($interventi);
        $query = 'SELECT in_righe_interventi.id, in_righe_interventi.updated_at FROM in_righe_interventi WHERE in_righe_interventi.idintervento IN ('.implode(',', $id_interventi).')';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND in_righe_interventi.updated_at > '.prepare($last_sync_at);
        }
        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
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

    protected function getRisorsaInterventi()
    {
        return new Interventi();
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
        } elseif (!empty($data['is_descrizione'])) {
            $type = Descrizione::class;
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

        if (empty($data['id_iva'])) {
            if ($data['is_articolo']) {
                $originale = ArticoloOriginale::find($data['id_articolo']);
                $data['id_iva'] = $originale->idiva_vendita;
            } else {
                $data['id_iva'] = setting('Iva predefinita');
            }
        }

        try {
            $record->qta = $data['qta'];
        } catch (\UnexpectedValueException) {
            throw new InternalError();
        }

        // Impostazione prezzo unitario
        $data['prezzo_unitario'] = $data['prezzo_unitario'] ?: 0;
        $record->setPrezzoUnitario($data['prezzo_unitario'], $data['id_iva']);

        // Impostazione sconto
        $sconto = $data['sconto_percentuale'] ?: $data['sconto_unitario'];
        if (!empty($sconto)) {
            $record->setSconto($sconto, $data['tipo_sconto']);
        }
    }
}
