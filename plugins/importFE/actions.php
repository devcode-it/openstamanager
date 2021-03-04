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

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;
use Plugins\ImportFE\FatturaElettronica;
use Plugins\ImportFE\Interaction;

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
            exit();
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
        } catch (Exception $e) {
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
        ];

        $fattura_pa = FatturaElettronica::manage($filename);
        $id_fattura = $fattura_pa->save($info);

        ricalcola_costiagg_fattura($id_fattura);
        elimina_scadenze($id_fattura);
        elimina_movimenti($id_fattura, 0);
        aggiungi_scadenza($id_fattura, post('pagamento'));
        aggiungi_movimento($id_fattura, 'uscita');

        $fattura_pa->delete();

        // Aggiorno la tipologia di anagrafica fornitore
        $anagrafica = $database->fetchOne('SELECT idanagrafica FROM co_documenti WHERE co_documenti.id='.prepare($id_fattura));
        $rs_t = $database->fetchOne("SELECT * FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica=(SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche WHERE an_tipianagrafiche.descrizione='Fornitore') AND idanagrafica=".prepare($anagrafica['idanagrafica']));

        // Se non trovo corrispondenza aggiungo all'anagrafica la tipologia fornitore
        if (empty($rs_t)) {
            $database->query("INSERT INTO an_tipianagrafiche_anagrafiche (idtipoanagrafica, idanagrafica) VALUES ((SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche WHERE an_tipianagrafiche.descrizione='Fornitore'), ".prepare($anagrafica['idanagrafica']).')');
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
        $tipi = $fatture->groupBy(function ($item, $key) {
            return $item->tipo->id;
        })->transform(function ($item, $key) {
            return $item->count();
        });
        $id_tipo = $tipi->sort()->keys()->last();

        // Ricerca del tipo di pagamento più utilizzato
        $pagamenti = $fatture->mapToGroups(function ($item, $key) {
            return [$item->pagamento->id => $item->pagamento];
        });
        $id_pagamento = $pagamenti->map(function ($item, $key) {
            return $item->count();
        })->sort()->keys()->last();
        $pagamento = $pagamenti[$id_pagamento]->first();

        // Ricerca del conto più utilizzato
        $conti = $righe->groupBy(function ($item, $key) {
            return $item->idconto;
        })->transform(function ($item, $key) {
            return $item->count();
        });
        $id_conto = $conti->sort()->keys()->last();
        $conto = $database->fetchOne('SELECT * FROM co_pianodeiconti3 WHERE id = '.prepare($id_conto));

        // Ricerca dell'IVA più utilizzata secondo percentuali
        $iva = [];
        $percentuali_iva = $righe->groupBy(function ($item, $key) {
            return $item->aliquota->percentuale;
        });
        foreach ($percentuali_iva as $key => $values) {
            $aliquote = $values->mapToGroups(function ($item, $key) {
                return [$item->aliquota->id => $item->aliquota];
            });
            $id_aliquota = $aliquote->map(function ($item, $key) {
                return $item->count();
            })->sort()->keys()->last();
            $aliquota = $aliquote[$id_aliquota]->first();

            $iva[$key] = [
                'id' => $aliquota->id,
                'descrizione' => $aliquota->descrizione,
            ];
        }

        echo json_encode([
            'id_tipo' => $id_tipo,
            'pagamento' => [
                'id' => $pagamento->id,
                'descrizione' => $pagamento->descrizione,
            ],
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

        // Iterazione sulle singole righe
        $righe = $fattura_pa->getRighe();
        foreach ($righe as $key => $riga) {
            $collegamento = null;

            // Visualizzazione codici articoli
            $codici = $riga['CodiceArticolo'] ?: [];
            $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

            // Ricerca dell'articolo collegato al codice
            $id_articolo = null;
            foreach ($codici as $codice) {
                if (!empty($anagrafica) && empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT id_articolo AS id FROM mg_fornitore_articolo WHERE codice_fornitore = '.prepare($codice['CodiceValore']).' AND id_fornitore = '.prepare($anagrafica->id))['id'];
                }

                if (empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT id FROM mg_articoli WHERE codice = '.prepare($codice['CodiceValore']))['id'];
                }

                if (!empty($id_articolo)) {
                    break;
                }
            }

            $query = "SELECT dt_righe_ddt.id, dt_righe_ddt.idddt AS id_documento, dt_righe_ddt.is_descrizione, dt_righe_ddt.idarticolo, dt_righe_ddt.is_sconto, 'ddt' AS ref,
            CONCAT('DDT num. ', IF(numero_esterno != '', numero_esterno, numero), ' del ', DATE_FORMAT(data, '%d/%m/%Y'), ' [', (SELECT descrizione FROM dt_statiddt WHERE id = idstatoddt)  , ']') AS opzione
        FROM dt_righe_ddt
            INNER JOIN dt_ddt ON dt_ddt.id = dt_righe_ddt.idddt
        WHERE dt_ddt.idanagrafica = ".prepare($anagrafica->id)." AND |where_ddt|

        UNION SELECT or_righe_ordini.id, or_righe_ordini.idordine AS id_documento, or_righe_ordini.is_descrizione, or_righe_ordini.idarticolo, or_righe_ordini.is_sconto, 'ordine' AS ref,
            CONCAT('Ordine num. ', IF(numero_esterno != '', numero_esterno, numero), ' del ', DATE_FORMAT(data, '%d/%m/%Y'), ' [', (SELECT descrizione FROM or_statiordine WHERE id = idstatoordine)  , ']') AS opzione
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine
        WHERE or_ordini.idanagrafica = ".prepare($anagrafica->id).' AND |where_ordini|';

            // Ricerca di righe DDT/Ordine con stesso Articolo
            if (!empty($id_articolo)) {
                $query_articolo = replace($query, [
                    '|where_ddt|' => 'dt_righe_ddt.idarticolo = '.prepare($id_articolo),
                    '|where_ordini|' => 'or_righe_ordini.idarticolo = '.prepare($id_articolo),
                ]);

                $collegamento = $database->fetchOne($query_articolo);
            }

            // Ricerca di righe DDT/Ordine per stessa descrizione
            if (empty($collegamento)) {
                $query_descrizione = replace($query, [
                    '|where_ddt|' => 'dt_righe_ddt.descrizione = '.prepare($riga['Descrizione']),
                    '|where_ordini|' => 'or_righe_ordini.descrizione = '.prepare($riga['Descrizione']),
                ]);

                $collegamento = $database->fetchOne($query_descrizione);
            }

            // Ricerca di righe DDT/Ordine per stesso importo
            if (empty($collegamento)) {
                $query_descrizione = replace($query, [
                    '|where_ddt|' => 'dt_righe_ddt.prezzo_unitario = '.prepare($riga['PrezzoUnitario']),
                    '|where_ordini|' => 'or_righe_ordini.prezzo_unitario = '.prepare($riga['PrezzoUnitario']),
                ]);

                $collegamento = $database->fetchOne($query_descrizione);
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

                // Compilazione dei dati
                $results[$key] = [
                    'documento' => [
                        'tipo' => $collegamento['ref'],
                        'id' => $collegamento['id_documento'],
                        'descrizione' => reference($documento, tr('Origine')),
                        'opzione' => $collegamento['opzione'],
                    ],
                    'riga' => [
                        'tipo' => get_class($riga),
                        'id' => $riga->id,
                        'descrizione' => $riga->descrizione,
                        'qta' => $riga->qta,
                        'um' => $riga->um,
                        'prezzo_unitario' => $riga->prezzo_unitario ?: $riga_origine->prezzo_unitario,
                        'id_iva' => $riga->id_iva,
                        'iva_percentuale' => $riga->aliquota->percentuale,
                    ],
                ];
            }
        }

        echo json_encode($results);

        break;
}
