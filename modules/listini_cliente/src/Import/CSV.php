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

namespace Modules\ListiniCliente\Import;

use Importer\CSVImporter;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\ListiniCliente\Articolo;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Articoli.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'nome_listino',
                'label' => 'Nome listino',
            ],
            [
                'field' => 'codice',
                'label' => 'Codice articolo',
                'primary_key' => true,
            ],
            [
                'field' => 'data_scadenza',
                'label' => 'Data scadenza',
            ],
            [
                'field' => 'prezzo_unitario',
                'label' => 'Prezzo unitario',
            ],
            [
                'field' => 'sconto_percentuale',
                'label' => 'Sconto percentuale',
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $id_listino = $database->fetchOne('SELECT id FROM mg_listini WHERE nome = '.prepare($record['nome_listino']))['id'];
        $id_articolo = $database->fetchOne('SELECT id FROM mg_articoli WHERE codice = '.prepare($record['codice']))['id'];
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

        if (!empty($id_listino) && !empty($id_articolo)) {
            $articolo_originale = ArticoloOriginale::find($id_articolo);
            $prezzo_unitario = $prezzi_ivati ? $articolo_originale->prezzo_vendita_ivato : $articolo_originale->prezzo_vendita;

            $articolo_listino = Articolo::where('id_articolo', $id_articolo)->where('id_listino', $id_listino)->first();

            if (!$articolo_listino) {
                $articolo_listino = Articolo::build($articolo_originale, $id_listino);
            }
            $articolo_listino->data_scadenza = $record['data_scadenza'] ?: null;
            $articolo_listino->setPrezzoUnitario($record['prezzo_unitario'] ?: $prezzo_unitario);
            $articolo_listino->sconto_percentuale = $record['sconto_percentuale'] ?: 0;
            $articolo_listino->save();
        }
    }

    public static function getExample()
    {
        return [
            ['Nome listino', 'Codice articolo', 'Data scadenza', 'Prezzo unitario', 'Sconto percentuale'],
            ['Listino 1', '1234', '2024-12-31', '100', '10'],
            ['Listino 1', '5678', '', '100', '50'],
            ['Listino 1', '9101', '2024-07-31', '100', ''],
        ];
    }
}
