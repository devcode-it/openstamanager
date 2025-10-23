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
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Articoli\Categoria;
use Modules\Articoli\Marca;
use Modules\Impianti\Impianto;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Impianti.
 *
 * @since 2.4.52
 */
class CSV extends CSVImporter
{
    /**
     * Array per memorizzare gli errori specifici per i record falliti.
     */
    protected $failed_errors = [];

    /**
     * Definisce i campi disponibili per l'importazione.
     *
     * @return array
     */
    public function getAvailableFields()
    {
        return [
            ['field' => 'matricola', 'label' => 'Matricola', 'primary_key' => true, 'required' => true],
            ['field' => 'immagine', 'label' => 'Immagine', 'names' => ['Immagine', 'Foto']],
            ['field' => 'import_immagine', 'label' => 'Import immagine'],
            ['field' => 'nome', 'label' => 'Nome', 'required' => true],
            ['field' => 'partita_iva', 'label' => 'Partita IVA cliente', 'required' => false],
            ['field' => 'codice_fiscale', 'label' => 'Codice Fiscale cliente', 'required' => false],
            ['field' => 'categoria', 'label' => 'Categoria'],
            ['field' => 'sottocategoria', 'label' => 'Sottocategoria'],
            ['field' => 'sede', 'label' => 'Sede'],
            ['field' => 'descrizione', 'label' => 'Descrizione'],
            ['field' => 'data', 'label' => 'Data installazione'],
            ['field' => 'marca', 'label' => 'Marca'],
            ['field' => 'modello', 'label' => 'Modello'],
        ];
    }

    /**
     * Importa le righe specificate dal file CSV.
     *
     * @param int  $offset        Offset di partenza
     * @param int  $length        Numero di righe da importare
     * @param bool $update_record Se true, aggiorna i record esistenti
     * @param bool $add_record    Se true, aggiunge nuovi record
     *
     * @return array Statistiche dell'importazione
     */
    public function importRows($offset, $length, $update_record = true, $add_record = true)
    {
        $rows = $this->getRows($offset, $length);
        $imported_count = 0;
        $failed_count = 0;

        foreach ($rows as $row) {
            $record = $this->getRecord($row);

            // Validazione campi obbligatori
            $missing_fields = [];
            foreach ($this->getAvailableFields() as $field) {
                if (isset($field['required']) && $field['required'] && array_key_exists($field['field'], $record)) {
                    if (trim((string) $record[$field['field']]) === '') {
                        $missing_fields[] = $field['field'];
                    }
                }
            }

            // Almeno uno tra P.IVA e C.F. deve essere presente
            if (empty($record['partita_iva']) && empty($record['codice_fiscale'])) {
                $missing_fields[] = 'partita_iva/codice_fiscale';
            }

            if (!empty($missing_fields)) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                $this->failed_errors[] = 'Campi obbligatori mancanti: '.implode(', ', $missing_fields);
                ++$failed_count;
                continue;
            }

            $result = $this->import($record, $update_record, $add_record);

            if ($result === false) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                ++$failed_count;
            } else {
                ++$imported_count;
            }
        }

        return ['imported' => $imported_count, 'failed' => $failed_count, 'total' => count($rows)];
    }

    /**
     * Importa un record nel database.
     *
     * @param array $record        Record da importare
     * @param bool  $update_record Se true, aggiorna i record esistenti
     * @param bool  $add_record    Se true, aggiunge nuovi record
     *
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        try {
            $database = database();
            $primary_key = $this->getPrimaryKey();

            if (empty($record['matricola']) || empty($record['nome'])) {
                $this->failed_errors[] = 'Campi obbligatori mancanti: matricola e/o nome';

                return false;
            }

            if (empty($record['partita_iva']) && empty($record['codice_fiscale'])) {
                $this->failed_errors[] = 'Almeno uno tra Partita IVA e Codice Fiscale deve essere presente';

                return false;
            }

            $anagrafica = $this->trovaAnagrafica($record);
            if (empty($anagrafica)) {
                $this->failed_errors[] = 'Impossibile trovare o creare anagrafica cliente';

                return false;
            }

            $impianto = $this->trovaImpianto($record, $primary_key);

            if (($impianto && !$update_record) || (!$impianto && !$add_record)) {
                return null;
            }

            $url = $record['immagine'] ?? '';
            unset($record['immagine']);

            $categoria = $this->processaCategoria($record);
            $this->processaSottocategoria($record, $categoria);
            $id_marca = $this->processaMarca($record, $database);

            if (empty($impianto)) {
                $impianto = Impianto::build($record['matricola'], $record['nome'], $categoria, $anagrafica->id);
            }

            $this->aggiornaImpianto($impianto, $record, $anagrafica, $id_marca);
            $this->collegaSede($impianto, $record, $anagrafica);
            $impianto->save();
            $this->processaImmagine($impianto, $url, $record, $database);

            unset($record['import_immagine']);

            return true;
        } catch (\Exception $e) {
            $error = 'Errore importazione impianto';
            if (!empty($record['matricola'])) {
                $error .= ' (Matricola: '.$record['matricola'].')';
            }
            error_log($error.': '.$e->getMessage());
            $this->failed_errors[] = $e->getMessage();

            return false;
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
            ['00004', 'https://openstamanager.com/moduli/climatizzazione/climatizzazione.webp', '2', 'Condizionatore Daikin', '', 'VRDLGI75M15F205Z', 'Climatizzazione', 'Split', 'Sede Principale', 'Condizionatore inverter 12000 BTU', '2023-05-15', 'Daikin', 'FTXM35R'],
        ];
    }

    /**
     * Salva i record falliti con gli errori specifici in un file CSV.
     *
     * @param string $filepath Percorso del file in cui salvare i record falliti
     *
     * @return string Percorso del file salvato
     */
    public function saveFailedRecordsWithErrors($filepath)
    {
        if (empty($this->failed_rows)) {
            return '';
        }

        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($filepath, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        $header = $this->getHeader();
        $header[] = 'Errore';
        fputcsv($file, $header, ';');

        foreach ($this->failed_rows as $index => $row) {
            $error_message = $this->failed_errors[$index] ?? 'Errore sconosciuto';
            $row[] = $error_message;
            fputcsv($file, $row, ';');
        }

        fclose($file);

        return $filepath;
    }

    /**
     * Restituisce gli errori specifici per i record falliti.
     *
     * @return array
     */
    public function getFailedErrors()
    {
        return $this->failed_errors;
    }

    /**
     * Trova l'anagrafica cliente in base alla partita IVA o al codice fiscale.
     * Se non trova nessuna corrispondenza, crea una nuova anagrafica.
     *
     * @param array $record Record da importare
     *
     * @return Anagrafica|null Anagrafica trovata o creata, null in caso di errore
     */
    protected function trovaAnagrafica($record)
    {
        $anagrafica = null;

        if (!empty($record['partita_iva'])) {
            $anagrafica = Anagrafica::where('piva', $record['partita_iva'])->first();
        }

        if (empty($anagrafica) && !empty($record['codice_fiscale'])) {
            $anagrafica = Anagrafica::where('codice_fiscale', $record['codice_fiscale'])->first();
        }

        if (empty($anagrafica)) {
            $anagrafica = $this->creaAnagrafica($record);
        }

        return $anagrafica;
    }

    /**
     * Crea una nuova anagrafica in base ai dati del record.
     *
     * @param array $record Record da processare
     *
     * @return Anagrafica|null Anagrafica creata o null in caso di errore
     */
    protected function creaAnagrafica($record)
    {
        try {
            $ragione_sociale = '';
            if (!empty($record['partita_iva'])) {
                $ragione_sociale = 'Cliente P.IVA '.$record['partita_iva'];
            } elseif (!empty($record['codice_fiscale'])) {
                $ragione_sociale = 'Cliente C.F. '.$record['codice_fiscale'];
            } else {
                $ragione_sociale = 'Cliente importato '.date('Y-m-d H:i:s');
            }

            if (empty($ragione_sociale)) {
                return null;
            }

            $tipo_cliente = TipoAnagrafica::where('name', 'Cliente')->first();
            $tipologie = !empty($tipo_cliente) ? [$tipo_cliente->id] : [];
            $anagrafica = Anagrafica::build($ragione_sociale, '', '', $tipologie);

            if (!empty($record['partita_iva'])) {
                $anagrafica->piva = $record['partita_iva'];
            }

            if (!empty($record['codice_fiscale'])) {
                $anagrafica->codice_fiscale = $record['codice_fiscale'];
            }

            if (empty($anagrafica->telefono) && empty($anagrafica->piva)) {
                $anagrafica->telefono = '000000000';
            }

            $anagrafica->save();

            return $anagrafica;
        } catch (\Exception $e) {
            error_log('Errore creazione anagrafica: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Trova l'impianto esistente in base alla chiave primaria.
     *
     * @param array  $record      Record da importare
     * @param string $primary_key Chiave primaria
     *
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
     *
     * @return Categoria|null
     */
    protected function processaCategoria($record)
    {
        if (empty($record['categoria'])) {
            return null;
        }

        try {
            $categoria_id = (new Categoria())->getByField('title', $record['categoria']);
            $categoria = $categoria_id ? Categoria::where('id', $categoria_id)->where('is_impianto', 1)->first() : null;

            if (empty($categoria)) {
                $categoria = Categoria::where('name', $record['categoria'])
                    ->where('is_impianto', 1)
                    ->where('parent', null)
                    ->first();
            }

            if (empty($categoria)) {
                $categoria = Categoria::build(null, $record['categoria']);
                $categoria->is_impianto = 1;
                $categoria->setTranslation('title', $record['categoria']);
                $categoria->save();
            }

            return $categoria;
        } catch (\Exception $e) {
            throw new \Exception('Errore categoria "'.$record['categoria'].'": '.$e->getMessage());
        }
    }

    /**
     * Processa la sottocategoria dell'impianto.
     *
     * @param array          $record    Record da importare
     * @param Categoria|null $categoria Categoria padre
     *
     * @return Categoria|null
     */
    protected function processaSottocategoria($record, $categoria)
    {
        if (empty($record['sottocategoria']) || empty($categoria)) {
            return null;
        }

        try {
            $sottocategoria_id = (new Categoria())->getByField('title', $record['sottocategoria']);
            $sottocategoria = $sottocategoria_id ? Categoria::where('id', $sottocategoria_id)->where('parent', $categoria->id)->first() : null;

            if (empty($sottocategoria)) {
                $sottocategoria = Categoria::where('name', $record['sottocategoria'])
                    ->where('parent', $categoria->id)
                    ->first();
            }

            if (empty($sottocategoria)) {
                $sottocategoria = Categoria::build($categoria, $record['sottocategoria']);
                $sottocategoria->is_impianto = 1;
                $sottocategoria->setTranslation('title', $record['sottocategoria']);
                $sottocategoria->parent()->associate($categoria);
                $sottocategoria->save();
            }

            return $sottocategoria;
        } catch (\Exception $e) {
            error_log('Errore sottocategoria "'.$record['sottocategoria'].'": '.$e->getMessage());

            return null;
        }
    }

    /**
     * Processa la marca dell'impianto.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return int|null
     */
    protected function processaMarca($record, $database)
    {
        if (empty($record['marca'])) {
            return null;
        }

        try {
            $marca = Marca::where('name', $record['marca'])->where('is_impianto', 1)->first();

            if (empty($marca)) {
                $marca = Marca::build($record['marca']);
                $marca->is_impianto = 1;
                $marca->save();
            }

            return $marca->id;
        } catch (\Exception $e) {
            error_log('Errore marca, uso fallback: '.$e->getMessage());

            $result = $database->fetchOne('SELECT `id` FROM `zz_marche` WHERE `name`='.prepare($record['marca']).' AND `is_impianto` = 1');
            $id_marca = !empty($result) ? $result['id'] : null;

            if (empty($id_marca)) {
                $database->query('INSERT INTO `zz_marche` (`name`, `is_impianto`) VALUES ('.prepare($record['marca']).', 1)');
                $id_marca = $database->lastInsertedID();
            }

            return $id_marca;
        }
    }

    /**
     * Aggiorna i campi dell'impianto.
     *
     * @param Impianto   $impianto   Impianto da aggiornare
     * @param array      $record     Record da importare
     * @param Anagrafica $anagrafica Anagrafica cliente
     * @param int|null   $id_marca   ID della marca
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
     * @param Impianto   $impianto   Impianto da aggiornare
     * @param array      $record     Record da importare
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
     * @param string   $url      URL dell'immagine
     * @param array    $record   Record da importare
     * @param object   $database Connessione al database
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
                $database->update('my_impianti', ['immagine' => ''], ['id' => $impianto->id]);
            }

            $name = 'immagine_'.$impianto->id.'.'.Upload::getExtensionFromMimeType($file_content);

            $upload = \Uploads::upload($file_content, [
                'name' => 'Immagine',
                'category' => 'Immagini',
                'original_name' => $name,
                'id_module' => Module::find('Impianti')->id,
                'id_record' => $impianto->id,
            ], ['thumbnails' => true]);

            if ($upload && !empty($upload->filename) && ($record['import_immagine'] == 1 || $record['import_immagine'] == 2)) {
                $database->update('my_impianti', ['immagine' => $upload->filename], ['id' => $impianto->id]);
            }
        } catch (\Exception $e) {
            error_log('Errore importazione immagine: '.$e->getMessage());
        }
    }
}
