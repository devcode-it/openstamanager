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

namespace Plugins\DichiarazioniIntento;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;

/*
 * Classe per la gestione delle dichiarazione d'intento.
 *
 * @since 2.4.11
 */
class Dichiarazione extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'co_dichiarazioni_intento';

    /**
     * Crea una nuova dichiarazione d'intento.
     *
     * @param $data
     * @param $numero_protocollo
     * @param $numero_progressivo
     * @param $data_inizio
     * @param $data_fine
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $data, $numero_protocollo, $numero_progressivo, $data_inizio, $data_fine)
    {
        $model = new static();

        $model->anagrafica()->associate($anagrafica);

        $model->data = $data;
        $model->numero_protocollo = $numero_protocollo;
        $model->numero_progressivo = $numero_progressivo;
        $model->data_inizio = $data_inizio;
        $model->data_fine = $data_fine;

        $model->save();

        return $model;
    }

    /**
     * Metodo per ricalcolare il totale utlizzato della dichiarazione.
     */
    public function fixTotale()
    {
        $this->setRelations([]);

        $righe = collect();
        $fatture = $this->fatture;
        foreach ($fatture as $fattura) {
            $righe = $righe->merge($fattura->getRighe());
        }

        // Filtro delle righe per IVA
        $id_iva = setting("Iva per lettere d'intento");
        $righe_dichiarazione = $righe->filter(function ($item, $key) use ($id_iva) {
            return $item->aliquota != null && $item->aliquota->id == $id_iva;
        });

        $totale = $righe_dichiarazione->sum('totale_imponibile') ?: 0;
        $this->totale = $totale;
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'id_dichiarazione_intento');
    }
}
