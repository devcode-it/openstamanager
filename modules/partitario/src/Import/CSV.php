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

namespace Modules\Partitario\Import;

use Importer\CSVImporter;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) del piano dei conti.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'numero',
                'label' => 'Conto',
                'primary_key' => true,
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'idpianodeiconti1',
                'label' => 'Sezione',
            ],
            [
                'field' => 'dir',
                'label' => 'Direzione',
                'names' => [
                    'Direzione',
                    'direzione',
                    'dir',
                ],
            ],
        ];
    }

    public function import($record, $update_record = true, $add_record = true)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        $numero = explode('.', (string) $record['numero']);
        $codice_conto2 = $numero[0];
        $codice_conto3 = $numero[1];

        // Estraggo il conto1
        $idpianodeiconti1 = $database->fetchOne('SELECT id FROM co_pianodeiconti1 WHERE LOWER(descrizione)=LOWER('.prepare($record['idpianodeiconti1']).')')['id'];

        // Estraggo il conto,
        $idpianodeiconti2 = $database->fetchOne('SELECT id FROM co_pianodeiconti2 WHERE numero='.prepare($codice_conto2))['id'];

        if ($add_record) {
            if (empty($idpianodeiconti2) && empty($codice_conto3)) {
                $database->insert('co_pianodeiconti2', [
                    'numero' => $codice_conto2,
                    'descrizione' => $record['descrizione'],
                    'idpianodeiconti1' => $idpianodeiconti1,
                    'dir' => $record['dir'],
                ]);
            } elseif (!empty($idpianodeiconti2) && !empty($codice_conto3)) {
                $idpianodeiconti3 = $database->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE numero='.prepare($codice_conto3).' AND idpianodeiconti2='.prepare($idpianodeiconti2))['id'];

                if (empty($idpianodeiconti3)) {
                    $database->insert('co_pianodeiconti3', [
                        'numero' => $codice_conto3,
                        'descrizione' => $record['descrizione'],
                        'idpianodeiconti2' => $idpianodeiconti2,
                        'dir' => $record['dir'],
                    ]);
                }
            }
        }
        if ($update_record) {
            if (!empty($idpianodeiconti2) && empty($codice_conto3)) {
                $database->update('co_pianodeiconti2', [
                    'descrizione' => $record['descrizione'],
                ], [
                    'id' => $idpianodeiconti2,
                ]);
            } elseif (!empty($idpianodeiconti2) && !empty($codice_conto3)) {
                $idpianodeiconti3 = $database->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE numero='.prepare($codice_conto3).' AND idpianodeiconti2='.prepare($idpianodeiconti2))['id'];

                if (!empty($idpianodeiconti3)) {
                    $database->update('co_pianodeiconti3', [
                        'descrizione' => $record['descrizione'],
                    ], [
                        'id' => $idpianodeiconti3,
                    ]);
                }
            }
        }
    }

    public static function getExample()
    {
        return [
            ['Sezione', 'Conto', 'Descrizione', 'Direzione'],
            ['Economico', '600.000010', 'Costi merci c/acquisto di rivendita', 'uscita'],
            ['Patrimoniale', '110.000010', 'Riepilogativo clienti', ''],
        ];
    }
}
