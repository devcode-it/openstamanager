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

namespace Plugins\ImportFE;

use Common\Components\Component;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Articoli\Categoria;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use Plugins\ListinoClienti\DettaglioPrezzo;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaOrdinaria extends FatturaElettronica
{
    public function __construct($name, $directory = null, $plugin = null)
    {
        parent::__construct($name, $directory, $plugin);

        if ($this->getHeader()['DatiTrasmissione']['FormatoTrasmissione'] == 'FSM10') {
            throw new \UnexpectedValueException();
        }
    }

    public function getAnagrafe($tipo = null)
    {
        if ($tipo == 'Cliente') {
            $dati = $this->getHeader()['CessionarioCommittente'];
        } else {
            $dati = $this->getHeader()['CedentePrestatore'];
        }

        $anagrafe = $dati['DatiAnagrafici'];
        $rea = $dati['IscrizioneREA'];
        $sede = $dati['Sede'];
        $contatti = $dati['Contatti'];

        $info = [
            'partita_iva' => $anagrafe['IdFiscaleIVA']['IdCodice'],
            'codice_fiscale' => $anagrafe['CodiceFiscale'],
            'ragione_sociale' => $anagrafe['Anagrafica']['Denominazione'],
            'nome' => $anagrafe['Anagrafica']['Nome'],
            'cognome' => $anagrafe['Anagrafica']['Cognome'],
            'rea' => [
                'codice' => (!empty($dati['IscrizioneREA']) ? $rea['Ufficio'].'-'.$rea['NumeroREA'] : ''),
                'capitale_sociale' => $rea['CapitaleSociale'],
            ],
            'sede' => [
                'indirizzo' => $sede['Indirizzo'].' '.$sede['NumeroCivico'],
                'cap' => $sede['CAP'],
                'citta' => $sede['Comune'],
                'provincia' => $sede['Provincia'],
                'nazione' => $sede['Nazione'],
            ],
            'contatti' => [
                'telefono' => $contatti['Telefono'],
                'fax' => $contatti['Fax'],
                'email' => $contatti['email'],
            ],
        ];

        return $info;
    }

    public function getRighe()
    {
        // Estraggo le linee
        $linee = $this->getBody()['DatiBeniServizi']['DettaglioLinee'];
        $linee = $this->forceArray($linee);

        // Estraggo la cassa previdenziale
        $cassa_previdenziale = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento']['DatiCassaPrevidenziale'];

        $imponibile = [];
        $totale_imposta = [];
        $importo = 0;

        // Calcolo l'imponibile per aliquota al netto degli sconti
        foreach ($linee as $linea) {
            $importo = $linea['PrezzoUnitario'] * $linea['Quantita'];

            if ($linea['ScontoMaggiorazione']) {
                $linea['ScontoMaggiorazione'] = $this->forceArray($linea['ScontoMaggiorazione']);
                foreach ($linea['ScontoMaggiorazione'] as $sm) {
                    if (isset($sm['Percentuale'])) {
                        $sconto = ($importo * $sm['Percentuale'] / 100);
                        if ($sm['Tipo'] == 'SC') {
                            $importo -= $sconto;
                        } else {
                            $importo += $sconto;
                        }
                    } elseif (isset($sm['Importo'])) {
                        if ($sm['Tipo'] == 'SC') {
                            $importo -= $sm['Importo'] * $linea['Quantita'];
                        } else {
                            $importo += $sm['Importo'] * $linea['Quantita'];
                        }
                    }
                }
            }

            if (!$linea['Quantita']) {
                $importo = $linea['PrezzoUnitario'];
            }

            $imponibile[$linea['AliquotaIVA']] += round($importo, 2);
        }

        // Estraggo i riepiloghi
        $riepiloghi = $this->getBody()['DatiBeniServizi']['DatiRiepilogo'];
        $riepiloghi = $this->forceArray($riepiloghi);
        $riepiloghi_raggruppati = [];

        foreach ($riepiloghi as $riepilogo) {
            $aliquota_iva = $riepilogo['AliquotaIVA'];

            if (array_key_exists($aliquota_iva, $riepiloghi_raggruppati)) {
                $riepiloghi_raggruppati[$aliquota_iva]['ImponibileImporto'] += $riepilogo['ImponibileImporto'];
                $riepiloghi_raggruppati[$aliquota_iva]['Imposta'] += $riepilogo['Imposta'];
            } else {
                $riepiloghi_raggruppati[$aliquota_iva] = $riepilogo;
            }
        }

        foreach ($imponibile as $aliquota_iva => $importo) {
            $totale_imposta[$aliquota_iva] = $imponibile[$aliquota_iva] * $aliquota_iva / 100;
            if ($cassa_previdenziale['AliquotaIVA'] == $aliquota_iva) {
                $totale_imposta[$aliquota_iva] += $cassa_previdenziale['ImportoContributoCassa'] * $cassa_previdenziale['AliquotaIVA'] / 100;
            }
        }

        // Calcolo la differenza IVA per aliquota, per creare una riga relativa all'arrotondamento IVA
        foreach ($riepiloghi_raggruppati as $riepilogo) {
            $valore = 0;
            $differenza_iva = ($totale_imposta[$riepilogo['AliquotaIVA']] ? round((float) $riepilogo['Imposta'] - round($totale_imposta[$riepilogo['AliquotaIVA']], 2), 2) : 0);

            if ($differenza_iva) {
                $valore = $differenza_iva * 100 / ($riepilogo['AliquotaIVA'] ?: 1);
            }

            if ($valore != 0) {
                $descrizione = tr('Arrotondamento IVA _VALUE_', [
                    '_VALUE_' => empty($riepilogo['Natura']) ? numberFormat($riepilogo['AliquotaIVA']).'%' : $riepilogo['Natura'],
                ]);

                $linee[] = [
                    'Descrizione' => $descrizione,
                    'PrezzoUnitario' => $valore,
                    'Quantita' => 1,
                    'AliquotaIVA' => $riepilogo['AliquotaIVA'],
                    'Natura' => $riepilogo['Natura'],
                ];
            }
        }

        return $this->forceArray($linee);
    }

    public function saveRighe($articoli, $iva, $conto, $movimentazione = true, $crea_articoli = [], $tipi_riferimenti = [], $id_riferimenti = [], $tipi_riferimenti_vendita = [], $id_riferimenti_vendita = [], $update_info = [], $serials = [])
    {
        $info = $this->getRitenutaRivalsa();

        $righe = $this->getRighe();
        $fattura = $this->getFattura();
        $anagrafica = Anagrafica::find($fattura->idanagrafica);
        $direzione = 'uscita';
        $id_ritenuta_acconto = $info['id_ritenuta_acconto'];
        $id_rivalsa = $info['id_rivalsa'];
        $calcolo_ritenuta_acconto = $info['rivalsa_in_ritenuta'] ? 'IMP+RIV' : 'IMP';
        $ritenuta_contributi = !empty($fattura->id_ritenuta_contributi);
        $conto_arrotondamenti = null;

        // Estraggo l'eventuale arrotondamento IVA applicato
        $arrotondamento_iva = 0;
        foreach ($righe as $riga) {
            if (str_contains((string) $riga['Descrizione'], 'Arrotondamento')) {
                $arrotondamento_iva += round($riga['PrezzoUnitario'], 2);
            }
        }

        // Disattivo temporaneamente l'impostazione per evadere solo le quantità previste
        $original_setting_evasione = setting('Permetti il superamento della soglia quantità dei documenti di origine');

        \Settings::setValue('Permetti il superamento della soglia quantità dei documenti di origine', 1);

        // Estraggo i totali del documento
        $totale_righe_riepilogo = 0;
        $totale_arrotondamento_riepilogo = 0;
        $totale_imponibile_riepilogo = 0;

        $dati_riepilogo = $this->getBody()['DatiBeniServizi']['DatiRiepilogo'];
        if (!empty($dati_riepilogo['ImponibileImporto'])) {
            $totale_arrotondamento_riepilogo = $dati_riepilogo['Arrotondamento'];
            $totale_imponibile_riepilogo = sum($dati_riepilogo['ImponibileImporto']);
        } elseif (is_array($dati_riepilogo)) {
            foreach ($dati_riepilogo as $dato) {
                $totale_arrotondamento_riepilogo += $dato['Arrotondamento'];
                $totale_imponibile_riepilogo += $dato['ImponibileImporto'];
            }
        }

        // Estraggo il totale delle righe
        foreach ($righe as $linea) {
            $importo = $linea['PrezzoUnitario'] * $linea['Quantita'];

            if ($linea['ScontoMaggiorazione']) {
                $linea['ScontoMaggiorazione'] = $this->forceArray($linea['ScontoMaggiorazione']);
                foreach ($linea['ScontoMaggiorazione'] as $sm) {
                    if (isset($sm['Percentuale'])) {
                        $sconto = ($importo * $sm['Percentuale'] / 100);
                        if ($sm['Tipo'] == 'SC') {
                            $importo -= $sconto;
                        } else {
                            $importo += $sconto;
                        }
                    } elseif (isset($sm['Importo'])) {
                        if ($sm['Tipo'] == 'SC') {
                            $importo -= $sm['Importo'] * $linea['Quantita'];
                        } else {
                            $importo += $sm['Importo'] * $linea['Quantita'];
                        }
                    }
                }
            }

            if (!$linea['Quantita']) {
                $importo = $linea['PrezzoUnitario'];
            }

            $totale_righe_riepilogo += round($importo, 2);
        }

        // Creazione righe
        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            $riga['PrezzoUnitario'] = floatval($riga['PrezzoUnitario']);
            $riga['Quantita'] = floatval($riga['Quantita']);

            $is_descrizione = empty($riga['Quantita']) && empty($riga['PrezzoUnitario']);

            $codici = $riga['CodiceArticolo'] ?: [];
            $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

            // Creazione articolo relativo
            if (!empty($codici) && !empty($crea_articoli[$key]) && empty($articolo)) {
                $codice = $codici[0]['CodiceValore'];
                $articolo = ArticoloOriginale::where('codice', $codice)->first();

                if (empty($articolo)) {
                    $nome_categoria = 'Importazione automatica';
                    $categoria = Categoria::find((new Categoria())->getByField('title', strtolower($nome_categoria)));
                    if (empty($categoria)) {
                        $categoria = Categoria::build();
                        $categoria->setTranslation('title', $nome_categoria);
                        $categoria->save();
                    }

                    $articolo = ArticoloOriginale::build($codice, $categoria);
                    $articolo->setTranslation('title', $riga['Descrizione']);
                    $articolo->um = $riga['UnitaMisura'];
                    $articolo->idconto_acquisto = $conto[$key];
                    $articolo->abilita_serial = setting('Serial number abilitato di default');
                    $articolo->save();
                }
            }

            if (!empty($articolo)) {
                $articolo->idconto_acquisto = $conto[$key];
                $articolo->save();

                $obj = Articolo::build($fattura, $articolo);

                $obj->movimentazione($movimentazione);

                $target_type = Articolo::class;
            } elseif ($is_descrizione) {
                $obj = Descrizione::build($fattura);

                $target_type = Descrizione::class;
            } else {
                $obj = Riga::build($fattura);

                $target_type = Riga::class;
            }

            $obj->descrizione = $riga['Descrizione'];
            $obj->save();

            // Collegamento al documento di riferimento
            $has_serial_riferimento = false;
            if (!empty($tipi_riferimenti[$key]) && is_subclass_of($tipi_riferimenti[$key], Component::class) && !empty($id_riferimenti[$key])) {
                $riga_origine = ($tipi_riferimenti[$key])::find($id_riferimenti[$key]);
                [$riferimento_precedente, $nuovo_riferimento] = $obj->impostaOrigine($riga_origine);

                // Correzione della descrizione
                $obj->descrizione = $riferimento_precedente ? str_replace($riferimento_precedente, '', $obj->descrizione) : '';
                $obj->descrizione .= $nuovo_riferimento;

                $serials_rif = $riga_origine->serials;
                if ($serials_rif && $obj->abilita_serial) {
                    $obj->serials = $serials_rif;
                    $has_serial_riferimento = true;
                }
            }

            if (!empty($tipi_riferimenti_vendita[$key])) {
                database()->insert('co_riferimenti_righe', [
                    'source_type' => $tipi_riferimenti_vendita[$key],
                    'source_id' => $id_riferimenti_vendita[$key],
                    'target_type' => $target_type,
                    'target_id' => $obj->id,
                ]);
            }

            if (!$is_descrizione) {
                $obj->id_iva = $iva[$key];
                $obj->idconto = $conto[$key];

                if (empty($conto_arrotondamenti) && !empty($conto[$key])) {
                    $conto_arrotondamenti = $conto[$key];
                }

                $obj->ritenuta_contributi = $ritenuta_contributi;

                // Inserisco la ritenuta se è specificata nella riga o se non è specificata nella riga ma è presente in Dati ritenuta (quindi comprende tutte le righe)
                if (!empty($riga['Ritenuta']) || $info['ritenuta_norighe'] == true || $info['rivalsa_norighe'] == true) {
                    $obj->id_ritenuta_acconto = $id_ritenuta_acconto;
                    $obj->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                    $obj->id_rivalsa_inps = $id_rivalsa;
                }

                // Nel caso il prezzo sia negativo viene gestito attraverso l'inversione della quantità (come per le note di credito)
                if (!empty($articolo->um) && !empty($articolo->um_secondaria) && !empty((float) $articolo->fattore_um_secondaria) && $riga['UnitaMisura'] == $articolo->um_secondaria) {
                    $qta = (($riga['Quantita'] ?: 1) / ($articolo->fattore_um_secondaria ?: 1));
                    $prezzo = $totale_righe_riepilogo > 0 ? $totale_righe_riepilogo / ($qta ?: 1) : -($totale_righe_riepilogo / ($qta ?: 1));
                } else {
                    $qta = ($riga['Quantita'] ?: 1);
                    $prezzo = $totale_righe_riepilogo > 0 ? $riga['PrezzoUnitario'] : -$riga['PrezzoUnitario'];
                }

                // Prezzo e quantità
                $obj->prezzo_unitario = $prezzo;
                $obj->qta = $qta;

                if (!empty($riga['UnitaMisura'])) {
                    if (!empty($articolo->um) && !empty($articolo->um_secondaria) && !empty((float) $articolo->fattore_um_secondaria) && $riga['UnitaMisura'] == $articolo->um_secondaria) {
                        $obj->um = $articolo->um;
                    } else {
                        $obj->um = $riga['UnitaMisura'];
                    }
                }

                // Sconti e maggiorazioni
                $sconti = $riga['ScontoMaggiorazione'];
                if (!empty($sconti)) {
                    $tot_sconto_calcolato = 0;
                    $sconto_unitario = 0;
                    $sconti = $sconti[0] ? $sconti : [$sconti];

                    // Determina il tipo di sconto in caso di sconti misti UNT e PRC
                    foreach ($sconti as $sconto) {
                        $tipo_sconto = !empty($sconto['Importo']) ? 'UNT' : 'PRC';
                        if (!empty($tipo) && $tipo_sconto != $tipo) {
                            $tipo = 'UNT';
                        } else {
                            $tipo = $tipo_sconto;
                        }
                    }

                    foreach ($sconti as $sconto) {
                        $unitario = $sconto['Importo'] ?: $sconto['Percentuale'];

                        // Sconto o Maggiorazione
                        $sconto_riga = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;

                        $tipo_sconto = !empty($sconto['Importo']) ? 'UNT' : 'PRC';
                        if ($tipo_sconto == 'PRC') {
                            $sconto_calcolato = calcola_sconto([
                                'sconto' => $sconto_riga,
                                'prezzo' => $sconto_unitario ? $obj->prezzo_unitario - ($tot_sconto_calcolato / ($obj->qta ?: 1)) : $obj->prezzo_unitario,
                                'tipo' => 'PRC',
                                'qta' => $obj->qta,
                            ]);

                            if ($tipo == 'PRC') {
                                $tot_sconto = $sconto_calcolato * 100 / ($obj->imponibile ?: 1);
                            } else {
                                $tot_sconto = $sconto_calcolato;
                            }
                        } else {
                            $tot_sconto = $sconto_riga;
                        }

                        $tot_sconto_calcolato += $sconto_calcolato;
                        $sconto_unitario += $tot_sconto;
                    }

                    $obj->setSconto($sconto_unitario, $tipo);
                }

                // Aggiornamento prezzo di acquisto e fornitore predefinito in base alle impostazioni
                if (!empty($articolo)) {
                    if ($update_info[$key] == 'update_price' || $update_info[$key] == 'update_all') {
                        $dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $anagrafica->idanagrafica, $direzione)
                        ->first();

                        // Aggiungo associazione fornitore-articolo se non presente
                        if (empty($dettaglio_predefinito)) {
                            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
                        }

                        // Imposto lo sconto nel listino solo se è una percentuale, se è un importo lo sottraggo dal prezzo
                        if ($tipo == 'PRC') {
                            $dettaglio_predefinito->sconto_percentuale = $sconto_unitario;
                            $prezzo_unitario = $obj->prezzo_unitario;
                            $prezzo_acquisto = $obj->prezzo_unitario - ($obj->prezzo_unitario * $sconto_unitario / 100);
                        } else {
                            $prezzo_unitario = $obj->prezzo_unitario - $sconto_unitario;
                            $prezzo_acquisto = $prezzo_unitario;
                        }

                        // Aggiornamento listino
                        $dettaglio_predefinito->setPrezzoUnitario($prezzo_unitario);
                        $dettaglio_predefinito->save();

                        // Aggiornamento fornitore predefinito
                        if ($update_info[$key] == 'update_all') {
                            // Aggiornamento prezzo di acquisto e fornitore predefinito
                            $articolo->prezzo_acquisto = $prezzo_acquisto;
                            $articolo->id_fornitore = $anagrafica->idanagrafica;
                            $articolo->save();
                        }
                    }

                    // Gestione seriali
                    if ($serials[$key] && !$has_serial_riferimento) {
                        $obj->serials = $serials[$key];
                    }
                }

                $tipo = null;
                $sconto_unitario = null;
            }

            $obj->save();
        }

        // Ripristino l'impostazione iniziale di evasione quantità
        \Settings::setValue('Permetti il superamento della soglia quantità dei documenti di origine', $original_setting_evasione);

        // Ricaricamento della fattura
        $fattura->refresh();

        // Arrotondamenti differenti nella fattura XML
        $riepiloghi = $this->forceArray($dati_riepilogo);
        $imposta_riepilogo = 0;

        foreach ($riepiloghi as $riepilogo) {
            $imposta_riepilogo += $riepilogo['Imposta'];
        }

        $totale_documento = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento']['ImportoTotaleDocumento'];
        if (empty($totale_documento)) {
            // se ImportoTotaleDocumento non è valorizzato recupero l'importo dal nodo ImportoPagamento
            $totale_documento = $this->getBody()['DatiPagamento']['DettaglioPagamento']['ImportoPagamento'];
        }
        $differenza_iva = round(abs($fattura->iva) - abs($imposta_riepilogo), 2);
        $diff = round(abs($fattura->totale_imponibile + abs($imposta_riepilogo) + $fattura->rivalsa_inps) - ($totale_documento ? abs($totale_documento) : 0), 2);

        $iva_arrotondamento = database()->fetchOne('SELECT * FROM `co_iva` WHERE `percentuale`= 0 AND `deleted_at` IS NULL LIMIT 1');
        if ($diff || $differenza_iva) {
            if ($diff && $differenza_iva) {
                $diff = ($diff + $differenza_iva) * '-1';
            } elseif ($diff == 0 && $differenza_iva) {
                $diff = $differenza_iva * '-1';
            } else {
                $diff = -$diff;
            }

            $obj = Riga::build($fattura);

            $obj->descrizione = tr('Arrotondamento calcolato in automatico');
            $obj->id_iva = $iva_arrotondamento['id'];
            $obj->idconto = $conto_arrotondamenti;
            $obj->prezzo_unitario = round($diff, 4);
            $obj->qta = 1;

            $obj->save();
        }
    }

    protected function prepareFattura($id_tipo, $data, $data_registrazione, $id_sezionale, $ref_fattura, $tipo = null)
    {
        $fattura = parent::prepareFattura($id_tipo, $data, $data_registrazione, $id_sezionale, $ref_fattura, $tipo);
        $database = database();

        $righe = $this->getRighe();

        $totali = array_column($righe, 'PrezzoTotale');
        $totale = sum($totali);

        foreach ($righe as $riga) {
            $dati = $riga['AltriDatiGestionali'];
            if (!empty($dati)) {
                $dati = isset($dati[0]) ? $dati : [$dati];

                foreach ($dati as $dato) {
                    if ($dato['TipoDato'] == 'CASSA-PREV') {
                        $descrizione = $dato['RiferimentoTesto'];
                        $importo = floatval($dato['RiferimentoNumero']);

                        preg_match('/^(.+?) - (.+?) \((.+?)%\)$/', trim((string) $descrizione), $m);

                        $nome = ucwords(strtolower($m[2]));
                        $percentuale = $m[3];

                        $totale_previsto = round($importo / ($percentuale ?: 1) * 100, 2);
                        $percentuale_importo = round($totale_previsto / ($totale ?: 1) * 100, 2);

                        $ritenuta_contributi = $database->fetchOne('SELECT * FROM`co_ritenuta_contributi` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));
                        if (empty($ritenuta_contributi)) {
                            $database->query('INSERT INTO `co_ritenuta_contributi` (`descrizione`, `percentuale`, `percentuale_imponibile`) VALUES ('.prepare($nome).', '.prepare($percentuale).', '.prepare($percentuale_importo).')');
                        }

                        $ritenuta_contributi = $database->fetchOne('SELECT * FROM`co_ritenuta_contributi` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));

                        $fattura->id_ritenuta_contributi = $ritenuta_contributi['id'];
                    }
                }
            }
        }

        return $fattura;
    }

    protected function getRitenutaRivalsa()
    {
        $database = database();
        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];

        // Righe
        $righe = $this->getRighe();

        $rivalsa_in_ritenuta = false;

        // Rivalsa
        $casse = $dati_generali['DatiCassaPrevidenziale'];
        if (!empty($casse)) {
            $totale = 0;
            $rivalsa_norighe = true;
            $totale_norighe = 0;

            foreach ($righe as $riga) {
                if ($riga['Ritenuta']) {
                    $totale += $riga['PrezzoTotale'];
                    $rivalsa_norighe = false;
                } else {
                    $totale_norighe += $riga['PrezzoTotale'];
                }
            }

            $casse = isset($casse[0]) ? $casse : [$casse];

            $importi = [];
            foreach ($casse as $cassa) {
                $importi[] = floatval($cassa['ImportoContributoCassa']);
                if ($cassa['Ritenuta']) {
                    $rivalsa_in_ritenuta = true;
                }
            }
            $importo = sum($importi);

            $percentuale = round($importo / ($rivalsa_norighe ? $totale_norighe : $totale) * 100, 2);

            $rivalsa = $database->fetchOne('SELECT * FROM`co_rivalse` WHERE `percentuale` = '.prepare($percentuale));
            if (empty($rivalsa)) {
                $descrizione = tr('Rivalsa _PRC_%', [
                    '_PRC_' => numberFormat($percentuale),
                ]);

                $database->query('INSERT INTO `co_rivalse` (`descrizione`, `percentuale`) VALUES ('.prepare($descrizione).', '.prepare($percentuale).')');
            }

            $rivalsa = $database->fetchOne('SELECT * FROM`co_rivalse` WHERE `percentuale` = '.prepare($percentuale));
            $id_rivalsa = $rivalsa['id'];
        }

        $percentuale = 0;
        $importo = 0;
        // Ritenuta d'Acconto
        $ritenuta = $dati_generali['DatiRitenuta'];
        if (!empty($ritenuta)) {
            $totali = [];
            $ritenuta_norighe = true;

            foreach ($righe as $riga) {
                if (!empty($riga['Ritenuta'])) {
                    $totali[] = $riga['PrezzoTotale'];
                    $ritenuta_norighe = false;
                }
            }

            if (!empty($ritenuta)) {
                $ritenuta = $this->forceArray($ritenuta);
                foreach ($ritenuta as $rit) {
                    $percentuale += floatval($rit['AliquotaRitenuta']);
                    $importo += floatval($rit['ImportoRitenuta']);
                }
            }

            // Calcolo la ritenuta su tutte le righe se non è specificata su nessuna riga
            if (empty($totali)) {
                $totale = array_sum(array_column($righe, 'PrezzoTotale'));
            } else {
                $totale = sum($totali);
            }

            $totale_previsto = round($importo * 100 / ($percentuale ?: 1), 2);
            $percentuale_importo = round($totale_previsto / ($totale ?: 1) * 100, 2);
            $percentuale_importo = min($percentuale_importo, 100); // Nota: Fix per la percentuale che superava il 100% nel caso di importi con Rivalsa compresa

            $ritenuta_acconto = $database->fetchOne('SELECT * FROM `co_ritenutaacconto` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));

            if (empty($ritenuta_acconto)) {
                $descrizione = tr('Ritenuta _PRC_% sul _TOT_%', [
                    '_PRC_' => numberFormat($percentuale),
                    '_TOT_' => numberFormat($percentuale_importo),
                ]);

                $database->query('INSERT INTO `co_ritenutaacconto` (`descrizione`, `percentuale`, `percentuale_imponibile`) VALUES ('.prepare($descrizione).', '.prepare($percentuale).', '.prepare($percentuale_importo).')');
                $ritenuta_acconto = $database->fetchOne('SELECT * FROM `co_ritenutaacconto` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));
            }

            $id_ritenuta_acconto = $ritenuta_acconto['id'];
        }

        return [
            'id_ritenuta_acconto' => $id_ritenuta_acconto,
            'id_rivalsa' => $id_rivalsa,
            'rivalsa_norighe' => $rivalsa_norighe,
            'rivalsa_in_ritenuta' => $rivalsa_in_ritenuta,
            'ritenuta_norighe' => $ritenuta_norighe,
        ];
    }
}
