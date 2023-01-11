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

namespace Modules\ListiniCliente;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Articoli\Articolo AS ArticoloOriginale;
use Modules\Iva\Aliquota;

/*
 * Classe per la gestione delle relazioni articolo-prezzo sulla base di un range di quantità e di una specifica anagrafica.
 *
 * @since 2.4.18
 */
class Articolo extends Model
{
    use SimpleModelTrait;

    protected $table = 'mg_listini_articoli';

    /**
     * Crea una nuova relazione tra Articolo e Listino per la gestione dei prezzi.
     *
     * @return self
     */
    public static function build(ArticoloOriginale $articolo, $id_listino, $direzione = 'entrata')
    {
        $model = new static();

        $model->articolo()->associate($articolo);
        $model->id_listino = $id_listino;
        $model->dir = $direzione == 'uscita' ? 'uscita' : 'entrata';

        $model->save();

        return $model;
    }

    /**
     * Imposta il prezzo di vendita sulla base dell'impstazione per l'utilizzo dei prezzi comprensivi di IVA.
     *
     * @param $prezzo_unitario
     */
    public function setPrezzoUnitario($prezzo_unitario)
    {
        $id_iva = $this->articolo->idiva_vendita ?: setting('Iva predefinita');

        // Calcolo prezzo di vendita ivato e non ivato
        $prezzi_ivati = ($this->dir == 'entrata' ? setting('Utilizza prezzi di vendita comprensivi di IVA') : 0);
        $percentuale_aliquota = floatval(Aliquota::find($id_iva)->percentuale);
        if ($prezzi_ivati) {
            $this->prezzo_unitario_ivato = $prezzo_unitario;
            $this->prezzo_unitario = $prezzo_unitario / (1 + $percentuale_aliquota / 100);
        } else {
            $this->prezzo_unitario = $prezzo_unitario;
            $this->prezzo_unitario_ivato = $prezzo_unitario * (1 + $percentuale_aliquota / 100);
        }
    }

    public function articolo()
    {
        return $this->belongsTo(ArticoloOriginale::class, 'id_articolo');
    }

    public function listino()
    {
        return $this->belongsTo(Listino::class, 'id_listino');
    }
}
