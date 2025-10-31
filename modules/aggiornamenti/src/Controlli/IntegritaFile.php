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

namespace Modules\Aggiornamenti\Controlli;

use Models\Upload;
use Util\FileSystem;

class IntegritaFile extends Controllo
{
    public function getName()
    {
        return tr('Integrità file allegati');
    }

    public function getType($record)
    {
        return $record['tipo'] === 'file_orfano' ? 'warning' : 'danger';
    }

    public function getOptions($record)
    {
        $options = [];

        if ($record['tipo'] === 'file_orfano') {
            $options[] = [
                'name' => tr('Rimuovi file'),
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'params' => ['action' => 'remove_orphan_file'],
            ];
        } elseif ($record['tipo'] === 'file_mancante') {
            $options[] = [
                'name' => tr('Rimuovi record'),
                'icon' => 'fa fa-database',
                'color' => 'warning',
                'params' => ['action' => 'remove_orphan_record'],
            ];
        }

        return $options;
    }

    /**
     * Indica se questo controllo supporta azioni globali.
     */
    public function hasGlobalActions()
    {
        return true;
    }

    /**
     * Restituisce le azioni globali disponibili per questo controllo.
     */
    public function getGlobalActions()
    {
        $orphan_files_stats = $this->getOrphanFilesStats();
        $orphan_records_stats = $this->getOrphanRecordsStats();

        return [
            [
                'name' => tr('Rimuovi tutti i file orfani'),
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'params' => ['action' => 'remove_all_orphan_files'],
                'badge' => $orphan_files_stats['count'] > 0 ? $orphan_files_stats['count'].' file - '.$orphan_files_stats['size'] : null,
            ],
            [
                'name' => tr('Rimuovi tutti i record orfani'),
                'icon' => 'fa fa-database',
                'color' => 'warning',
                'params' => ['action' => 'remove_all_orphan_records'],
                'badge' => $orphan_records_stats['count'] > 0 ? $orphan_records_stats['count'].' record - '.$orphan_records_stats['size'] : null,
            ],
        ];
    }

    public function check()
    {
        // 1. Controllo file orfani nel filesystem (presenti in /files/ ma non in zz_files)
        $this->checkOrphanFiles();

        // 2. Controllo record orfani nel database (presenti in zz_files ma file fisico mancante)
        $this->checkMissingFiles();
    }

    /**
     * Controlla i file orfani nel filesystem
     */
    protected function checkOrphanFiles()
    {
        $files_dir = base_dir().'/files';

        // Escludo alcune cartelle di sistema
        $excluded_dirs = ['temp', 'importFE', 'exportFE', 'receiptFE', 'impianti', 'backup', 'backups'];

        // Aggiungo dinamicamente la cartella di backup configurata
        try {
            $backup_dir = \Backup::getDirectory();
            $files_base = base_dir().'/files';

            // Se la cartella di backup è dentro /files/, la escludo
            if (string_starts_with($backup_dir, $files_base)) {
                $relative_backup_dir = str_replace($files_base.'/', '', $backup_dir);
                $backup_folder_name = explode('/', $relative_backup_dir)[0];
                if (!in_array($backup_folder_name, $excluded_dirs)) {
                    $excluded_dirs[] = $backup_folder_name;
                }
            }
        } catch (\Exception $e) {
            // Se non riesco a ottenere la cartella di backup, continuo senza errori
        }

        // Ottengo tutti i file registrati nel database usando il modello Upload
        $uploads = Upload::all();
        $registered_files = [];

        foreach ($uploads as $upload) {
            // Uso il metodo attachments_directory per ottenere il percorso corretto
            $directory = $upload->attachments_directory;
            if (!empty($directory)) {
                $registered_files[] = $directory.'/'.$upload->filename;
            } else {
                // File nella root della cartella files (caso raro)
                $registered_files[] = $upload->filename;
            }
        }

        // Scansiono ricorsivamente la cartella files
        $this->scanDirectory($files_dir, $registered_files, $excluded_dirs);
    }

    /**
     * Scansiona ricorsivamente una directory per trovare file orfani
     */
    protected function scanDirectory($dir, $registered_files, $excluded_dirs, $base_path = '')
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full_path = $dir.'/'.$item;
            $relative_path = $base_path ? $base_path.'/'.$item : $item;

            if (is_dir($full_path)) {
                // Escludo le cartelle di sistema
                if (!in_array($item, $excluded_dirs)) {
                    $this->scanDirectory($full_path, $registered_files, $excluded_dirs, $relative_path);
                }
            } else {
                // Escludo i file di sistema
                if (!in_array($item, ['.htaccess', '.gitkeep', 'index.html'])) {
                    // Controllo se il file è registrato nel database
                    if (!in_array($relative_path, $registered_files)) {
                        $file_size = filesize($full_path);
                        $this->addResult([
                            'id' => 'orphan_file_'.md5($relative_path),
                            'tipo' => 'file_orfano',
                            'nome' => '<strong>'.$item.'</strong><br><small class="text-muted">'.$relative_path.'</small>',
                            'nome_file' => $item,
                            'percorso_completo' => $relative_path,
                            'dimensione' => FileSystem::formatBytes($file_size),
                            'dimensione_bytes' => $file_size,
                            'descrizione' => tr('File orfano nel filesystem (_SIZE_)', ['_SIZE_' => '<span class="badge badge-info">'.FileSystem::formatBytes($file_size).'</span>']).'<br><small class="text-muted">'.tr('File presente nel filesystem ma non registrato nel database zz_files').'</small>',
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Controlla i record orfani nel database
     */
    protected function checkMissingFiles()
    {
        // Ottengo tutti i record dal database usando il modello Upload
        $uploads = Upload::all();

        foreach ($uploads as $upload) {
            // Costruisco il percorso del file usando i metodi del modello
            $file_path = base_dir().'/files/'.$upload->attachments_directory.'/'.$upload->filename;

            // Controllo se il file fisico esiste
            if (!file_exists($file_path)) {
                $module_name = '';
                if ($upload->module) {
                    $module_name = $upload->module->getTranslation('title');
                } elseif ($upload->plugin) {
                    $module_name = $upload->plugin->getTranslation('title');
                } else {
                    $module_name = 'Modulo sconosciuto';
                }

                $this->addResult([
                    'id' => 'missing_file_'.$upload->id,
                    'tipo' => 'file_mancante',
                    'nome' => '<strong>'.($upload->name ?: $upload->filename).'</strong><br><small class="text-muted">'.$module_name.' (Record: '.$upload->id_record.')</small>',
                    'nome_file' => $upload->name ?: $upload->filename,
                    'filename' => $upload->filename,
                    'modulo' => $module_name,
                    'record_id' => $upload->id_record,
                    'dimensione' => FileSystem::formatBytes($upload->size),
                    'dimensione_bytes' => $upload->size,
                    'percorso_atteso' => $upload->attachments_directory.'/'.$upload->filename,
                    'descrizione' => tr('Record orfano nel database (_SIZE_)', ['_SIZE_' => '<span class="badge badge-warning">'.FileSystem::formatBytes($upload->size).'</span>']).'<br><small class="text-muted">'.tr('Record presente nel database zz_files (ID: _ID_) ma file fisico mancante in _PATH_', [
                        '_ID_' => $upload->id,
                        '_PATH_' => $upload->attachments_directory.'/'.$upload->filename
                    ]).'</small>',
                ]);
            }
        }
    }

    public function execute($record, $params = [])
    {
        $action = $params['action'] ?? '';

        if ($action === 'remove_orphan_file' && $record['tipo'] === 'file_orfano') {
            $file_path = base_dir().'/files/'.$record['percorso_completo'];

            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    return tr('File _FILE_ rimosso con successo', ['_FILE_' => $record['nome_file']]);
                } else {
                    return tr('Errore nella rimozione del file _FILE_', ['_FILE_' => $record['nome_file']]);
                }
            } else {
                return tr('File _FILE_ non trovato', ['_FILE_' => $record['nome_file']]);
            }
        } elseif ($action === 'remove_orphan_record' && $record['tipo'] === 'file_mancante') {
            // Estraggo l'ID del record dal campo id (formato: missing_file_123)
            $upload_id = str_replace('missing_file_', '', $record['id']);

            try {
                $upload = Upload::find($upload_id);
                if ($upload) {
                    $upload->delete();
                    return tr('Record _FILE_ rimosso dal database con successo', ['_FILE_' => $record['nome_file']]);
                } else {
                    return tr('Record _FILE_ non trovato nel database', ['_FILE_' => $record['nome_file']]);
                }
            } catch (\Exception $e) {
                return tr('Errore nella rimozione del record _FILE_: _ERROR_', [
                    '_FILE_' => $record['nome_file'],
                    '_ERROR_' => $e->getMessage()
                ]);
            }
        }

        return tr('Azione non supportata per questo tipo di record');
    }

    /**
     * Risolve tutti i problemi di integrità
     */
    public function solveGlobal($params = [])
    {
        $action = $params['action'] ?? '';
        $results = [];

        if ($action === 'remove_all_orphan_files') {
            foreach ($this->results as $record) {
                if ($record['tipo'] === 'file_orfano') {
                    $file_path = base_dir().'/files/'.$record['percorso_completo'];

                    $results[$record['id']] = file_exists($file_path)
                        ? (unlink($file_path) ? tr('File _FILE_ rimosso con successo', ['_FILE_' => $record['nome_file']]) : tr('Errore nella rimozione del file _FILE_', ['_FILE_' => $record['nome_file']]))
                        : tr('File _FILE_ non trovato', ['_FILE_' => $record['nome_file']]);
                }
            }
        } elseif ($action === 'remove_all_orphan_records') {
            foreach ($this->results as $record) {
                if ($record['tipo'] === 'file_mancante') {
                    // Estraggo l'ID del record dal campo id (formato: missing_file_123)
                    $upload_id = str_replace('missing_file_', '', $record['id']);

                    try {
                        $upload = Upload::find($upload_id);
                        if ($upload) {
                            $upload->delete();
                            $results[$record['id']] = tr('Record _FILE_ rimosso dal database con successo', ['_FILE_' => $record['nome_file']]);
                        } else {
                            $results[$record['id']] = tr('Record _FILE_ non trovato nel database', ['_FILE_' => $record['nome_file']]);
                        }
                    } catch (\Exception $e) {
                        $results[$record['id']] = tr('Errore nella rimozione del record _FILE_: _ERROR_', [
                            '_FILE_' => $record['nome_file'],
                            '_ERROR_' => $e->getMessage()
                        ]);
                    }
                }
            }
        } elseif ($action === 'remove_all_both') {
            // Esegui entrambe le operazioni a blocchi di 100 per evitare timeout

            // Separa i record per tipo
            $orphan_files = [];
            $orphan_records = [];

            foreach ($this->results as $record) {
                if ($record['tipo'] === 'file_orfano') {
                    $orphan_files[] = $record;
                } elseif ($record['tipo'] === 'file_mancante') {
                    $orphan_records[] = $record;
                }
            }

            $batch_size = 100;
            $processed_files = 0;
            $processed_records = 0;

            // 1. Rimuovi file orfani a blocchi
            $file_batches = array_chunk($orphan_files, $batch_size);
            foreach ($file_batches as $batch) {
                foreach ($batch as $record) {
                    $file_path = base_dir().'/files/'.$record['percorso_completo'];

                    if (file_exists($file_path)) {
                        if (unlink($file_path)) {
                            $processed_files++;
                            $results[$record['id']] = tr('File _FILE_ rimosso con successo', ['_FILE_' => $record['nome_file']]);
                        } else {
                            $results[$record['id']] = tr('Errore nella rimozione del file _FILE_', ['_FILE_' => $record['nome_file']]);
                        }
                    } else {
                        $results[$record['id']] = tr('File _FILE_ non trovato', ['_FILE_' => $record['nome_file']]);
                    }
                }

                // Pausa breve tra i blocchi per evitare sovraccarico
                if (count($file_batches) > 1) {
                    usleep(100000); // 0.1 secondi
                }
            }

            // 2. Rimuovi record orfani a blocchi
            $record_batches = array_chunk($orphan_records, $batch_size);
            foreach ($record_batches as $batch) {
                foreach ($batch as $record) {
                    $upload_id = str_replace('missing_file_', '', $record['id']);

                    try {
                        $upload = Upload::find($upload_id);
                        if ($upload) {
                            $upload->delete();
                            $processed_records++;
                            $results[$record['id']] = tr('Record _FILE_ rimosso dal database con successo', ['_FILE_' => $record['nome_file']]);
                        } else {
                            $results[$record['id']] = tr('Record _FILE_ non trovato nel database', ['_FILE_' => $record['nome_file']]);
                        }
                    } catch (\Exception $e) {
                        $results[$record['id']] = tr('Errore nella rimozione del record _FILE_: _ERROR_', [
                            '_FILE_' => $record['nome_file'],
                            '_ERROR_' => $e->getMessage()
                        ]);
                    }
                }

                // Pausa breve tra i blocchi per evitare sovraccarico del database
                if (count($record_batches) > 1) {
                    usleep(100000); // 0.1 secondi
                }
            }

            // Aggiungi un messaggio di riepilogo
            if ($processed_files > 0 || $processed_records > 0) {
                $results['summary'] = tr('Operazione completata: _FILES_ file e _RECORDS_ record rimossi con successo', [
                    '_FILES_' => $processed_files,
                    '_RECORDS_' => $processed_records
                ]);
            }
        }

        return $results;
    }

    /**
     * Calcola le statistiche dei file orfani
     */
    protected function getOrphanFilesStats()
    {
        $count = 0;
        $total_size = 0;

        foreach ($this->results as $record) {
            if ($record['tipo'] === 'file_orfano') {
                $count++;
                // Calcolo la dimensione del file fisico
                $file_path = base_dir().'/files/'.$record['percorso_completo'];
                if (file_exists($file_path)) {
                    $total_size += filesize($file_path);
                }
            }
        }

        return [
            'count' => $count,
            'size' => FileSystem::formatBytes($total_size)
        ];
    }

    /**
     * Calcola le statistiche dei record orfani
     */
    protected function getOrphanRecordsStats()
    {
        $count = 0;
        $total_size = 0;

        foreach ($this->results as $record) {
            if ($record['tipo'] === 'file_mancante') {
                $count++;
                // Estraggo l'ID del record dal campo id (formato: missing_file_123)
                $upload_id = str_replace('missing_file_', '', $record['id']);
                $upload = Upload::find($upload_id);
                if ($upload) {
                    $total_size += $upload->size;
                }
            }
        }

        return [
            'count' => $count,
            'size' => FileSystem::formatBytes($total_size)
        ];
    }
}
