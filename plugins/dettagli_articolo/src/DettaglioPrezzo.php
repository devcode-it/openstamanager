<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Plugins\DettagliArticolo;

use Common\Model;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Modules\Iva\Aliquota;

/**
 * Classe per la gestione delle relazioni articolo-prezzo sulla base di un range di quantità e di una specifica anagrafica.
 *
 * @since 2.4.18
 */
class DettaglioPrezzo extends Model
{
    protected $table = 'mg_prezzi_articoli';

    /**
     * Crea una nuova relazione tra Articolo e Anagrafica per la gestione dei prezzi.
     *
     * @return self
     */
    public static function build(Articolo $articolo, Anagrafica $anagrafica, $direzione = 'uscita')
    {
        $model = parent::build();

        $model->anagrafica()->associate($anagrafica);
        $model->articolo()->associate($articolo);
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
        $id_iva = $this->articolo->idiva_vendita;

        // Calcolo prezzo di vendita ivato e non ivato
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $percentuale_aliquota = floatval(Aliquota::find($id_iva)->percentuale);
        if ($prezzi_ivati) {
            $this->prezzo_unitario_ivato = $prezzo_unitario;
            $this->prezzo_unitario = $prezzo_unitario / (1 + $percentuale_aliquota / 100);
        } else {
            $this->prezzo_unitario = $prezzo_unitario;
            $this->prezzo_unitario_ivato = $prezzo_unitario * (1 + $percentuale_aliquota / 100);
        }
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'id_articolo');
    }

    public static function dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
    {
        return self::where('id_articolo', $id_articolo)
            ->where('id_anagrafica', $id_anagrafica)
            ->where('dir', $direzione)
            ->whereNull('minimo')
            ->whereNull('massimo');
    }

    public static function dettagli($id_articolo, $id_anagrafica, $direzione)
    {
        return self::where('id_articolo', $id_articolo)
            ->where('id_anagrafica', $id_anagrafica)
            ->where('dir', $direzione)
            ->whereNotNull('minimo')
            ->whereNotNull('massimo');
    }
}
