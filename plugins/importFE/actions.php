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

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Modules\Anagrafiche\Tipo;
use Modules\DDT\DDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Ordini\Ordine;
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;
use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;
use Util\XML;

$file = null;
switch (filter('op')) {
    case 'list':
        $list = Interaction::getRemoteList();

        echo json_encode($list);

        break;

    case 'save':
        $temp_name = $_FILES['blob']['tmp_name'];
        $name = $_FILES['blob']['name'];

        if (string_ends_with($name, '.zip')) {
            $directory = FatturaElettronica::getImportDirectory();

            Util\Zip::extract($temp_name, $directory);

            // Redirect forzato per l'importazione
            echo json_encode([
                'id' => 1,
            ]);
            exit;
        } else {
            $content = file_get_contents($temp_name);

            $file = FatturaElettronica::store($_FILES['blob']['name'], $content);
        }

        // no break
    case 'prepare':
        if (!isset($file)) {
            $name = filter('name');
            $file = Interaction::getInvoiceFile($name);
        }

        try {
            if (!FatturaElettronica::isValid($file)) {
                echo json_encode([
                    'already' => 1,
                ]);

                return;
            }
        } catch (Exception) {
        }

        // Individuazione ID fisico
        $files = Interaction::getFileList();
        foreach ($files as $key => $value) {
            if ($value['name'] == $file) {
                $index = $key;

                break;
            }
        }

        echo json_encode([
            'id' => $index + 1,
        ]);

        break;

    case 'delete':
        $file_id = get('file_id');

        $directory = FatturaElettronica::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            delete($directory.'/'.$file['name']);
        }

        break;

    case 'download':
        $file_id = get('file_id');

        $directory = FatturaElettronica::getImportDirectory();
        $files = Interaction::getFileList();
        $file = $files[$file_id];

        if (!empty($file)) {
            download($directory.'/'.$file['name']);
        }

        break;

    case 'generate':
        $filename = post('filename');

        $info = [
            'id_pagamento' => post('pagamento'),
            'id_segment' => post('id_segment'),
            'id_tipo' => post('id_tipo'),
            'ref_fattura' => post('ref_fattura'),
            'data_registrazione' => post('data_registrazione'),
            'articoli' => post('articoli'),
            'iva' => post('iva'),
            'conto' => post('conto'),
            'tipo_riga_riferimento' => post('tipo_riga_riferimento'),
            'id_riga_riferimento' => post('id_riga_riferimento'),
            'tipo_riga_riferimento_vendita' => post('tipo_riga_riferimento_vendita'),
            'id_riga_riferimento_vendita' => post('id_riga_riferimento_vendita'),
            'movimentazione' => post('movimentazione'),
            'crea_articoli' => post('crea_articoli'),
            'is_ritenuta_pagata' => post('is_ritenuta_pagata'),
            'update_info' => post('update_info'),
            'serial' => post('flag_crea_seriali') ? post('serial') : [],
        ];

        $fattura_pa = FatturaElettronica::manage($filename);
        $id_fattura = $fattura_pa->save($info);
        $fattura_pa->delete();
        $fattura = Fattura::find($id_fattura);
        $id_autofattura = post('autofattura');
        $new_stato = Stato::where('name', 'Pagato')->first()->id;

        if ($fattura->isAutofattura() && !empty($id_autofattura)) {
            $autofattura_collegata = Fattura::find($id_autofattura);
            $fattura->registraScadenze(true);
            $autofattura_collegata->registraScadenze(true);

            $fattura->stato()->associate($new_stato);
            $autofattura_collegata->stato()->associate($new_stato);

            $mastrino = Mastrino::build('Compensazione autofattura', $fattura->data, false, true);

            $movimento1 = Movimento::build($mastrino, $fattura->anagrafica->idconto_cliente);
            $movimento1->setTotale($fattura->totale, 0);
            $movimento1->save();

            $movimento2 = Movimento::build($mastrino, $fattura->anagrafica->idconto_fornitore);
            $movimento2->setTotale(0, $fattura->totale);
            $movimento2->save();

            $fattura->id_autofattura = $id_autofattura;
            $fattura->save();
            $autofattura_collegata->save();
        }

        // Aggiorno la tipologia di anagrafica fornitore
        $anagrafica = $database->fetchOne('SELECT `idanagrafica` FROM `co_documenti` WHERE `co_documenti`.`id`='.prepare($id_fattura));
        $id_tipo = Tipo::where('name', 'Fornitore')->first()->id;
        $rs_t = $database->fetchOne('SELECT * FROM `an_tipianagrafiche_anagrafiche` WHERE `idtipoanagrafica`='.prepare($id_tipo).' AND `idanagrafica`='.prepare($anagrafica['idanagrafica']));

        // Se non trovo corrispondenza aggiungo all'anagrafica la tipologia fornitore
        if (empty($rs_t)) {
            $database->query("INSERT INTO `an_tipianagrafiche_anagrafiche` (`idtipoanagrafica`, `idanagrafica`) VALUES ($id_tipo, ".prepare($anagrafica['idanagrafica']).')');
        }

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processInvoice($filename);
            if ($process_result != '') {
                flash()->error($process_result);
                redirect(base_path().'/controller.php?id_module='.$id_module);

                return;
            }
        }

        $files = Interaction::getFileList();
        $file = $files[$id_record - 1];

        if (get('sequence') == null) {
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_fattura);
        } elseif (!empty($file)) {
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&sequence=1');
            flash()->info(tr('La fattura numero _NUM_ del _DATA_ (_ANAGRAFICA_) è stata importata correttamente', [
                '_NUM_' => $fattura->numero,
                '_DATA_' => dateFormat($fattura->data),
                '_ANAGRAFICA_' => $fattura->anagrafica->ragione_sociale,
            ]));
        } else {
            flash()->info(tr('Tutte le fatture salvate sono state importate!'));
            redirect(base_path().'/controller.php?id_module='.$id_module);
        }
        break;

    case 'process':
        $name = get('name');

        // Processo il file ricevuto
        if (Interaction::isEnabled()) {
            $process_result = Interaction::processInvoice($name);
            if (!empty($process_result)) {
                flash()->error($process_result);
            }
        }

        break;

    case 'compile':
        // Gestione del caso di anagrafica inesistente
        if (empty($anagrafica)) {
            echo json_encode([]);

            return;
        }

        $fatture = $anagrafica->fattureAcquisto()
            ->contabile()
            ->orderBy('created_at', 'DESC')
            ->take(10)
            ->get();

        $righe = collect();
        foreach ($fatture as $fattura) {
            $righe->push($fattura->righe);
            $righe->push($fattura->articoli);
        }
        $righe = $righe->flatten();

        // Gestione del caso di anagrafica senza fatture o con fatture senza righe
        if ($fatture->isEmpty() || $righe->isEmpty()) {
            echo json_encode([]);

            return;
        }

        // Ricerca del tipo di documento più utilizzato
        $tipi = $fatture->groupBy(fn ($item, $key) => $item->tipo->id)->transform(fn ($item, $key) => $item->count());
        $id_tipo = $tipi->sort()->keys()->last();

        // Ricerca del conto più utilizzato
        $conti = $righe->groupBy(fn ($item, $key) => $item->idconto)->transform(fn ($item, $key) => $item->count());
        $id_conto = $conti->sort()->keys()->last();
        $conto = $database->fetchOne('SELECT * FROM co_pianodeiconti3 WHERE id = '.prepare($id_conto));

        // Ricerca dell'IVA più utilizzata secondo percentuali
        $iva = [];
        $percentuali_iva = $righe->groupBy(fn ($item, $key) => $item->aliquota->percentuale);
        foreach ($percentuali_iva as $key => $values) {
            $aliquote = $values->mapToGroups(fn ($item, $key) => [$item->aliquota->id => $item->aliquota]);
            $id_aliquota = $aliquote->map(fn ($item, $key) => $item->count())->sort()->keys()->last();
            $aliquota = $aliquote[$id_aliquota]->first();

            $iva[$key] = [
                'id' => $aliquota->id,
                'descrizione' => $aliquota->getTranslation('title'),
            ];
        }

        echo json_encode([
            'id_tipo' => $id_tipo,
            'conto' => [
                'id' => $conto['id'],
                'descrizione' => $conto['descrizione'],
            ],
            'iva' => $iva,
        ]);
        break;

    case 'riferimenti-automatici':
        if (empty($anagrafica)) {
            echo json_encode([]);

            return;
        }

        $results = [];

        // Dati ordini
        $DatiOrdini = XML::forceArray($fattura_pa->getBody()['DatiGenerali']['DatiOrdineAcquisto']);
        $DatiDDT = XML::forceArray($fattura_pa->getBody()['DatiGenerali']['DatiDDT']);

        $replaces = ['n ', 'N ', 'n. ', 'N. ', 'nr ', 'NR ', 'nr. ', 'NR. ', 'num ', 'NUM ', 'num. ', 'NUM. ', 'numero ', 'NUMERO '];

        // Riorganizzazione dati ordini per numero di riga
        $dati_ordini = [];
        foreach ($DatiOrdini as $dato) {
            if (is_array($dato['RiferimentoNumeroLinea'])) {
                foreach ($dato['RiferimentoNumeroLinea'] as $dati => $linea) {
                    foreach ($replaces as $replace) {
                        if (string_starts_with($dato['IdDocumento'], $replace)) {
                            $dato['IdDocumento'] = str_replace($replace, '', $dato['IdDocumento']);
                            break;
                        }
                    }

                    try {
                        $dati_ordini[(int) $linea] = [
                            'numero' => $dato['IdDocumento'],
                            'anno' => (new Carbon($dato['Data']))->format('Y'),
                        ];
                    } catch (Exception) {
                        $dati_ordini[(int) $linea] = [
                            'numero' => $dato['IdDocumento'],
                        ];
                    }
                }
            } else {
                foreach ($replaces as $replace) {
                    if (string_starts_with($dato['IdDocumento'], $replace)) {
                        $dato['IdDocumento'] = str_replace($replace, '', $dato['IdDocumento']);
                        break;
                    }
                }

                try {
                    $dati_ordini[(int) $dato['RiferimentoNumeroLinea']] = [
                        'numero' => $dato['IdDocumento'],
                        'anno' => (new Carbon($dato['Data']))->format('Y'),
                    ];
                } catch (Exception) {
                    $dati_ordini[(int) $dato['RiferimentoNumeroLinea']] = [
                        'numero' => $dato['IdDocumento'],
                    ];
                }
            }
        }

        // Riorganizzazione dati ddt per numero di riga
        $dati_ddt = [];
        foreach ($DatiDDT as $dato) {
            if (is_array($dato['RiferimentoNumeroLinea'])) {
                foreach ($dato['RiferimentoNumeroLinea'] as $dati => $linea) {
                    foreach ($replaces as $replace) {
                        if (string_starts_with($dato['NumeroDDT'], $replace)) {
                            $dato['NumeroDDT'] = str_replace($replace, '', $dato['NumeroDDT']);
                            break;
                        }
                    }

                    try {
                        $dati_ddt[(int) $linea] = [
                            'numero' => $dato['NumeroDDT'],
                            'anno' => (new Carbon($dato['DataDDT']))->format('Y'),
                        ];
                    } catch (Exception) {
                        $dati_ddt[(int) $linea] = [
                            'numero' => $dato['NumeroDDT'],
                        ];
                    }
                }
            } else {
                foreach ($replaces as $replace) {
                    if (string_starts_with($dato['NumeroDDT'], $replace)) {
                        $dato['NumeroDDT'] = str_replace($replace, '', $dato['NumeroDDT']);
                        break;
                    }
                }
                try {
                    $dati_ddt[(int) $dato['RiferimentoNumeroLinea']] = [
                        'numero' => $dato['NumeroDDT'],
                        'anno' => (new Carbon($dato['DataDDT']))->format('Y'),
                    ];
                } catch (Exception) {
                    $dati_ddt[(int) $dato['RiferimentoNumeroLinea']] = [
                        'numero' => $dato['NumeroDDT'],
                    ];
                }
            }
        }

        // Iterazione sulle singole righe
        $righe = $fattura_pa->getRighe();
        foreach ($righe as $key => $riga) {
            // Se la riga è descrittiva non la collego a documenti
            if ($riga['PrezzoTotale'] == 0) {
                continue;
            }

            $collegamento = null;
            $match_documento_da_fe = true;

            $numero_linea = (int) $riga['NumeroLinea'];

            // Visualizzazione codici articoli
            $codici = $riga['CodiceArticolo'] ?: [];
            $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

            // Ricerca dell'articolo collegato a ogni codice associato alla riga
            $id_articolo = null;
            foreach ($codici as $codice) {
                if (!empty($anagrafica) && empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT id_articolo AS id FROM mg_fornitore_articolo WHERE codice_fornitore = '.prepare($codice['CodiceValore']).' AND id_fornitore = '.prepare($anagrafica->id))['id'];

                    if (empty($id_articolo)) {
                        $id_articolo = $database->fetchOne('SELECT id_articolo AS id FROM mg_fornitore_articolo WHERE REPLACE(codice_fornitore, " ", "") = '.prepare($codice['CodiceValore']).' AND id_fornitore = '.prepare($anagrafica->id))['id'];
                    }
                }

                if (empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE `codice` = '.prepare($codice['CodiceValore']).' AND `deleted_at` IS NULL')['id'];

                    if (empty($id_articolo)) {
                        $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE REPLACE(`codice`, " ", "") = '.prepare($codice['CodiceValore']).' AND `deleted_at` IS NULL')['id'];
                    }

                    // Controllo se esistono articoli con barcode corrispondente al codice
                    if (empty($id_articolo)) {
                        $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE `barcode` = '.prepare($codice['CodiceValore']).' AND `deleted_at` IS NULL')['id'];
                    }

                    if (empty($id_articolo)) {
                        $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE REPLACE(`barcode`, " ", "") = '.prepare($codice['CodiceValore']).' AND `deleted_at` IS NULL')['id'];
                    }
                }

                if (!empty($id_articolo)) {
                    break;
                }
            }

            // Se nella fattura elettronica è indicato un DDT cerco quel documento specifico
            $ddt = $dati_ddt[$numero_linea];
            $query = "SELECT 
                `dt_righe_ddt`.`id`, 
                `dt_righe_ddt`.`idddt` AS id_documento, 
                `dt_righe_ddt`.`is_descrizione`, 
                `dt_righe_ddt`.`idarticolo`, 
                `dt_righe_ddt`.`is_sconto`, 'ddt' AS ref,
                CONCAT('DDT num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `dt_statiddt_lang`.`title`, ']') AS opzione
            FROM 
                `dt_righe_ddt`
                INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`
                INNER JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
                LEFT JOIN `dt_statiddt_lang` ON `dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).'
            WHERE
                `dt_ddt`.`numero_esterno` = '.prepare($ddt['numero']).' AND
                YEAR(`dt_ddt`.`data`) = '.prepare($ddt['anno']).' AND
                `dt_ddt`.`idanagrafica` = '.prepare($anagrafica->id).' AND
                `dt_righe_ddt`.`qta` > `dt_righe_ddt`.`qta_evasa` AND
                |where|';

            // Ricerca di righe DDT con stesso Articolo
            if (!empty($id_articolo)) {
                $query_articolo = replace($query, [
                    '|where|' => '`dt_righe_ddt`.`idarticolo` = '.prepare($id_articolo),
                ]);

                $collegamento = $database->fetchOne($query_articolo);
            }

            // Ricerca di righe DDT per stessa descrizione
            if (empty($collegamento)) {
                $query_descrizione = replace($query, [
                    '|where|' => '`dt_righe_ddt`.`descrizione` = '.prepare($riga['Descrizione']),
                ]);

                $collegamento = $database->fetchOne($query_descrizione);
            }

            // Se nella fattura elettronica NON è indicato un DDT ed è indicato anche un ordine
            // cerco per quell'ordine
            if (empty($collegamento)) {
                $ordine = $dati_ordini[$numero_linea];
                $query = "SELECT 
                    `or_righe_ordini`.`id`, 
                    `or_righe_ordini`.`idordine` AS id_documento, 
                    `or_righe_ordini`.`is_descrizione`, 
                    `or_righe_ordini`.`idarticolo`, 
                    `or_righe_ordini`.`is_sconto`, 
                    'ordine' AS ref,
                    CONCAT('Ordine num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `or_statiordine_lang`.`title`  , ']') AS opzione
                FROM `or_righe_ordini`
                    INNER JOIN `or_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
                    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
                    LEFT JOIN `or_statiordine_lang` ON `or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).'
                WHERE
                    `or_ordini`.`numero_esterno` = '.prepare($ordine['numero']).'
                    AND YEAR(`or_ordini`.`data`) = '.prepare($ordine['anno']).'
                    AND `or_ordini`.`idanagrafica` = '.prepare($anagrafica->id).'
                    AND `or_righe_ordini`.`qta` > `or_righe_ordini`.`qta_evasa`
                    AND |where|';

                // Ricerca di righe Ordine con stesso Articolo
                if (!empty($id_articolo)) {
                    $query_articolo = replace($query, [
                        '|where|' => '`or_righe_ordini`.`idarticolo` = '.prepare($id_articolo),
                    ]);

                    $collegamento = $database->fetchOne($query_articolo);
                }

                // Ricerca di righe Ordine per stessa descrizione
                if (empty($collegamento)) {
                    $query_descrizione = replace($query, [
                        '|where|' => '`or_righe_ordini`.`descrizione` = '.prepare($riga['Descrizione']),
                    ]);

                    $collegamento = $database->fetchOne($query_descrizione);
                }
            }

            /*
             * TENTATIVO 2: ricerca solo per articolo o descrizione su documenti
             * non referenziati nella fattura elettronica
             */
            // Se non ci sono Ordini o DDT cerco per contenuto
            if (empty($collegamento)) {
                $match_documento_da_fe = false;
                $query = "SELECT 
                        `dt_righe_ddt`.`id`, 
                        `dt_righe_ddt`.`idddt` AS id_documento, 
                        `dt_righe_ddt`.`is_descrizione`, 
                        `dt_righe_ddt`.`idarticolo`, 
                        `dt_righe_ddt`.`is_sconto`, 
                        'ddt' AS ref,
                        CONCAT('DDT num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', `dt_statiddt_lang`.`title`, ']') AS opzione
                    FROM 
                        `dt_righe_ddt`
                        INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`
                        INNER JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
                        LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
                        INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
                    WHERE 
                        `dt_ddt`.`idanagrafica` = '.prepare($anagrafica->id)." AND 
                        |where_ddt| AND 
                        `dt_righe_ddt`.`qta` > `dt_righe_ddt`.`qta_evasa` AND 
                        `dt_statiddt_lang`.`title` != 'Fatturato' AND
                        `dt_tipiddt`.`dir` = 'uscita'
                UNION 
                    SELECT 
                        `or_righe_ordini`.`id`,
                        `or_righe_ordini`.`idordine` AS id_documento,
                        `or_righe_ordini`.`is_descrizione`, 
                        `or_righe_ordini`.`idarticolo`, 
                        `or_righe_ordini`.`is_sconto`, 
                        'ordine' AS ref,
                        CONCAT('Ordine num. ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' del ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' [', (SELECT `descrizione` FROM `or_statiordine` WHERE `id` = `idstatoordine`)  , ']') AS opzione
                    FROM 
                        `or_righe_ordini`
                        INNER JOIN `or_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
                        INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
                        LEFT JOIN `or_statiordine_lang` ON (`or_statiordine_lang`.`id_record` = `or_statiordine`.`id` AND `or_statiordine_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).')
                        INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
                    WHERE 
                        `or_ordini`.`idanagrafica` = '.prepare($anagrafica->id)." AND 
                        |where_ordini| AND 
                        `or_righe_ordini`.`qta` > `or_righe_ordini`.`qta_evasa` AND 
                        `or_statiordine_lang`.`title` != 'Fatturato' AND
                        `or_tipiordine`.`dir` ='uscita'";

                // Ricerca di righe DDT/Ordine con stesso Articolo
                if (!empty($id_articolo)) {
                    $query_articolo = replace($query, [
                        '|where_ddt|' => '`dt_righe_ddt`.`idarticolo` = '.prepare($id_articolo),
                        '|where_ordini|' => '`or_righe_ordini`.`idarticolo` = '.prepare($id_articolo),
                    ]);

                    $collegamento = $database->fetchOne($query_articolo);
                }

                // Ricerca di righe DDT/Ordine per stessa descrizione
                if (empty($collegamento)) {
                    $query_descrizione = replace($query, [
                        '|where_ddt|' => '`dt_righe_ddt`.`descrizione` = '.prepare($riga['Descrizione']),
                        '|where_ordini|' => '`or_righe_ordini`.`descrizione` = '.prepare($riga['Descrizione']),
                    ]);

                    $collegamento = $database->fetchOne($query_descrizione);
                }

                // Ricerca di righe DDT/Ordine per stesso importo
                if (empty($collegamento)) {
                    $query_descrizione = replace($query, [
                        '|where_ddt|' => '`dt_righe_ddt`.`prezzo_unitario` = '.prepare($riga['PrezzoUnitario']),
                        '|where_ordini|' => '`or_righe_ordini`.`prezzo_unitario` = '.prepare($riga['PrezzoUnitario']),
                    ]);

                    $collegamento = $database->fetchOne($query_descrizione);
                }
            }

            if (!empty($collegamento)) {
                // Individuazione del documento
                $documento = $collegamento['ref'] == 'ddt' ? DDT::find($collegamento['id_documento']) : Ordine::find($collegamento['id_documento']);

                // Individuazione della classe di gestione per la riga
                $namespace = $collegamento['ref'] == 'ddt' ? 'Modules\\DDT\\Components\\' : 'Modules\\Ordini\\Components\\';
                if (!empty($collegamento['idarticolo'])) {
                    $type = 'Articolo';
                } elseif (!empty($collegamento['is_sconto'])) {
                    $type = 'Sconto';
                } elseif (!empty($collegamento['is_descrizione'])) {
                    $type = 'Descrizione';
                } else {
                    $type = 'Riga';
                }

                // Ricerca della riga
                $riga = $documento->getRiga($namespace.$type, $collegamento['id']);
                $riga_origine = $riga->getOriginalComponent();

                if (!empty($riga->idarticolo)) {
                    $desc_conto = $dbo->fetchOne('SELECT CONCAT( co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_pianodeiconti3.id = '.prepare($riga->articolo->idconto_acquisto))['descrizione'];
                }

                // Compilazione dei dati
                $results[$key] = [
                    'documento' => [
                        'tipo' => $collegamento['ref'],
                        'id' => $collegamento['id_documento'],
                        'descrizione' => reference($documento, tr('Origine')),
                        'opzione' => $collegamento['opzione'],
                        'match_documento_da_fe' => $match_documento_da_fe,
                    ],
                    'riga' => [
                        'tipo' => $riga::class,
                        'id' => $riga->id,
                        'descrizione' => $riga->descrizione,
                        'qta' => $riga->qta,
                        'um' => $riga->um,
                        'prezzo_unitario' => $riga->prezzo_unitario ?: $riga_origine->prezzo_unitario,
                        'id_iva' => $riga->id_iva,
                        'iva_percentuale' => $riga->aliquota->percentuale,
                        'id_articolo' => $riga->idarticolo,
                        'desc_articolo' => str_replace(' ', '_', $riga->articolo->codice.' - '.$riga->articolo->getTranslation('title')),
                        'id_conto' => $riga->articolo->idconto_acquisto,
                        'desc_conto' => str_replace(' ', '_', $desc_conto),
                    ],
                ];
            }
        }

        echo json_encode($results);

        break;
}
