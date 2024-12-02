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

namespace Modules\Articoli\Export;

use Exporter\CSVExporter;
use Modules\Articoli\Articolo;

/**
 * Struttura per la gestione delle operazioni di esportazione (in CSV) degli Articoli.
 *
 * @since 2.4.26
 */
class CSV extends CSVExporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'codice',
                'label' => 'Codice',
                'primary_key' => true,
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'qta',
                'label' => 'Quantità',
                'type' => 'number',
            ],
            [
                'field' => 'um',
                'label' => 'Unità di misura',
            ],
            [
                'field' => 'prezzo_acquisto',
                'label' => 'Prezzo acquisto',
                'type' => 'number',
            ],
            [
                'field' => 'prezzo_vendita',
                'label' => 'Prezzo vendita',
                'type' => 'number',
            ],
            [
                'field' => 'peso_lordo',
                'label' => 'Peso lordo (KG)',
                'type' => 'number',
            ],
            [
                'field' => 'volume',
                'label' => 'Volume (M3)',
                'type' => 'number',
            ],
            [
                'field' => 'categoria.nome',
                'label' => 'Categoria',
            ],
            [
                'field' => 'sottocategoria.nome',
                'label' => 'Sottocategoria',
            ],
            [
                'field' => 'barcode',
                'label' => 'Barcode',
            ],
            [
                'field' => 'id_fornitore',
                'label' => 'Fornitore predefinito',
            ],
            [
                'field' => 'codice_iva_vendita',
                'label' => 'Codice IVA vendita',
            ],
            [
                'field' => 'ubicazione',
                'label' => 'Ubicazione',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
            ],
        ];
    }

    public function getRecords()
    {
        return Articolo::all();
    }
}
