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
use Illuminate\Database\Eloquent\Builder;
use Modules\Anagrafiche\Tipo;
use Modules\Impianti\Impianto;

class Impianti extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // TODO: modificare introducendo deleted_at su my_impianti
        return database()
            ->table('zz_operations')
            ->select('zz_operations.id_record')
            ->distinct()
            ->join('zz_modules', 'zz_modules.id', '=', 'zz_operations.id_module')
            ->leftJoin('zz_modules_lang', function ($join) {
                $join->on('zz_modules.id', '=', 'zz_modules_lang.id_record')
                    ->where('zz_modules_lang.id_lang', '=', \Models\Locale::getDefault()->id);
            })
            ->where('zz_modules_lang.name', '=', 'Impianti')
            ->where('zz_operations.op', '=', 'delete')
            ->whereNotNull('zz_operations.options')
            ->where('zz_operations.created_at', '>', $last_sync_at)
            ->pluck('id_record')
            ->toArray();
    }

    public function getModifiedRecords($last_sync_at)
    {
        $statement = Impianto::select('id', 'updated_at', 'idtecnico')
            ->whereHas('anagrafica.tipi', function (Builder $query) {
                $tipo_cliente = (new Tipo())->getByField('name', 'Cliente');
                $query->where('id', '=', $tipo_cliente);
            });

        // Limite impianti visualizzabili dal tecnico
        $limite_impianti = setting('Limita la visualizzazione degli impianti a quelli gestiti dal tecnico');

        if ($limite_impianti == 1 && !\Auth::user()->is_admin) {
            $id_tecnico = \Auth::user()->id_anagrafica;

            // Elenco di interventi di interesse
            $risorsa_interventi = $this->getRisorsaInterventi();
            // Da applicazione, i Clienti sono sincronizzati prima degli Interventi: last_sync_at permette di identificare le stesse modifiche
            $interventi = $risorsa_interventi->getModifiedRecords(null);
            $id_interventi = array_keys($interventi);

            $statement->where('idtecnico', $id_tecnico)->orWhere('id', 'IN', 'SELECT idimpianto FROM my_impianti_interventi WHERE idintervento IN ('.implode(',', $id_interventi).')');
        }

        // Filtro per data
        if ($last_sync_at) {
            $statement = $statement->where('updated_at', '>', $last_sync_at);
        }

        $records = $statement->get();

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT my_impianti.id,
            my_impianti.idanagrafica AS id_cliente,
            my_impianti.idsede AS id_sede,
            my_impianti.matricola,
            my_impianti.nome,
            my_impianti.descrizione,
            my_impianti.data AS data_installazione,
            my_impianti.proprietario,
            my_impianti.ubicazione,
            my_impianti.palazzo,
            my_impianti.scala,
            my_impianti.piano,
            my_impianti.interno,
            my_impianti.occupante,
            my_impianti_categorie.nome AS categoria
        FROM my_impianti
            LEFT JOIN my_impianti_categorie ON my_impianti_categorie.id = my_impianti.id_categoria
        WHERE my_impianti.id = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }
}
