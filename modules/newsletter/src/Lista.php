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

namespace Modules\Newsletter;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Traits\RecordTrait;

class Lista extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_lists';

    public static function build($name)
    {
        $model = new static();
        $model->name = $name;

        $model->save();

        return $model;
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        $query = $this->query;
        if (!empty($query)) {
            $database = database();

            // Rimozione record precedenti
            $database->delete('em_list_receiver', [
                'id_list' => $this->id,
            ]);

            // Ricerca nuovi record
            $results = $database->fetchArray($query);
            $gruppi = collect($results)
                ->groupBy('tipo');

            // Preparazione al salvataggio
            $destinatari = [];
            foreach ($gruppi as $tipo => $gruppo) {
                if ($tipo == 'anagrafica') {
                    $type = Anagrafica::class;
                } elseif ($tipo == 'sede') {
                    $type = Sede::class;
                } else {
                    $type = Referente::class;
                }

                foreach ($gruppo as $record) {
                    $destinatari[] = [
                        'record_type' => $type,
                        'record_id' => $record['id'],
                    ];
                }
            }

            // Aggiornamento destinatari
            foreach ($destinatari as $destinatario) {
                $data = array_merge($destinatario, [
                    'id_list' => $this->id,
                ]);

                $registrato = $database->select('em_list_receiver', '*', $data);
                if (empty($registrato)) {
                    $database->insert('em_list_receiver', $data);
                }
            }
        }

        return $result;
    }

    // Relazione Eloquent

    public function getDestinatari()
    {
        return $this->anagrafiche
            ->concat($this->sedi)
            ->concat($this->referenti);
    }

    public function anagrafiche()
    {
        return $this
            ->belongsToMany(Anagrafica::class, 'em_list_receiver', 'id_list', 'record_id')
            ->where('record_type', '=', Anagrafica::class)
            ->withTrashed();
    }

    public function sedi()
    {
        return $this
            ->belongsToMany(Sede::class, 'em_list_receiver', 'id_list', 'record_id')
            ->where('record_type', '=', Sede::class);
    }

    public function referenti()
    {
        return $this
            ->belongsToMany(Referente::class, 'em_list_receiver', 'id_list', 'record_id')
            ->where('record_type', '=', Referente::class);
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Liste';
    }
}
