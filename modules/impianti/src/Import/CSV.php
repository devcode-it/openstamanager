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
use Models\Upload;
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Anagrafiche\Tipo;
use Modules\Impianti\Impianto;
use Modules\Impianti\Categoria;
use Uploads;

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
                'field' => 'cliente',
                'label' => 'Cliente',
            ],
            [
                'field' => 'telefono',
                'label' => 'Telefono',
            ],
            [
                'field' => 'id_categoria',
                'label' => 'Categoria',
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
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        if (!empty($record['telefono'])) {
            $anagrafica = Anagrafica::where('telefono', $record['telefono'])->first();
        }

        if (!empty($anagrafica)) {
            $url = $record['immagine'];
            unset($record['immagine']);
    
            // Gestione categoria e sottocategoria
            $categoria = null;
    
            if (!empty($record['id_categoria'])) {
                // Categoria
                $categoria = Categoria::where('nome', strtolower($record['id_categoria']))->first();
    
                if (empty($categoria)) {
                    $categoria = Categoria::build($record['id_categoria']);
                }
            }
    
            // Individuazione impianto e generazione
            $impianto = null;
            // Ricerca sulla base della chiave primaria se presente
            if (!empty($primary_key)) {
                $impianto = Impianto::where($primary_key, $record[$primary_key])->first();
            }
            if (empty($impianto)) {
                $impianto = Impianto::build($record['matricola'], $record['nome'], $categoria, $record['cliente']);
            } 
    
            if (!empty($record['data'])) {
                $impianto->data = $record['data'];
                $impianto->save();
            }

    
            $tipo = Tipo::where('descrizione', 'Cliente')->first();
            $tipi = $anagrafica->tipi->pluck('idtipoanagrafica')->toArray();

            $tipi[] = $tipo->id;

            $anagrafica->tipologie = $tipi;
            $anagrafica->save();

            $impianto->idanagrafica = $anagrafica->idanagrafica;
            $impianto->save();

            if (!empty($record['sede'])) {
                $sede = Sede::where('nomesede', $record['sede'])
                    ->where('idanagrafica', $anagrafica->idanagrafica)
                    ->first();
                $impianto->idsede = $sede->id;
                $impianto->save(); 
                
            }
    
            
            //Gestione immagine
            if (!empty($url) && !empty($record['import_immagine'])) {
                $file_content = file_get_contents($url);
    
                if (!empty($file_content)) {
                    if ($record['import_immagine'] == 2 || $record['import_immagine'] == 4) {
                        Uploads::deleteLinked([
                            'id_module' => Modules::get('Impianti')['id'],
                            'id_record' => $impianto->id,
                        ]);
    
                        $database->update('mg_articoli', [
                            'immagine' => '',
                        ], [
                            'id' => $impianto->id,
                        ]);
                    }
    
                    $name = 'immagine_'.$impianto->id.'.'.Upload::getExtensionFromMimeType($file_content);
    
                    $upload = Uploads::upload($file_content, [
                        'name' => 'Immagine',
                        'category' => 'Immagini',
                        'original_name' => $name,
                        'id_module' => Modules::get('Impianti')['id'],
                        'id_record' => $impianto->id,
                    ], [
                        'thumbnails' => true,
                    ]);
                    $filename = $upload->filename;
    
                    if ($record['import_immagine'] == 1 || $record['import_immagine'] == 2) {
                        if (!empty($filename)) {
                            $database->update('mg_articoli', [
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
            ['Matricola', 'Nome', 'Categoria', 'Immagine', 'Data installazione', 'Cliente', 'Telefono', 'Sede'],
            ['00001', 'Marca', 'Lavatrice','https://immagini.com/immagine.jpg', '01/10/2023', 'Mario Rossi', '04444444', 'Sede2'],
            ['00002', 'Marca2', 'Lavastoviglie', 'https://immagini.com/immagine2.jpg', '12/09/2023', 'Mario Rossi', '04444444', 'Sede2'],
            ['00003', 'Marca3', 'Frigorifero','https://immagini.com/immagine3.jpg', '20/09/2023', 'Mario Rossi', '04444444', 'Sede2'],
            ['00004', 'Marca4', 'Caldaia', 'https://immagini.com/immagine4.jpg', '06/11/2023', 'Mario Rossi',  '04444444', 'Sede2'],
            [],
            ['Import immagine = 1 -> Permette di importare l\'immagine come principale dell\'impianto mantenendo gli altri allegati già presenti'],
            ['Import immagine = 2 -> Permette di importare l\'immagine come principale dell\'impianto rimuovendo tutti gli allegati presenti'],
            ['Import immagine = 3 -> Permette di importare l\'immagine come allegato dell\'impianto mantenendo gli altri allegati già presenti'],
            ['Import immagine = 4 -> Permette di importare l\'immagine come allegato dell\'impianto rimuovendo tutti gli allegati presenti'],
        ];
    }
}