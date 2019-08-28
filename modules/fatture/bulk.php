<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;
use Plugins\ExportFE\FatturaElettronica;
use Util\Zip;

switch (post('op')) {
    case 'export-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da stampare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $print = Prints::getModulePredefinedPrint($id_module);

                Prints::render($print['id'], $r['id'], $dir.'tmp/');
            }

            // Creazione zip
            if (extension_loaded('zip')) {
                Zip::create($dir.'tmp/', $zip);

                // Invio al browser dello zip
                download($zip);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }
        }

        break;

    case 'delete-bulk':

        foreach ($id_records as $id) {
            $dbo->query('DELETE FROM co_documenti  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
            $dbo->query('DELETE FROM mg_movimenti WHERE iddocumento='.prepare($id).Modules::getAdditionalsQuery($id_module));
        }

        flash()->info(tr('Fatture eliminate!'));

        break;

    case 'genera-xml':
        $failed = [];
        $added = [];

        foreach ($id_records as $id) {
            $fattura = Fattura::find($id);
            try {
                $fattura_pa = new FatturaElettronica($id);

                if (!empty($fattura_pa) && !$fattura_pa->isGenerated()) {
                    $file = $fattura_pa->save($upload_dir);
                    $added[] = $fattura->numero_esterno;
                }
            } catch (UnexpectedValueException $e) {
                $failed[] = $fattura->numero_esterno;
            }
        }

        if (!empty($failed)) {
            flash()->warning(tr('Le fatture elettroniche _LIST_ non sono state generate.', [
                '_LIST_' => implode(', ', $failed),
            ]));
        }

        if (!empty($added)) {
            flash()->info(tr('Le fatture elettroniche _LIST_ sono state generate.', [
                '_LIST_' => implode(', ', $added),
            ]));
        }

        break;

    case 'export-xml-bulk':
        $dir = DOCROOT.'/files/export_fatture/';
        directory($dir.'tmp/');

        $dir = slashes($dir);
        $zip = slashes($dir.'fatture_'.time().'.zip');

        // Rimozione dei contenuti precedenti
        $files = glob($dir.'/*.zip');
        foreach ($files as $file) {
            delete($file);
        }

        // Selezione delle fatture da esportare
        $fatture = $dbo->fetchArray('SELECT co_documenti.id, numero_esterno, data, ragione_sociale, co_tipidocumento.descrizione, co_tipidocumento.dir FROM co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_statidocumento ON co_documenti.idstatodocumento=co_statidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        $failed = [];
        $added = 0;
        if (!empty($fatture)) {
            foreach ($fatture as $r) {
                $fattura = Fattura::find($r['id']);
                $include = true;

                try {
                    if ($r['dir'] == 'entrata') {
                        $fe = new \Plugins\ExportFE\FatturaElettronica($fattura->id);
                        $include = $fe->isGenerated();
                    } else {
                        $include = $fattura->isFE();
                    }
                } catch (UnexpectedValueException $e) {
                    $include = false;
                }

                if (!$include) {
                    $failed[] = $fattura->numero_esterno;
                } else {
                    if ($r['dir'] == 'entrata') {
                        $src = $fe->getFilename();
                        $dst = $src;
                    } else {
                        $src = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->filepath);
                        $dst = basename($fattura->uploads()->where('name', 'Fattura Elettronica')->first()->original_name);
                    }

                    $file = slashes($module->upload_directory.'/'.$src);
                    $dest = slashes($dir.'/tmp/'.$dst);

                    $result = copy($file, $dest);

                    if ($result) {
                        ++$added;
                        //operationLog('export-xml-bulk', ['id_record' => $r['id']]);
                    } else {
                        $failed[] = $fattura->numero_esterno;
                    }
                }
            }

            // Creazione zip
            if (extension_loaded('zip') and !empty($added)) {
                Zip::create($dir.'tmp/', $zip);

                // Invio al browser il file zip
                download($zip);

                // Rimozione dei contenuti
                delete($dir.'tmp/');
            }

            if (!empty($failed)) {
                flash()->warning(tr('Le fatture elettroniche _LIST_ non sono state incluse poichè non ancora generate o non presenti sul server', [
                    '_LIST_' => implode(', ', $failed),
                ]));
            }
        }
        break;
}

if (App::debug()) {
    $operations = [
        'delete-bulk' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
    ];
}

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'fatture',
        'url' => $rootdir.'/add.php?id_module='.Modules::get('Prima nota')['id'],
    ],
];

if ($module->name == 'Fatture di vendita') {
    $operations['genera-xml'] = [
        'text' => '<span><i class="fa fa-file-code-o"></i> '.tr('Genera fatture elettroniche').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Generare le fatture elettroniche per i documenti selezionati?<br><small>(le fatture dovranno essere nello stato <i class="fa fa-clock-o text-info" title="Emessa"></i> <small>Emessa</small> e non essere mai state generate)</small>'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];

    $operations['export-bulk'] = [
        'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta stampe').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Vuoi davvero esportare i PDF delle fatture selezionate in un archivio ZIP?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];
}

$operations['export-xml-bulk'] = [
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o"></i> '.tr('Esporta XML').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

return $operations;
