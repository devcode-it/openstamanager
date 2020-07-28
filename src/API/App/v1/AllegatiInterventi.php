<?php

namespace API\App\v1;

use API\App\AppResource;
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
        $interventi = $risorsa_interventi->getModifiedRecords($last_sync_at);
        if (empty($interventi)) {
            return [];
        }

        $query = 'SELECT zz_files.id FROM zz_files WHERE id_module = (SELECT `id` FROM `zz_modules` WHERE `name` = "Interventi") AND id_record IN ('.implode(',', $interventi).')';

        // Filtro per data
        if ($last_sync_at) {
            $query .= ' AND zz_files.updated_at > '.prepare($last_sync_at);
        }

        $records = database()->fetchArray($query);

        return array_column($records, 'id');
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
        ];

        return $record;
    }

    public function createRecord($data)
    {
        $module = Modules::get('Interventi');

        // Creazione del file temporaneo
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        fwrite($file, $data['contenuto']);

        // Salvataggio del file come allegato
        $upload = Upload::build($path, [
            'id_module' => $module['id'],
            'id_record' => $data['id_intervento'],
        ], $data['nome'], $data['categoria']);

        // Chiusura e rimozione del file temporaneo
        fclose($file);

        return[
            'id' => $upload->id,
            'filename' => $upload->filename,
        ];
    }

    protected function getRisorsaInterventi()
    {
        return new Interventi();
    }
}
