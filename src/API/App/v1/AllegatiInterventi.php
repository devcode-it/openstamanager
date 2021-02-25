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

use API\App\AppResource;
use API\Exceptions\InternalError;
use Models\Upload;
use Modules;

class AllegatiInterventi extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getCleanupData($last_sync_at);

        // Elenco allegati degli interventi da rimuovere
        $da_interventi = [];
        if (!empty($interventi)) {
            $query = 'SELECT zz_files.id FROM zz_files WHERE id_module = (SELECT `id` FROM `zz_modules` WHERE `name` = "Interventi") AND id_record IN ('.implode(',', $interventi).')';
            $allegati_interventi = database()->fetchArray($query);
            $da_interventi = array_column($allegati_interventi, 'id');
        }

        // Allegati rimossi manualmente
        $mancanti = $this->getMissingIDs('zz_files', 'id', $last_sync_at);
        $results = array_unique(array_merge($da_interventi, $mancanti));

        return $results;
    }

    public function getModifiedRecords($last_sync_at)
    {
        // Elenco di interventi di interesse
        $risorsa_interventi = $this->getRisorsaInterventi();
        $interventi = $risorsa_interventi->getModifiedRecords(null);
        if (empty($interventi)) {
            return [];
        }

        $id_interventi = array_keys($interventi);
        $query = 'SELECT zz_files.id, zz_files.updated_at FROM zz_files WHERE id_module = (SELECT `id` FROM `zz_modules` WHERE `name` = "Interventi") AND id_record IN ('.implode(',', $id_interventi).')';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_files.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $upload = Upload::find($id);

        $record = [
            'id' => $upload->id,
            'tipo' => $upload->extension,
            'nome' => $upload->name,
            'categoria' => $upload->category,
            'size' => $upload->size,
            'id_intervento' => $upload->id_record,
            'data_creazione' => $upload->created_at,
        ];

        return $record;
    }

    public static function getTempDirectory()
    {
        return DIRECTORY_SEPARATOR.
        trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).
        DIRECTORY_SEPARATOR;
    }

    public function createRecord($data)
    {
        $module = Modules::get('Interventi');

        // Creazione del file temporaneo
        $content = explode(',', $data['contenuto']);
        if (count($content) < 1) {
            throw new InternalError();
        }

        $file = self::getTempDirectory().$data['nome'];
        //$path = stream_get_meta_data($file)['uri'];
        file_put_contents($file, base64_decode($content[1]));

        // Salvataggio del file come allegato
        $upload = Upload::build($file, [
            'id_module' => $module['id'],
            'id_record' => $data['id_intervento'],
        ], $data['nome'], $data['categoria']);

        // Chiusura e rimozione del file temporaneo
        delete($file);

        return [
            'id' => $upload->id,
            'tipo' => $upload->extension,
            'size' => $upload->size,
            'contenuto' => '',
        ];
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }
}
