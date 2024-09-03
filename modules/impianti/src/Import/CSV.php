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

namespace Modules\Impianti\Import;

use Importer\CSVImporter;
use Models\Module;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Impianti\Categoria;
use Modules\Impianti\Impianto;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Impianti.
 *
 * @since 2.4.52
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'matricola',
                'label' => 'Matricola',
                'primary_key' => true,
            ],
            [
                'field' => 'immagine',
                'label' => 'Immagine',
                'names' => [
                    'Immagine',
                    'Foto',
                ],
            ],
            [
                'field' => 'import_immagine',
                'label' => 'Import immagine',
            ],
            [
                'field' => 'nome',
                'label' => 'Nome',
            ],
            [
                'field' => 'partita_iva',
                'label' => 'Partita IVA cliente',
            ],
            [
                'field' => 'codice_fiscale',
                'label' => 'Codice Fiscale cliente',
            ],
            [
                'field' => 'categoria',
                'label' => 'Categoria',
            ],
            [
                'field' => 'sottocategoria',
                'label' => 'Sottocategoria',
            ],
            [
                'field' => 'sede',
                'label' => 'Sede',
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'data',
                'label' => 'Data installazione',
            ],
            [
                'field' => 'marca',
                'label' => 'Marca',
            ],
            [
                'field' => 'modello',
                'label' => 'Modello',
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        if (!empty($record['partita_iva'])) {
            $anagrafica = Anagrafica::where('piva', '=', $record['partita_iva'])->first();
        } elseif (!empty($record['codice_fiscale'])) {
            $anagrafica = Anagrafica::where('codice_fiscale', '=', $record['codice_fiscale'])->first();
        }

        if (!empty($anagrafica)) {
            $url = $record['immagine'];
            unset($record['immagine']);

            // Gestione categoria e sottocategoria
            $categoria = null;
            $sottocategoria = null;
            if (!empty($record['categoria'])) {
                // Categoria
                $categoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['categoria'])))->first();

                if (empty($categoria)) {
                    $categoria = Categoria::build();
                    $categoria->setTranslation('title', $record['categoria']);
                    $categoria->save();
                }

                // Sotto-categoria
                if (!empty($record['sottocategoria'])) {
                    $sottocategoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['sottocategoria'])))->first();
                    if (empty($sottocategoria)) {
                        $sottocategoria = Categoria::build();
                        $sottocategoria->setTranslation('title', $record['sottocategoria']);
                        $sottocategoria->parent()->associate($categoria);
                        $sottocategoria->save();
                    }
                }
            }

            // Gestione marca
            $id_marca = null;
            if (!empty($record['marca'])) {
                // Marca
                $n = $database->fetchOne('SELECT `id` FROM `my_impianti_marche` WHERE `title`='.prepare($record['marca']))['id'];

                if (empty($n)) {
                    $query = 'INSERT INTO `my_impianti_marche` (`title`) VALUES ('.prepare($record['marca']).')';
                    $database->query($query);
                    $id_marca = $database->lastInsertedID();
                } else {
                    $id_marca = $n;
                }
            }

            // Individuazione impianto e generazione
            $impianto = null;

            // Ricerca sulla base della chiave primaria se presente
            if (!empty($primary_key)) {
                $impianto = Impianto::where($primary_key, $record[$primary_key])->first();
                $impianto->nome = $record['nome'];
            }
            if (empty($impianto)) {
                $impianto = Impianto::build($record['matricola'], $record['nome'], $categoria, $record['cliente']);
            }

            if (!empty($record['data'])) {
                $impianto->data = $record['data'];
            }

            $impianto->idanagrafica = $anagrafica->idanagrafica;
            $impianto->id_marca = $id_marca;
            $impianto->id_modello = $record['modello'];

            if (!empty($record['sede'])) {
                $sede = Sede::where('nomesede', $record['sede'])
                    ->where('idanagrafica', $anagrafica->idanagrafica)
                    ->first();
                $impianto->idsede = $sede->id;
            }

            $impianto->descrizione = $record['descrizione'];
            $impianto->save();

            // Gestione immagine
            if (!empty($url) && !empty($record['import_immagine'])) {
                $file_content = file_get_contents($url);

                if (!empty($file_content)) {
                    if ($record['import_immagine'] == 2 || $record['import_immagine'] == 4) {
                        \Uploads::deleteLinked([
                            'id_module' => Module::find('Impianti')->id,
                            'id_record' => $impianto->id,
                        ]);

                        $database->update('my_impianti', [
                            'immagine' => '',
                        ], [
                            'id' => $impianto->id,
                        ]);
                    }

                    $name = 'immagine_'.$impianto->id.'.'.Upload::getExtensionFromMimeType($file_content);

                    $upload = \Uploads::upload($file_content, [
                        'name' => 'Immagine',
                        'category' => 'Immagini',
                        'original_name' => $name,
                        'id_module' => Module::find('Impianti')->id,
                        'id_record' => $impianto->id,
                    ], [
                        'thumbnails' => true,
                    ]);
                    $filename = $upload->filename;

                    if ($record['import_immagine'] == 1 || $record['import_immagine'] == 2) {
                        if (!empty($filename)) {
                            $database->update('my_impianti', [
                                'immagine' => $filename,
                            ], [
                                'id' => $impianto->id,
                            ]);
                        }
                    }
                }
            }

            unset($record['import_immagine']);
        }
    }

    public static function getExample()
    {
        return [
            ['Matricola', 'Immagine', 'Import immagine', 'Nome', 'Partita IVA Cliente', 'Codice Fiscale Cliente', 'Categoria', 'Sottocategoria', 'Sede', 'Descrizione', 'Data installazione'],
            ['00001', 'https://openstamanager.com/moduli/budget/budget.webp', '2', 'Lavatrice', '123456789', '123456789', 'Elettrodomestici', 'Marca1', '', '', '2023-01-01'],
            ['00002', 'https://openstamanager.com/moduli/3cx/3cx.webp', '2', 'Caldaia', '123456789', '123456789', 'Elettrodomestici', 'Marca2', '', '', '2023-03-06'],
            ['00003', 'https://openstamanager.com/moduli/disponibilita-tecnici/tecnici.webp', '2', 'Forno', '123456789', '123456789', 'Elettrodomestici', 'Marca3', '', '', '2023-04-01'],
            ['00004', 'https://openstamanager.com/moduli/distinta-base/distinta.webp', '2', 'Lavastoviglie', '123456789', '123456789', 'Elettrodomestici', 'Marca4', '', '', '2023-08-06'],
        ];
    }
}
