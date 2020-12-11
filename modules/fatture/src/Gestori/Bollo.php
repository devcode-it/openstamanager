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

namespace Modules\Fatture\Gestori;

use Modules\Fatture\Components;
use Modules\Fatture\Fattura;

/**
 * Classe dedicata alla gestione del Bollo per la Fattura, compreso il calcolo del relativo valore e la generazione dinamica della riga associata.
 *
 * @since 2.4.17
 */
class Bollo
{
    private $fattura;

    public function __construct(Fattura $fattura)
    {
        $this->fattura = $fattura;
    }

    /**
     * Metodo per calcolare automaticamente il bollo da applicare al documento.
     *
     * @return float
     */
    public function getBollo()
    {
        if (isset($this->fattura->bollo)) {
            return $this->fattura->bollo;
        }

        $righe_bollo = $this->fattura->getRighe()->filter(function ($item, $key) {
            return $item->aliquota != null && in_array($item->aliquota->codice_natura_fe, ['N1', 'N2.1', 'N2.2', 'N3.1', 'N3.2', 'N3.3', 'N3.4', 'N3.5', 'N3.6', 'N4']);
        });
        $importo_righe_bollo = $righe_bollo->sum('netto');

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bollo = ($this->fattura->direzione == 'uscita') ? $this->fattura->bollo : setting('Importo marca da bollo');

        $marca_da_bollo = 0;
        if (abs($bollo) > 0 && abs($importo_righe_bollo) > setting("Soglia minima per l'applicazione della marca da bollo")) {
            $marca_da_bollo = $bollo;
        }

        // Se l'importo è negativo può essere una nota di credito, quindi cambio segno alla marca da bollo
        $marca_da_bollo = abs($marca_da_bollo);

        return $marca_da_bollo;
    }

    /**
     * Metodo per aggiornare ed eventualmente aggiungere la marca da bollo al documento.
     */
    public function manageRigaMarcaDaBollo()
    {
        $riga = $this->fattura->rigaBollo;

        $addebita_bollo = $this->fattura->addebita_bollo;
        $marca_da_bollo = $this->getBollo();

        // Rimozione riga bollo se nullo
        if (empty($addebita_bollo) || empty($marca_da_bollo)) {
            if (!empty($riga)) {
                $riga->delete();
            }

            return null;
        }

        // Creazione riga bollo se non presente
        if (empty($riga)) {
            $riga = Components\Riga::build($this->fattura);
            $riga->save();
        }

        $riga->prezzo_unitario = $marca_da_bollo;
        $riga->qta = $this->fattura->isNota() ? -1 : 1;
        $riga->descrizione = setting('Descrizione addebito bollo');
        $riga->id_iva = setting('Iva da applicare su marca da bollo');
        $riga->idconto = setting('Conto predefinito per la marca da bollo');

        $riga->save();

        return $riga->id;
    }
}
