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
            $fe = new \Plugins\ExportFE\FatturaElettronica($fattura->id);

            //se la fattura è emessa e non è stata generata la fattura elettronica
            if ($fattura->stato->descrizione == 'Emessa' and !($fe->isGenerated())) {
                $fattura_pa = new FatturaElettronica($id);
                if (!empty($fattura_pa)) {
                    $file = $fattura_pa->save($upload_dir);
                    $added[] = $fattura->numero_esterno;
                }
            } else {
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
                        operationLog('export-xml-bulk', ['id_record' => $r['id']]);
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

    case 'registra-contabile':
        //Generazione della descrizione del movimento
        $rs_fatture = $dbo->fetchArray('SELECT *, co_documenti.id AS id, co_documenti.data AS data_doc FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_documenti.id IN('.implode(',', $id_records).')');

        //calcolo della descrizione
        $descrizione_movimento = 'Pag. fatture num. ';

        for ($i = 0; $i < sizeof($rs_fatture); ++$i) {
            if ($rs_fatture[$i]['numero_esterno'] != '') {
                $descrizione_movimento .= $rs_fatture[$i]['numero_esterno'].' ';
            } else {
                $descrizione_movimento .= $rs_fatture[$i]['numero'].' ';
            }
        }

        $idmastrino = get_new_idmastrino();

        $importo_conto_aziendale = 0;

        for ($i = 0; $i < sizeof($rs_fatture); ++$i) {
            //Inserimento righe cliente

            if ($rs_fatture[$i]['dir'] == 'entrata') {
                $dir = 'entrata';
            } else {
                $dir = 'uscita';
            }

            $field = 'idconto_'.($dir == 'entrata' ? 'vendite' : 'acquisti');
            $idconto_aziendale = $dbo->fetchArray('SELECT '.$field.' FROM co_pagamenti WHERE id = (SELECT idpagamento FROM co_documenti WHERE id='.prepare($rs_fatture[$i]['id']).') GROUP BY descrizione')[0][$field];

            // Lettura conto di default
            $idconto_aziendale = !empty($idconto_aziendale) ? $idconto_aziendale : setting('Conto aziendale predefinito');

            $query = 'SELECT SUM(ABS(da_pagare-pagato)) AS rata FROM co_scadenziario WHERE iddocumento='.prepare($rs_fatture[$i]['id']).' GROUP BY iddocumento';
            $rs = $dbo->fetchArray($query);
            $totale_pagato = $rs[0]['rata'];

            $importo_conto_aziendale += $totale_pagato;
        }

        //Inserimento riga unica per conto aziendale
        if ($dir == 'entrata') {
            $dbo->query('INSERT INTO co_movimenti(idmastrino, data, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', NOW(), '.prepare($descrizione_movimento).', '.prepare($idconto_aziendale).', '.prepare($importo_conto_aziendale).', 1)');
        } else {
            $dbo->query('INSERT INTO co_movimenti(idmastrino, data, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', NOW(), '.prepare($descrizione_movimento).', '.prepare($idconto_aziendale).', '.prepare(-$importo_conto_aziendale).', 1)');
        }

        for ($i = 0; $i < sizeof($rs_fatture); ++$i) {
            //Inserimento righe cliente

            if ($rs_fatture[$i]['dir'] == 'entrata') {
                $dir = 'entrata';
            } else {
                $dir = 'uscita';
            }

            $query = 'SELECT SUM(ABS(da_pagare-pagato)) AS rata FROM co_scadenziario WHERE iddocumento='.prepare($rs_fatture[$i]['id']).' GROUP BY iddocumento';
            $rs = $dbo->fetchArray($query);
            $totale_pagato = $rs[0]['rata'];

            // conto crediti clienti
            if ($dir == 'entrata') {
                // Se è la prima nota di una fattura leggo il conto del cliente
                if ($rs_fatture[$i]['id'] != '') {
                    $query = 'SELECT idconto_cliente FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($rs_fatture[$i]['id']);
                    $rs = $dbo->fetchArray($query);
                    $idconto_controparte = $rs[0]['idconto_cliente'];
                } else {
                    $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti'";
                    $rs = $dbo->fetchArray($query);
                    $idconto_controparte = $rs[0]['id'];
                }
            }
            // conto debiti fornitori
            else {
                // Se è la prima nota di una fattura leggo il conto del fornitore
                if ($rs_fatture[$i]['id'] != '') {
                    $query = 'SELECT idconto_fornitore FROM an_anagrafiche INNER JOIN co_documenti ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_documenti.id='.prepare($rs_fatture[$i]['id']);
                    $rs = $dbo->fetchArray($query);
                    $idconto_controparte = $rs[0]['idconto_fornitore'];
                } else {
                    $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo fornitori'";
                    $rs = $dbo->fetchArray($query);
                    $idconto_controparte = $rs[0]['id'];
                }
            }

            // Lettura causale movimento (documento e ragione sociale)
            $importo_conto_controparte = $totale_pagato;

            if ($dir == 'entrata') {
                $dbo->query('INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', NOW(), '.prepare($rs_fatture[$i]['data_doc']).', '.prepare($rs_fatture[$i]['id']).', '.prepare($rs_fatture[$i]['idanagrafica']).', '.prepare($descrizione_movimento).', '.prepare($idconto_controparte).', '.prepare(-$importo_conto_controparte).', 1)');
            } else {
                $dbo->query('INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica, descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', NOW(), '.prepare($rs_fatture[$i]['data_doc']).', '.prepare($rs_fatture[$i]['id']).', '.prepare($rs_fatture[$i]['idanagrafica']).', '.prepare($descrizione_movimento).', '.prepare($idconto_controparte).', '.prepare($importo_conto_controparte).', 1)');
            }

            aggiorna_scadenziario($rs_fatture[$i]['id'], abs($totale_pagato), date('d/m/Y'));

            // Verifico se la fattura è stata pagata tutta, così imposto lo stato a "Pagato"
            $query = 'SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($rs_fatture[$i]['id']);
            $rs = $dbo->fetchArray($query);

            // Aggiorno lo stato della fattura
            if (abs($rs[0]['tot_pagato']) == abs($rs[0]['tot_da_pagare'])) {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Pagato') WHERE id=".prepare($rs_fatture[$i]['id']));
            } elseif (abs($rs[0]['tot_pagato']) != abs($rs[0]['tot_da_pagare']) && abs($rs[0]['tot_pagato']) != '0') {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Parzialmente pagato') WHERE id=".prepare($rs_fatture[$i]['id']));
            } else {
                $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Emessa') WHERE id=".prepare($rs_fatture[$i]['id']));
            }

            // Aggiorno lo stato dei preventivi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idpreventivo FROM co_righe_documenti WHERE iddocumento='.prepare($rs_fatture[$i]['id']).' AND NOT idpreventivo=0 AND idpreventivo IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE co_preventivi SET idstato=(SELECT id FROM co_statipreventivi WHERE descrizione='Pagato') WHERE id=".prepare($rs2[$j]['idpreventivo']));
            }

            // Aggiorno lo stato dei contratti collegati alla fattura se ce ne sono
            $query2 = 'SELECT idcontratto FROM co_righe_documenti WHERE iddocumento='.prepare($rs_fatture[$i]['id']).' AND NOT idcontratto=0 AND idcontratto IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);
            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE co_contratti SET idstato=(SELECT id FROM co_staticontratti WHERE descrizione='Pagato') WHERE id=".prepare($rs2[$j]['idcontratto']));
            }

            // Aggiorno lo stato degli interventi collegati alla fattura se ce ne sono
            $query2 = 'SELECT idintervento FROM co_righe_documenti WHERE iddocumento='.prepare($rs_fatture[$i]['id']).' AND idintervento IS NOT NULL';
            $rs2 = $dbo->fetchArray($query2);

            for ($j = 0; $j < sizeof($rs2); ++$j) {
                $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id_preventivo=".prepare($rs2[$j]['idpreventivo']));
            }
        }

        $database->commitTransaction();
        header('location:'.$rootdir.'/editor.php?id_module='.Modules::get('Prima nota')['id'].'&id_record='.$idmastrino);
        exit;

        break;
}

if (App::debug()) {
    $operations = [
        'delete-bulk' => '<span><i class="fa fa-trash" ></i> '.tr('Elimina selezionati').'</span>',
    ];
}

$operations['registra-contabile'] = [
    'text' => '<span><i class="fa fa-calculator" ></i> '.tr('Registra contabile pagamento').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi aggiungere un movimento contabile per le fatture selezionate?<br><small>(le fatture dovranno essere nello stato <i class="fa fa-clock-o text-info" title="Emessa"></i> <small>Emessa</small> altrimenti non saranno processate)</small>'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

if ($module->name == 'Fatture di vendita') {
    $operations['genera-xml'] = [
        'text' => '<span><i class="fa fa-file-code-o" ></i> '.tr('Genera fatture elettroniche').'</span>',
        'data' => [
            'title' => '',
            'msg' => tr('Generare le fatture elettroniche per i documenti selezionati?<br><small>(le fatture dovranno essere nello stato <i class="fa fa-clock-o text-info" title="Emessa"></i> <small>Emessa</small> e non essere mai state generate)</small>'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => true,
        ],
    ];

    $operations['export-bulk'] = [
        'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o" ></i> '.tr('Esporta stampe').'</span>',
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
    'text' => '<span class="'.((!extension_loaded('zip')) ? 'text-muted disabled' : '').'"><i class="fa fa-file-archive-o" ></i> '.tr('Esporta XML').'</span>',
    'data' => [
        'title' => '',
        'msg' => tr('Vuoi davvero esportare le fatture elettroniche selezionate in un archivio ZIP?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => true,
    ],
];

return $operations;
