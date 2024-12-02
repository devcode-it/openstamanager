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
    public function __construct(private readonly Fattura $fattura)
    {
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

        $righe_bollo = $this->fattura->getRighe()->filter(fn ($item, $key) => $item->aliquota != null && in_array($item->aliquota->codice_natura_fe, ['N2.1', 'N2.2', 'N3.5', 'N3.6', 'N4']));
        $importo_righe_bollo = $righe_bollo->sum('subtotale');

        // Leggo la marca da bollo se c'è e se il netto a pagare supera la soglia
        $bollo = ($this->fattura->direzione == 'uscita') ? $this->fattura->bollo : setting('Importo marca da bollo');

        $marca_da_bollo = 0;
        if ($bollo && abs($bollo) > 0 && abs($importo_righe_bollo) > setting("Soglia minima per l'applicazione della marca da bollo")) {
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
        $righe_bollo = $this->fattura->getRighe()->filter(fn ($item, $key) => $item->aliquota != null && in_array($item->aliquota->codice_natura_fe, ['N2.1', 'N2.2', 'N3.5', 'N3.6', 'N4']))->first();

        $addebita_bollo = $this->fattura->addebita_bollo;
        $marca_da_bollo = $this->getBollo();

        $cassa_pred = [];
        if (setting('Cassa previdenziale predefinita')) {
            $cassa_pred = database()->fetchOne('SELECT percentuale FROM co_rivalse WHERE id='.setting('Cassa previdenziale predefinita'));
        }

        // Verifico se la fattura ha righe con rivalsa applicata, esclusa la marca da bollo
        $rivalsa = ($this->fattura->rivalsainps > 0 && $this->fattura->rivalsainps != (setting('Importo marca da bollo') * $cassa_pred['percentuale'] / 100)) ? 1 : 0;

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
        }
        $riga->prezzo_unitario = $marca_da_bollo;
        $riga->qta = 1;
        $riga->descrizione = setting('Descrizione addebito bollo');
        $riga->id_iva = $righe_bollo->idiva ?: database()->fetchOne('SELECT `id` FROM `co_iva` WHERE `name` = "Escluso art. 15"')['id'];
        $riga->idconto = setting('Conto predefinito per la marca da bollo');
        $riga->iddocumento = $this->fattura->id ?: 0;

        // Applico la rivalsa alla marca da bollo se previsto
        if ((setting('Regime Fiscale') == 'RF19') && (!empty(setting('Cassa previdenziale predefinita')))) {
            $riga['id_rivalsa_inps'] = $rivalsa ? setting('Cassa previdenziale predefinita') : '';
        }

        $riga->save();

        return $riga->id;
    }
}
