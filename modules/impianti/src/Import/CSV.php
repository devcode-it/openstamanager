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
use Modules\Articoli\Categoria;
use Modules\Impianti\Impianto;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Impianti.
 *
 * @since 2.4.52
 */
class CSV extends CSVImporter
{
    /**
     * Definisce i campi disponibili per l'importazione.
     *
     * @return array
     */
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'matricola',
                'label' => 'Matricola',
                'primary_key' => true,
                'required' => true,
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
                'required' => true,
            ],
            [
                'field' => 'partita_iva',
                'label' => 'Partita IVA cliente',
                'required' => false, // Almeno uno tra partita IVA e codice fiscale deve essere presente
            ],
            [
                'field' => 'codice_fiscale',
                'label' => 'Codice Fiscale cliente',
                'required' => false, // Almeno uno tra partita IVA e codice fiscale deve essere presente
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

    /**
     * Importa un record nel database.
     *
     * @param array $record Record da importare
     * @param bool $update_record Se true, aggiorna i record esistenti
     * @param bool $add_record Se true, aggiunge nuovi record
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        try {
            $database = database();
            $primary_key = $this->getPrimaryKey();

            // Validazione dei campi obbligatori
            if (empty($record['matricola']) || empty($record['nome'])) {
                return false;
            }

            // Verifica che almeno uno tra partita IVA e codice fiscale sia presente
            if (empty($record['partita_iva']) && empty($record['codice_fiscale'])) {
                return false;
            }

            // Ricerca dell'anagrafica cliente
            $anagrafica = $this->trovaAnagrafica($record);
            if (empty($anagrafica)) {
                return false;
            }

            // Ricerca dell'impianto esistente
            $impianto = $this->trovaImpianto($record, $primary_key);

            // Controllo se creare o aggiornare il record
            if (($impianto && !$update_record) || (!$impianto && !$add_record)) {
                return null;
            }

            // Estrazione URL immagine
            $url = $record['immagine'] ?? '';
            unset($record['immagine']);

            // Gestione categoria e sottocategoria
            $categoria = $this->processaCategoria($record);
            // Processa la sottocategoria (anche se non viene utilizzata direttamente)
            $this->processaSottocategoria($record, $categoria);

            // Gestione marca
            $id_marca = $this->processaMarca($record, $database);

            // Creazione o aggiornamento dell'impianto
            if (empty($impianto)) {
                $impianto = Impianto::build($record['matricola'], $record['nome'], $categoria, $anagrafica->id);
            }

            // Aggiornamento dei campi dell'impianto
            $this->aggiornaImpianto($impianto, $record, $anagrafica, $id_marca);

            // Gestione della sede
            $this->collegaSede($impianto, $record, $anagrafica);

            // Salvataggio dell'impianto
            $impianto->save();

            // Gestione immagine
            $this->processaImmagine($impianto, $url, $record, $database);

            unset($record['import_immagine']);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione dell\'impianto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trova l'anagrafica cliente in base alla partita IVA o al codice fiscale.
     *
     * @param array $record Record da importare
     * @return Anagrafica|null
     */
    protected function trovaAnagrafica($record)
    {
        $anagrafica = null;

        if (!empty($record['partita_iva'])) {
            $anagrafica = Anagrafica::where('piva', '=', $record['partita_iva'])->first();
        }

        if (empty($anagrafica) && !empty($record['codice_fiscale'])) {
            $anagrafica = Anagrafica::where('codice_fiscale', '=', $record['codice_fiscale'])->first();
        }

        return $anagrafica;
    }

    /**
     * Trova l'impianto esistente in base alla chiave primaria.
     *
     * @param array $record Record da importare
     * @param string $primary_key Chiave primaria
     * @return Impianto|null
     */
    protected function trovaImpianto($record, $primary_key)
    {
        if (empty($primary_key) || empty($record[$primary_key])) {
            return null;
        }

        return Impianto::where($primary_key, $record[$primary_key])->first();
    }

    /**
     * Processa la categoria dell'impianto.
     *
     * @param array $record Record da importare
     * @return Categoria|null
     */
    protected function processaCategoria($record)
    {
        if (empty($record['categoria'])) {
            return null;
        }

        $categoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['categoria'])))->where('is_impianto', '=', 1)->first();

        if (empty($categoria)) {
            $categoria = Categoria::build();
            $categoria->setTranslation('title', $record['categoria']);
            $categoria->save();
        }

        return $categoria;
    }

    /**
     * Processa la sottocategoria dell'impianto.
     *
     * @param array $record Record da importare
     * @param Categoria|null $categoria Categoria padre
     * @return Categoria|null
     */
    protected function processaSottocategoria($record, $categoria)
    {
        if (empty($record['sottocategoria']) || empty($categoria)) {
            return null;
        }

        $sottocategoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['sottocategoria'])))->first();

        if (empty($sottocategoria)) {
            $sottocategoria = Categoria::build();
            $sottocategoria->setTranslation('title', $record['sottocategoria']);
            $sottocategoria->parent()->associate($categoria);
            $sottocategoria->save();
        }

        return $sottocategoria;
    }

    /**
     * Processa la marca dell'impianto.
     *
     * @param array $record Record da importare
     * @param object $database Connessione al database
     * @return int|null
     */
    protected function processaMarca($record, $database)
    {
        if (empty($record['marca'])) {
            return null;
        }

        $result = $database->fetchOne('SELECT `id` FROM `my_impianti_marche` WHERE `title`='.prepare($record['marca']));
        $id_marca = !empty($result) ? $result['id'] : null;

        if (empty($id_marca)) {
            $query = 'INSERT INTO `my_impianti_marche` (`title`) VALUES ('.prepare($record['marca']).')';
            $database->query($query);
            $id_marca = $database->lastInsertedID();
        }

        return $id_marca;
    }

    /**
     * Aggiorna i campi dell'impianto.
     *
     * @param Impianto $impianto Impianto da aggiornare
     * @param array $record Record da importare
     * @param Anagrafica $anagrafica Anagrafica cliente
     * @param int|null $id_marca ID della marca
     */
    protected function aggiornaImpianto($impianto, $record, $anagrafica, $id_marca)
    {
        if (!empty($record['data'])) {
            $impianto->data = $record['data'];
        }

        $impianto->nome = $record['nome'];
        $impianto->idanagrafica = $anagrafica->idanagrafica;
        $impianto->id_marca = $id_marca;

        if (!empty($record['modello'])) {
            $impianto->id_modello = $record['modello'];
        }

        if (!empty($record['descrizione'])) {
            $impianto->descrizione = $record['descrizione'];
        }
    }

    /**
     * Collega la sede all'impianto.
     *
     * @param Impianto $impianto Impianto da aggiornare
     * @param array $record Record da importare
     * @param Anagrafica $anagrafica Anagrafica cliente
     */
    protected function collegaSede($impianto, $record, $anagrafica)
    {
        if (empty($record['sede'])) {
            return;
        }

        $sede = Sede::where('nomesede', $record['sede'])
            ->where('idanagrafica', $anagrafica->idanagrafica)
            ->first();

        if (!empty($sede)) {
            $impianto->idsede = $sede->id;
        }
    }

    /**
     * Processa l'immagine dell'impianto.
     *
     * @param Impianto $impianto Impianto da aggiornare
     * @param string $url URL dell'immagine
     * @param array $record Record da importare
     * @param object $database Connessione al database
     */
    protected function processaImmagine($impianto, $url, $record, $database)
    {
        try {
            if (empty($url) || empty($record['import_immagine'])) {
                return;
            }

            $file_content = file_get_contents($url);

            if (empty($file_content)) {
                return;
            }

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

            if ($upload && !empty($upload->filename) && ($record['import_immagine'] == 1 || $record['import_immagine'] == 2)) {
                $database->update('my_impianti', [
                    'immagine' => $upload->filename,
                ], [
                    'id' => $impianto->id,
                ]);
            }
        } catch (\Exception $e) {
            // Registra l'errore ma continua con l'importazione
            error_log('Errore durante l\'importazione dell\'immagine: ' . $e->getMessage());
        }
    }

    /**
     * Restituisce un esempio di file CSV per l'importazione.
     *
     * @return array
     */
    public static function getExample()
    {
        return [
            ['Matricola', 'Immagine', 'Import immagine', 'Nome', 'Partita IVA Cliente', 'Codice Fiscale Cliente', 'Categoria', 'Sottocategoria', 'Sede', 'Descrizione', 'Data installazione', 'Marca', 'Modello'],
            ['00001', 'https://openstamanager.com/moduli/budget/budget.webp', '2', 'Lavatrice Samsung', '12345678901', '', 'Elettrodomestici', 'Lavatrici', 'Sede Principale', 'Lavatrice a carica frontale 8kg', '2023-01-01', 'Samsung', 'WW80TA046AX'],
            ['00002', 'https://openstamanager.com/moduli/3cx/3cx.webp', '2', 'Caldaia Ariston', '', 'RSSMRA80A01H501U', 'Riscaldamento', 'Caldaie', 'Sede Secondaria', 'Caldaia a condensazione 24kW', '2023-03-06', 'Ariston', 'Genus One Net'],
            ['00003', 'https://openstamanager.com/moduli/disponibilita-tecnici/tecnici.webp', '2', 'Forno Electrolux', '98765432109', '', 'Elettrodomestici', 'Forni', 'Sede Principale', 'Forno elettrico multifunzione', '2023-04-01', 'Electrolux', 'EOC6P77WX'],
            ['00004', 'https://openstamanager.com/moduli/distinta-base/distinta.webp', '2', 'Lavastoviglie Bosch', '12345678901', '', 'Elettrodomestici', 'Lavastoviglie', 'Sede Principale', 'Lavastoviglie da incasso 60cm', '2023-08-06', 'Bosch', 'SMV4HCX48E'],
            ['00005', '', '', 'Condizionatore Daikin', '', 'VRDLGI75M15F205Z', 'Climatizzazione', 'Split', 'Sede Principale', 'Condizionatore inverter 12000 BTU', '2023-05-15', 'Daikin', 'FTXM35R'],
        ];
    }
}
