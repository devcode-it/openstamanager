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

use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo;

class ControlloClienti extends Resource implements RetrieveInterface
{
    public function retrieve($data)
    {
        // Ricerca per Partita IVA
        $partita_iva = $data['partita_iva'];
        if (!empty($partita_iva)) {
            $cliente_partita_iva = Anagrafica::where('piva', $partita_iva)
                ->first();
        }

        // Ricerca per Codice fiscale
        $codice_fiscale = $data['codice_fiscale'];
        if (!empty($codice_fiscale)) {
            $cliente_codice_fiscale = Anagrafica::where('codice_fiscale', $codice_fiscale)
                ->first();
        }

        $cliente = $cliente_partita_iva ?: $cliente_codice_fiscale;

        // Aggiunta tipologia Cliente se non presente nell'anagrafica trovata
        if (!empty($cliente) && !$cliente->isTipo('Cliente')) {
            $tipo_cliente = Tipo::where('descrizione', '=', 'Cliente')->first();
            $tipi = $cliente->tipi->pluck('idtipoanagrafica')->toArray();

            $tipi[] = $tipo_cliente->id;

            $cliente->tipologie = $tipi;
            $cliente->save();
        }

        return [
            'id' => $cliente ? $cliente->id : '',
        ];
    }
}
