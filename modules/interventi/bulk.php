<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'export-bulk':
        $dir = DOCROOT.'/files/export_interventi/';
        directory($dir.'tmp/');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $records = $dbo->fetchArray('SELECT in_interventi.id, in_interventi.codice, data_richiesta, ragione_sociale FROM in_interventi INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id IN('.implode(',', $id_records).')');

        if (!empty($records)) {
            foreach ($records as $r) {
                //$numero = !empty($r['numero_esterno']) ? $r['numero_esterno'] : $r['numero'];
                $numero =  $r['codice'];
				
				$numero = str_replace(['/', '\\'], '-', $numero);

                // Gestione della stampa
                $rapportino_nome = sanitizeFilename($numero.' '.date('Y_m_d', strtotime($r['data_richiesta'])).' '.$r['ragione_sociale'].'.pdf');
                $filename = slashes($dir.'tmp/'.$rapportino_nome);

                $_GET['idintervento'] = $r['id']; // Fix temporaneo per la stampa
                $idintervento = $r['id']; // Fix temporaneo per la stampa
                //$ptype = ($r['descrizione'] == 'Fattura accompagnatoria di vendita') ? 'fatture_accompagnatorie' : 'fatture';
				
				$ptype = 'interventi';
				
                require DOCROOT.'/pdfgen.php';
            }

            $dir = slashes($dir);
            $file = slashes($dir.'interventi_'.time().'.zip');

            // Creazione zip
            if (extension_loaded('zip')) {
                create_zip($dir.'tmp/', $file);

                // Invio al browser dello zip
                download($file);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;
}

return [
    'export-bulk' => [
        'text' => tr('Esporta stampe'),
        'data' => [
            'msg' => tr('Vuoi davvero esportare tutte le stampe in un archivio?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ],
];
