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
        return database()
            ->table('zz_operations')
            ->select('zz_operations.id_record')
            ->distinct()
            ->join('zz_modules', 'zz_modules.id', '=', 'zz_operations.id_module')
            ->where('zz_modules.name', '=', 'Impianti')
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
                $tipo_cliente = Tipo::where('name', 'Cliente')->first()->id;
                $query->where('id', '=', $tipo_cliente);
            });

        // Filtro per data
        if ($last_sync_at) {
            $statement = $statement->where('updated_at', '>', $last_sync_at);
        }

        // Limite impianti visualizzabili dal tecnico
        $limite_impianti = setting('Limita la visualizzazione degli impianti a quelli gestiti dal tecnico');

        if ($limite_impianti == 1 && !\Auth::user()->is_admin) {
            $id_tecnico = \Auth::user()->id_anagrafica;

            $statement->where('idtecnico', $id_tecnico);
        }

        $records = $statement->get();

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT 
            `impianti`.`id`,
            `impianti`.`idanagrafica` AS id_cliente,
            `impianti`.`idsede` AS id_sede,
            `impianti`.`matricola`,
            `impianti`.`nome`,
            `impianti`.`descrizione`,
            `impianti`.`data` AS data_installazione,
            `impianti`.`proprietario`,
            `impianti`.`ubicazione`,
            `impianti`.`palazzo`,
            `impianti`.`scala`,
            `impianti`.`piano`,
            `impianti`.`interno`,
            `impianti`.`occupante`,
            `categorie_lang`.`title` AS categoria,
            `sottocategorie_lang`.`title` AS sottocategoria
        FROM 
            my_impianti AS impianti
        LEFT JOIN 
            my_impianti_categorie AS categorie 
            ON categorie.id = impianti.id_categoria
        LEFT JOIN 
            my_impianti_categorie_lang AS categorie_lang 
            ON categorie_lang.id_record = categorie.id 
            AND categorie_lang.id_lang = '.prepare(\Models\Locale::getDefault()->id).'
        LEFT JOIN 
            my_impianti_categorie AS sottocategorie 
            ON sottocategorie.id = impianti.id_sottocategoria
        LEFT JOIN 
            my_impianti_categorie_lang AS sottocategorie_lang 
            ON sottocategorie_lang.id_record = sottocategorie.id 
            AND sottocategorie_lang.id_lang = '.prepare(\Models\Locale::getDefault()->id).'
        WHERE `impianti`.`id` = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }
}
