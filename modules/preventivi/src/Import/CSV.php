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

namespace Modules\Preventivi\Import;

use Carbon\Carbon;
use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) dei Preventivi.
 *
 * @since 2.4.44
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'numero',
                'label' => 'Numero',
                'primary_key' => true,
            ],
            [
                'field' => 'nome',
                'label' => 'Nome preventivo',
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione preventivo',
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Cliente',
            ],
            [
                'field' => 'idtipointervento',
                'label' => 'Tipo attività',
            ],
            [
                'field' => 'data_bozza',
                'label' => 'Data',
            ],
            [
                'field' => 'codice',
                'label' => 'Codice articolo',
            ],
            [
                'field' => 'qta',
                'label' => 'Quantità riga',
            ],
            [
                'field' => 'data_evasione',
                'label' => 'Data prevista evasione riga',
            ],
            [
                'field' => 'prezzo_unitario',
                'label' => 'Prezzo unitario riga',
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        $id_preventivo = $database->fetchOne('SELECT id FROM `co_preventivi` WHERE `numero`='.prepare($record['numero']))['id'];
        $preventivo = Preventivo::find($id_preventivo);

        if (empty($preventivo)) {
            $anagrafica = Anagrafica::where('ragione_sociale', $record['ragione_sociale'])->first();

            if (empty($anagrafica)) {
                $anagrafica = Anagrafica::build($record['ragione_sociale']);
                $tipo_cliente = (new TipoAnagrafica())->getByName('Cliente')->id_record;
                $anagrafica->tipologie = [$tipo_cliente];
                $anagrafica->save();
            }

            $tipo = TipoSessione::find($record['idtipointervento']) ?: TipoSessione::where('codice', 'GEN')->first();

            $preventivo = Preventivo::build($anagrafica, $tipo, $record['nome'], new Carbon($record['data_bozza']), 0);
            $preventivo->numero = $record['numero'];
            $preventivo->idstato = (new Stato())->getByName('Bozza')->id_record;
            $preventivo->descrizione = $record['descrizione'];
            $preventivo->save();
        }

        // Individuazione articolo
        $articolo_orig = ArticoloOriginale::where('codice', $record['codice'])->first();
        if (!empty($articolo_orig)) {
            $articolo = Articolo::build($preventivo, $articolo_orig);

            $articolo->setTranslation('name', $articolo_orig->name);
            $articolo->um = $articolo_orig->um ?: null;
            $articolo->data_evasione = new Carbon($record['data_evasione']) ?: null;

            $idiva = $articolo_orig->idiva_vendita ?: ($anagrafica->idiva_vendite ?: setting('Iva predefinita'));

            $articolo->setPrezzoUnitario($record['prezzo_unitario'], $idiva);
            $articolo->qta = $record['qta'];

            $articolo->save();
        }
    }

    public static function getExample()
    {
        return [
            ['Numero', 'Nome Preventivo', 'Descrizione Preventivo', 'Cliente', 'Tipo Attività', 'Data', 'Codice Articolo', 'Quantità riga', 'Data prevista evasione riga', 'Prezzo unitario riga'],
            ['15', 'Preventivo Materiali', 'Preventivo iniziale', 'Rossi', 'Generico', '27/04/2024', '001', '2', '30/04/2024', '50'],
            ['15', 'Preventivo Materiali', 'Preventivo iniziale', 'Rossi', 'Generico', '27/04/2024', '043', '1', '10/05/2024', '100'],
        ];
    }
}
