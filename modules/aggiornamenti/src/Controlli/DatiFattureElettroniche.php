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

use Modules\Fatture\Fattura;
use Util\XML;

class DatiFattureElettroniche extends Controllo
{
    // Costanti per i tipi di errore
    public const ERROR_WARNING = 'warning';
    public const ERROR_INFO = 'info';

    // Costanti per le categorie di controllo
    public const CATEGORY_ANAGRAFICA = 'anagrafica';
    public const CATEGORY_TOTALI = 'totali';
    public const CATEGORY_DOCUMENTO = 'documento';
    public const CATEGORY_XML_STRUCTURE = 'xml_structure';

    public function getName()
    {
        return tr('Corrispondenze XML FE e Documenti di vendita');
    }

    public function getType($record)
    {
        return 'info';
    }

    public function check()
    {
        $fatture_vendita = Fattura::vendita()
            ->whereNotIn('codice_stato_fe', ['ERR', 'NS', 'EC02', 'ERVAL'])
            ->where('data', '>=', $_SESSION['period_start'])
            ->where('data', '<=', $_SESSION['period_end'])
            ->orderBy('data')
            ->get();

        foreach ($fatture_vendita as $fattura_vendita) {
            $this->checkFattura($fattura_vendita);
        }
    }

    public function checkFattura(Fattura $fattura_vendita)
    {
        try {
            $xml = XML::read($fattura_vendita->getXML());

            // Controlli di integrità XML
            $xml_errors = $this->checkXMLStructure($xml);

            // Controlli sui dati anagrafici
            $anagrafica_errors = $this->checkAnagraficaData($fattura_vendita, $xml);

            // Controlli sui totali
            $totali_errors = $this->checkTotaliData($fattura_vendita, $xml);

            // Controlli sui dati del documento
            $documento_errors = $this->checkDocumentoData($fattura_vendita, $xml);

            // Raccolta di tutti gli errori
            $all_errors = array_merge($xml_errors, $anagrafica_errors, $totali_errors, $documento_errors);

            if (!empty($all_errors)) {
                $this->processErrors($fattura_vendita, $all_errors);
            }
        } catch (\Exception $e) {
            $this->addResult([
                'id' => $fattura_vendita->id,
                'nome' => $fattura_vendita->getReference(),
                'descrizione' => tr("Impossibile verificare l'XML di questa fattura").': '.$e->getMessage(),
            ]);
        }
    }

    public function execute($record, $params = [])
    {
        return false;
    }

    /**
     * Controlla la struttura XML e i campi obbligatori.
     */
    protected function checkXMLStructure($xml)
    {
        $errors = [];

        // Controllo presenza sezioni obbligatorie
        if (!isset($xml['FatturaElettronicaHeader'])) {
            $errors[] = [
                'type' => self::ERROR_WARNING,
                'category' => self::CATEGORY_XML_STRUCTURE,
                'field' => 'FatturaElettronicaHeader',
                'message' => tr('Manca la sezione FatturaElettronicaHeader nell\'XML'),
                'suggestion' => tr('Rigenerare il file XML della fattura'),
            ];
        }

        if (!isset($xml['FatturaElettronicaBody'])) {
            $errors[] = [
                'type' => self::ERROR_WARNING,
                'category' => self::CATEGORY_XML_STRUCTURE,
                'field' => 'FatturaElettronicaBody',
                'message' => tr('Manca la sezione FatturaElettronicaBody nell\'XML'),
                'suggestion' => tr('Rigenerare il file XML della fattura'),
            ];
        }

        // Controllo campi obbligatori nel header
        if (isset($xml['FatturaElettronicaHeader'])) {
            $header = $xml['FatturaElettronicaHeader'];

            if (!isset($header['DatiTrasmissione']['ProgressivoInvio'])) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_XML_STRUCTURE,
                    'field' => 'ProgressivoInvio',
                    'message' => tr('Manca il ProgressivoInvio nei DatiTrasmissione'),
                    'suggestion' => tr('Verificare la configurazione della fatturazione elettronica'),
                ];
            }

            if (!isset($header['DatiTrasmissione']['FormatoTrasmissione'])) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_XML_STRUCTURE,
                    'field' => 'FormatoTrasmissione',
                    'message' => tr('Manca il FormatoTrasmissione nei DatiTrasmissione'),
                    'suggestion' => tr('Verificare la configurazione della fatturazione elettronica'),
                ];
            }
        }

        // Controllo campi obbligatori nel body
        if (isset($xml['FatturaElettronicaBody'])) {
            $body = $xml['FatturaElettronicaBody'];

            if (!isset($body['DatiGenerali']['DatiGeneraliDocumento']['TipoDocumento'])) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_XML_STRUCTURE,
                    'field' => 'TipoDocumento',
                    'message' => tr('Manca il TipoDocumento nei DatiGeneraliDocumento'),
                    'suggestion' => tr('Verificare il tipo documento della fattura'),
                ];
            }

            if (!isset($body['DatiGenerali']['DatiGeneraliDocumento']['Data'])) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_XML_STRUCTURE,
                    'field' => 'Data',
                    'message' => tr('Manca la Data nei DatiGeneraliDocumento'),
                    'suggestion' => tr('Verificare la data della fattura'),
                ];
            }

            if (!isset($body['DatiGenerali']['DatiGeneraliDocumento']['Numero'])) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_XML_STRUCTURE,
                    'field' => 'Numero',
                    'message' => tr('Manca il Numero nei DatiGeneraliDocumento'),
                    'suggestion' => tr('Verificare il numero della fattura'),
                ];
            }
        }

        return $errors;
    }

    /**
     * Controlla i dati anagrafici tra XML e gestionale.
     */
    protected function checkAnagraficaData(Fattura $fattura_vendita, $xml)
    {
        $errors = [];

        try {
            $dati_anagrafici = $fattura_vendita->is_fattura_conto_terzi
                ? $xml['FatturaElettronicaHeader']['CedentePrestatore']['DatiAnagrafici']
                : $xml['FatturaElettronicaHeader']['CessionarioCommittente']['DatiAnagrafici'];

            $sede = $fattura_vendita->is_fattura_conto_terzi
                ? $xml['FatturaElettronicaHeader']['CedentePrestatore']['Sede']
                : $xml['FatturaElettronicaHeader']['CessionarioCommittente']['Sede'];

            $anagrafica = $fattura_vendita->anagrafica;

            // Controllo P.IVA
            $piva_xml = $dati_anagrafici['IdFiscaleIVA']['IdCodice'] ?? '';
            $piva_gestionale = $anagrafica->piva ?? '';

            if ($piva_xml !== $piva_gestionale) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_ANAGRAFICA,
                    'field' => 'piva',
                    'message' => tr('P.IVA non corrispondente'),
                    'xml_value' => $piva_xml,
                    'gestionale_value' => $piva_gestionale,
                    'suggestion' => tr('Verificare e aggiornare la P.IVA dell\'anagrafica cliente'),
                ];
            }

            // Controllo Codice Fiscale
            $cf_xml = $dati_anagrafici['CodiceFiscale'] ?? '';
            $cf_gestionale = $anagrafica->codice_fiscale ?? '';

            if ($cf_xml !== $cf_gestionale) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_ANAGRAFICA,
                    'field' => 'codice_fiscale',
                    'message' => tr('Codice Fiscale non corrispondente'),
                    'xml_value' => $cf_xml,
                    'gestionale_value' => $cf_gestionale,
                    'suggestion' => tr('Verificare e aggiornare il Codice Fiscale dell\'anagrafica cliente'),
                ];
            }

            // Controllo Denominazione/Ragione Sociale
            $denominazione_xml = '';
            if (isset($dati_anagrafici['Anagrafica']['Denominazione'])) {
                $denominazione_xml = $dati_anagrafici['Anagrafica']['Denominazione'];
            } elseif (isset($dati_anagrafici['Anagrafica']['Nome']) && isset($dati_anagrafici['Anagrafica']['Cognome'])) {
                $denominazione_xml = trim($dati_anagrafici['Anagrafica']['Nome'].' '.$dati_anagrafici['Anagrafica']['Cognome']);
            }

            $denominazione_gestionale = $anagrafica->ragione_sociale ?? '';

            // Controllo più flessibile per la denominazione (ignora differenze minori)
            if (!empty($denominazione_xml) && !empty($denominazione_gestionale)) {
                $denominazione_xml_clean = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $denominazione_xml)));
                $denominazione_gestionale_clean = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $denominazione_gestionale)));

                if ($denominazione_xml_clean !== $denominazione_gestionale_clean) {
                    $errors[] = [
                        'type' => self::ERROR_WARNING,
                        'category' => self::CATEGORY_ANAGRAFICA,
                        'field' => 'denominazione',
                        'message' => tr('Denominazione/Ragione Sociale differente'),
                        'xml_value' => $denominazione_xml,
                        'gestionale_value' => $denominazione_gestionale,
                        'suggestion' => tr('Verificare la correttezza della denominazione nell\'anagrafica cliente'),
                    ];
                }
            }

            // Controllo Indirizzo
            if (isset($sede)) {
                $indirizzo_xml = $sede['Indirizzo'] ?? '';
                $cap_xml = $sede['CAP'] ?? '';
                $comune_xml = $sede['Comune'] ?? '';
                $provincia_xml = $sede['Provincia'] ?? '';

                $indirizzo_gestionale = $anagrafica->sedeLegale->indirizzo ?? '';
                $cap_gestionale = $anagrafica->sedeLegale->cap ?? '';
                $comune_gestionale = $anagrafica->sedeLegale->citta ?? '';
                $provincia_gestionale = $anagrafica->sedeLegale->provincia ?? '';

                if (!empty($indirizzo_xml) && !empty($indirizzo_gestionale)
                    && strtolower(trim((string) $indirizzo_xml)) !== strtolower(trim((string) $indirizzo_gestionale))) {
                    $errors[] = [
                        'type' => self::ERROR_INFO,
                        'category' => self::CATEGORY_ANAGRAFICA,
                        'field' => 'indirizzo',
                        'message' => tr('Indirizzo differente'),
                        'xml_value' => $indirizzo_xml,
                        'gestionale_value' => $indirizzo_gestionale,
                        'suggestion' => tr('Verificare l\'indirizzo nell\'anagrafica cliente'),
                    ];
                }

                if (!empty($cap_xml) && !empty($cap_gestionale) && $cap_xml !== $cap_gestionale) {
                    $errors[] = [
                        'type' => self::ERROR_INFO,
                        'category' => self::CATEGORY_ANAGRAFICA,
                        'field' => 'cap',
                        'message' => tr('CAP differente'),
                        'xml_value' => $cap_xml,
                        'gestionale_value' => $cap_gestionale,
                        'suggestion' => tr('Verificare il CAP nell\'anagrafica cliente'),
                    ];
                }

                if (!empty($comune_xml) && !empty($comune_gestionale)
                    && strtolower(trim((string) $comune_xml)) !== strtolower(trim((string) $comune_gestionale))) {
                    $errors[] = [
                        'type' => self::ERROR_INFO,
                        'category' => self::CATEGORY_ANAGRAFICA,
                        'field' => 'comune',
                        'message' => tr('Comune differente'),
                        'xml_value' => $comune_xml,
                        'gestionale_value' => $comune_gestionale,
                        'suggestion' => tr('Verificare il comune nell\'anagrafica cliente'),
                    ];
                }

                if (!empty($provincia_xml) && !empty($provincia_gestionale)
                    && strtoupper(trim((string) $provincia_xml)) !== strtoupper(trim((string) $provincia_gestionale))) {
                    $errors[] = [
                        'type' => self::ERROR_INFO,
                        'category' => self::CATEGORY_ANAGRAFICA,
                        'field' => 'provincia',
                        'message' => tr('Provincia differente'),
                        'xml_value' => $provincia_xml,
                        'gestionale_value' => $provincia_gestionale,
                        'suggestion' => tr('Verificare la provincia nell\'anagrafica cliente'),
                    ];
                }
            }
        } catch (\Exception $e) {
            $errors[] = [
                'type' => self::ERROR_WARNING,
                'category' => self::CATEGORY_ANAGRAFICA,
                'field' => 'general',
                'message' => tr('Errore durante il controllo dei dati anagrafici').': '.$e->getMessage(),
                'suggestion' => tr('Verificare la struttura XML della fattura'),
            ];
        }

        return $errors;
    }

    /**
     * Controlla i totali tra XML e gestionale.
     */
    protected function checkTotaliData(Fattura $fattura_vendita, $xml)
    {
        $errors = [];

        try {
            $dati_generali = $xml['FatturaElettronicaBody']['DatiGenerali']['DatiGeneraliDocumento'];
            $totale_documento_xml = null;

            // Calcolo del totale XML
            if (isset($dati_generali['ImportoTotaleDocumento'])) {
                $totale_documento_indicato = abs(floatval($dati_generali['ImportoTotaleDocumento']));

                // Calcolo del totale basato sui DatiRiepilogo se ImportoTotaleDocumento è vuoto
                if (empty($totale_documento_indicato) && empty($dati_generali['ScontoMaggiorazione'])) {
                    $totale_documento_xml = 0;

                    $riepiloghi = $xml['FatturaElettronicaBody']['DatiBeniServizi']['DatiRiepilogo'];
                    if (!empty($riepiloghi) && !isset($riepiloghi[0])) {
                        $riepiloghi = [$riepiloghi];
                    }

                    foreach ($riepiloghi as $riepilogo) {
                        $totale_documento_xml = sum([$totale_documento_xml, $riepilogo['ImponibileImporto'], $riepilogo['Imposta']]);
                    }

                    $totale_documento_xml = abs($totale_documento_xml);
                } else {
                    $totale_documento_xml = $totale_documento_indicato;
                }
            }

            $totale_gestionale = abs($fattura_vendita->totale);

            // Controllo corrispondenza totale principale con tolleranza
            $differenza_totale = abs($totale_gestionale - $totale_documento_xml);

            if ($differenza_totale > 0.01) { // Tolleranza di 1 centesimo
                $error_type = self::ERROR_WARNING; // Tutti gli errori sono ora avvisi

                $errors[] = [
                    'type' => $error_type,
                    'category' => self::CATEGORY_TOTALI,
                    'field' => 'totale_documento',
                    'message' => tr('Totale documento non corrispondente (diff: _DIFF_€)', [
                        '_DIFF_' => str_replace('&euro;', '€', moneyFormat($differenza_totale, 2)),
                    ]),
                    'xml_value' => str_replace('&euro;', '€', moneyFormat($totale_documento_xml, 2)),
                    'gestionale_value' => str_replace('&euro;', '€', moneyFormat($totale_gestionale, 2)),
                    'suggestion' => $differenza_totale > 1.00
                        ? tr('Differenza significativa: verificare i calcoli della fattura e rigenerare l\'XML')
                        : tr('Differenza minima probabilmente dovuta ad arrotondamenti: verificare se accettabile'),
                ];
            }

            // Controllo dettagliato dei riepiloghi IVA
            if (isset($xml['FatturaElettronicaBody']['DatiBeniServizi']['DatiRiepilogo'])) {
                $riepiloghi_xml = $xml['FatturaElettronicaBody']['DatiBeniServizi']['DatiRiepilogo'];
                if (!isset($riepiloghi_xml[0])) {
                    $riepiloghi_xml = [$riepiloghi_xml];
                }

                // Raggruppa i riepiloghi IVA del gestionale con normalizzazione delle aliquote
                $riepiloghi_gestionale = [];
                foreach ($fattura_vendita->righe as $riga) {
                    $aliquota = floatval($riga->aliquota->percentuale ?? 0);
                    // Normalizza l'aliquota per evitare problemi di floating point
                    $aliquota_key = number_format($aliquota, 2, '.', '');

                    if (!isset($riepiloghi_gestionale[$aliquota_key])) {
                        $riepiloghi_gestionale[$aliquota_key] = [
                            'aliquota' => $aliquota,
                            'imponibile' => 0,
                            'imposta' => 0,
                        ];
                    }
                    $riepiloghi_gestionale[$aliquota_key]['imponibile'] += $riga->imponibile;
                    $riepiloghi_gestionale[$aliquota_key]['imposta'] += $riga->iva;
                }

                // Confronta i riepiloghi con logica migliorata
                foreach ($riepiloghi_xml as $riepilogo_xml) {
                    $aliquota_xml = floatval($riepilogo_xml['AliquotaIVA']);
                    $aliquota_xml_key = number_format($aliquota_xml, 2, '.', '');
                    $imponibile_xml = floatval($riepilogo_xml['ImponibileImporto']);
                    $imposta_xml = floatval($riepilogo_xml['Imposta']);

                    // Cerca l'aliquota corrispondente nel gestionale
                    $found_aliquota = false;
                    $imponibile_gestionale = 0;
                    $imposta_gestionale = 0;

                    // Controllo esatto
                    if (isset($riepiloghi_gestionale[$aliquota_xml_key])) {
                        $found_aliquota = true;
                        $imponibile_gestionale = $riepiloghi_gestionale[$aliquota_xml_key]['imponibile'];
                        $imposta_gestionale = $riepiloghi_gestionale[$aliquota_xml_key]['imposta'];
                    } else {
                        // Controllo con tolleranza per problemi di arrotondamento
                        foreach ($riepiloghi_gestionale as $riepilogo_gest) {
                            if (abs($riepilogo_gest['aliquota'] - $aliquota_xml) < 0.01) {
                                $found_aliquota = true;
                                $imponibile_gestionale = $riepilogo_gest['imponibile'];
                                $imposta_gestionale = $riepilogo_gest['imposta'];
                                break;
                            }
                        }
                    }

                    if ($found_aliquota) {
                        // Controllo con tolleranza per gli importi (differenza massima di 1 centesimo)
                        $diff_imponibile = abs($imponibile_xml - $imponibile_gestionale);
                        $diff_imposta = abs($imposta_xml - $imposta_gestionale);

                        if ($diff_imponibile > 0.01) {
                            $errors[] = [
                                'type' => self::ERROR_WARNING,
                                'category' => self::CATEGORY_TOTALI,
                                'field' => "imponibile_iva_{$aliquota_xml}",
                                'message' => tr('Imponibile IVA _ALIQUOTA_% non corrispondente (diff: _DIFF_€)', [
                                    '_ALIQUOTA_' => $aliquota_xml,
                                    '_DIFF_' => str_replace('&euro;', '€', moneyFormat($diff_imponibile, 2)),
                                ]),
                                'xml_value' => str_replace('&euro;', '€', moneyFormat($imponibile_xml, 2)),
                                'gestionale_value' => str_replace('&euro;', '€', moneyFormat($imponibile_gestionale, 2)),
                                'suggestion' => tr('Verificare le righe con IVA _ALIQUOTA_% - differenza: _DIFF_€', [
                                    '_ALIQUOTA_' => $aliquota_xml,
                                    '_DIFF_' => str_replace('&euro;', '€', moneyFormat($diff_imponibile, 2)),
                                ]),
                            ];
                        }

                        if ($diff_imposta > 0.01) {
                            $errors[] = [
                                'type' => self::ERROR_WARNING,
                                'category' => self::CATEGORY_TOTALI,
                                'field' => "imposta_iva_{$aliquota_xml}",
                                'message' => tr('Imposta IVA _ALIQUOTA_% non corrispondente (diff: _DIFF_€)', [
                                    '_ALIQUOTA_' => $aliquota_xml,
                                    '_DIFF_' => str_replace('&euro;', '€', moneyFormat($diff_imposta, 2)),
                                ]),
                                'xml_value' => str_replace('&euro;', '€', moneyFormat($imposta_xml, 2)),
                                'gestionale_value' => str_replace('&euro;', '€', moneyFormat($imposta_gestionale, 2)),
                                'suggestion' => tr('Verificare il calcolo IVA per l\'aliquota _ALIQUOTA_% - differenza: _DIFF_€', [
                                    '_ALIQUOTA_' => $aliquota_xml,
                                    '_DIFF_' => str_replace('&euro;', '€', moneyFormat($diff_imposta, 2)),
                                ]),
                            ];
                        }
                    } else {
                        // Debug: mostra le aliquote disponibili nel gestionale
                        $aliquote_disponibili = array_map(fn ($r) => $r['aliquota'].'%', $riepiloghi_gestionale);

                        $errors[] = [
                            'type' => self::ERROR_WARNING,
                            'category' => self::CATEGORY_TOTALI,
                            'field' => "aliquota_iva_{$aliquota_xml}",
                            'message' => tr('Aliquota IVA _ALIQUOTA_% presente nell\'XML ma non trovata nel gestionale', ['_ALIQUOTA_' => $aliquota_xml]),
                            'xml_value' => $aliquota_xml.'%',
                            'gestionale_value' => 'Disponibili: '.implode(', ', $aliquote_disponibili),
                            'suggestion' => tr('Verificare la configurazione delle aliquote IVA. Aliquote nel gestionale: _ALIQUOTE_', [
                                '_ALIQUOTE_' => implode(', ', $aliquote_disponibili),
                            ]),
                        ];
                    }
                }
            }

            // Controllo sconti e maggiorazioni
            if (isset($dati_generali['ScontoMaggiorazione'])) {
                $sconti_xml = $dati_generali['ScontoMaggiorazione'];
                if (!isset($sconti_xml[0])) {
                    $sconti_xml = [$sconti_xml];
                }

                $sconto_totale_gestionale = $fattura_vendita->sconto;
                $sconto_totale_xml = 0;

                foreach ($sconti_xml as $sconto_xml) {
                    if ($sconto_xml['Tipo'] === 'SC') { // Sconto
                        $sconto_totale_xml += floatval($sconto_xml['Importo'] ?? 0);
                    } elseif ($sconto_xml['Tipo'] === 'MG') { // Maggiorazione
                        $sconto_totale_xml -= floatval($sconto_xml['Importo'] ?? 0);
                    }
                }

                if (numberFormat($sconto_totale_xml, 2) !== numberFormat($sconto_totale_gestionale, 2)) {
                    $errors[] = [
                        'type' => self::ERROR_WARNING,
                        'category' => self::CATEGORY_TOTALI,
                        'field' => 'sconti_maggiorazioni',
                        'message' => tr('Sconti/Maggiorazioni non corrispondenti'),
                        'xml_value' => str_replace('&euro;', '€', moneyFormat($sconto_totale_xml, 2)),
                        'gestionale_value' => str_replace('&euro;', '€', moneyFormat($sconto_totale_gestionale, 2)),
                        'suggestion' => tr('Verificare gli sconti e le maggiorazioni applicate'),
                    ];
                }
            }
        } catch (\Exception $e) {
            $errors[] = [
                'type' => self::ERROR_WARNING,
                'category' => self::CATEGORY_TOTALI,
                'field' => 'general',
                'message' => tr('Errore durante il controllo dei totali').': '.$e->getMessage(),
                'suggestion' => tr('Verificare la struttura XML della fattura'),
            ];
        }

        return $errors;
    }

    /**
     * Controlla i dati del documento tra XML e gestionale.
     */
    protected function checkDocumentoData(Fattura $fattura_vendita, $xml)
    {
        $errors = [];

        try {
            $dati_generali = $xml['FatturaElettronicaBody']['DatiGenerali']['DatiGeneraliDocumento'];

            // Controllo numero documento
            $numero_xml = $dati_generali['Numero'] ?? '';
            $numero_gestionale = $fattura_vendita->numero_esterno ?: $fattura_vendita->numero;

            if ($numero_xml !== (string) $numero_gestionale) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_DOCUMENTO,
                    'field' => 'numero',
                    'message' => tr('Numero documento non corrispondente'),
                    'xml_value' => $numero_xml,
                    'gestionale_value' => $numero_gestionale,
                    'suggestion' => tr('Verificare il numero della fattura'),
                ];
            }

            // Controllo data documento con normalizzazione
            $data_xml = $dati_generali['Data'] ?? '';
            $data_gestionale = $fattura_vendita->data;

            if (!empty($data_xml) && !empty($data_gestionale)) {
                // Normalizza le date per il confronto
                try {
                    $data_xml_normalized = date('Y-m-d', strtotime((string) $data_xml));
                    $data_gestionale_normalized = date('Y-m-d', strtotime($data_gestionale));

                    if ($data_xml_normalized !== $data_gestionale_normalized) {
                        $errors[] = [
                            'type' => self::ERROR_WARNING,
                            'category' => self::CATEGORY_DOCUMENTO,
                            'field' => 'data',
                            'message' => tr('Data documento non corrispondente'),
                            'xml_value' => date('d/m/Y', strtotime((string) $data_xml)),
                            'gestionale_value' => date('d/m/Y', strtotime($data_gestionale)),
                            'suggestion' => tr('Verificare la data della fattura. XML: _XML_DATE_, Gestionale: _GEST_DATE_', [
                                '_XML_DATE_' => $data_xml,
                                '_GEST_DATE_' => $data_gestionale,
                            ]),
                        ];
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'type' => self::ERROR_WARNING,
                        'category' => self::CATEGORY_DOCUMENTO,
                        'field' => 'data',
                        'message' => tr('Errore nel controllo della data documento'),
                        'xml_value' => $data_xml,
                        'gestionale_value' => $data_gestionale,
                        'suggestion' => tr('Verificare il formato delle date: ').$e->getMessage(),
                    ];
                }
            }

            // Controllo tipo documento
            $tipo_xml = $dati_generali['TipoDocumento'] ?? '';
            $tipo_gestionale = $this->getTipoDocumentoFE($fattura_vendita);

            if (!empty($tipo_xml) && $tipo_xml !== $tipo_gestionale) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_DOCUMENTO,
                    'field' => 'tipo_documento',
                    'message' => tr('Tipo documento non corrispondente'),
                    'xml_value' => $tipo_xml,
                    'gestionale_value' => $tipo_gestionale,
                    'suggestion' => tr('Verificare il tipo documento della fattura'),
                ];
            }

            // Controllo causale (se presente)
            if (isset($dati_generali['Causale'])) {
                $causale_xml = is_array($dati_generali['Causale'])
                    ? implode(' ', $dati_generali['Causale'])
                    : $dati_generali['Causale'];

                $causale_gestionale = $fattura_vendita->note ?? '';

                if (!empty($causale_xml) && !empty($causale_gestionale)) {
                    // Controllo più flessibile per la causale
                    $causale_xml_clean = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $causale_xml)));
                    $causale_gestionale_clean = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $causale_gestionale)));

                    if ($causale_xml_clean !== $causale_gestionale_clean) {
                        $errors[] = [
                            'type' => self::ERROR_INFO,
                            'category' => self::CATEGORY_DOCUMENTO,
                            'field' => 'causale',
                            'message' => tr('Causale differente'),
                            'xml_value' => $causale_xml,
                            'gestionale_value' => $causale_gestionale,
                            'suggestion' => tr('Verificare le note della fattura'),
                        ];
                    }
                }
            }

            // Controllo divisa
            $divisa_xml = $dati_generali['Divisa'] ?? 'EUR';
            $divisa_gestionale = 'EUR'; // Assumiamo EUR come default

            if ($divisa_xml !== $divisa_gestionale) {
                $errors[] = [
                    'type' => self::ERROR_WARNING,
                    'category' => self::CATEGORY_DOCUMENTO,
                    'field' => 'divisa',
                    'message' => tr('Divisa non corrispondente'),
                    'xml_value' => $divisa_xml,
                    'gestionale_value' => $divisa_gestionale,
                    'suggestion' => tr('Verificare la configurazione della divisa'),
                ];
            }

            // Controllo bollo (se presente)
            if (isset($dati_generali['DatiBollo'])) {
                $bollo_xml = floatval($dati_generali['DatiBollo']['ImportoBollo'] ?? 0);
                $bollo_gestionale = 0; // TODO: implementare il controllo del bollo dal gestionale

                if ($bollo_xml > 0 && numberFormat($bollo_xml, 2) !== numberFormat($bollo_gestionale, 2)) {
                    $errors[] = [
                        'type' => self::ERROR_INFO,
                        'category' => self::CATEGORY_DOCUMENTO,
                        'field' => 'bollo',
                        'message' => tr('Importo bollo differente'),
                        'xml_value' => str_replace('&euro;', '€', moneyFormat($bollo_xml, 2)),
                        'gestionale_value' => str_replace('&euro;', '€', moneyFormat($bollo_gestionale, 2)),
                        'suggestion' => tr('Verificare l\'applicazione del bollo'),
                    ];
                }
            }
        } catch (\Exception $e) {
            $errors[] = [
                'type' => self::ERROR_WARNING,
                'category' => self::CATEGORY_DOCUMENTO,
                'field' => 'general',
                'message' => tr('Errore durante il controllo dei dati documento').': '.$e->getMessage(),
                'suggestion' => tr('Verificare la struttura XML della fattura'),
            ];
        }

        return $errors;
    }

    /**
     * Determina il tipo documento FE basato sulla fattura.
     */
    protected function getTipoDocumentoFE(Fattura $fattura)
    {
        // Logica per determinare il tipo documento FE
        // TD01 = Fattura, TD04 = Nota di credito, TD05 = Nota di debito, etc.

        if ($fattura->tipo->getTranslation('title') === 'Nota di credito') {
            return 'TD04';
        } elseif ($fattura->tipo->getTranslation('title') === 'Nota di debito') {
            return 'TD05';
        } else {
            return 'TD01'; // Fattura standard
        }
    }

    /**
     * Processa gli errori e genera il report.
     */
    protected function processErrors(Fattura $fattura_vendita, $errors)
    {
        // Raggruppa gli errori per categoria e tipo
        $grouped_errors = [
            self::ERROR_WARNING => [],
            self::ERROR_WARNING => [],
            self::ERROR_INFO => [],
        ];

        foreach ($errors as $error) {
            $grouped_errors[$error['type']][] = $error;
        }

        // Genera il report HTML
        $report_html = $this->generateErrorReport($grouped_errors);

        // Determina il tipo di risultato basato sulla gravità degli errori
        $result_type = 'info';
        if (!empty($grouped_errors[self::ERROR_WARNING])) {
            $result_type = 'error';
        } elseif (!empty($grouped_errors[self::ERROR_WARNING])) {
            $result_type = 'warning';
        }

        $this->addResult([
            'id' => $fattura_vendita->id,
            'nome' => $fattura_vendita->getReference(),
            'descrizione' => $report_html,
            'type' => $result_type,
        ]);
    }

    /**
     * Genera il report HTML degli errori.
     */
    protected function generateErrorReport($grouped_errors)
    {
        // Contatori per tipo di errore
        $warning_count = count($grouped_errors[self::ERROR_WARNING]);
        $info_count = count($grouped_errors[self::ERROR_INFO]);

        // Container principale con stili migliorati per evitare conflitti
        $html = '<div style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.4;">';

        // Header con contatori compatto e migliorato
        $total_errors = $warning_count + $info_count;
        $status_color = $warning_count > 0 ? '#ffc107' : '#28a745';

        $html .= '</div>';

        // Usa sempre la modalità compatta per migliorare la leggibilità
        $use_compact_mode = true;

        // Avvisi
        if (!empty($grouped_errors[self::ERROR_WARNING])) {
            $html .= $this->generateErrorSection(
                tr('Avvisi'),
                $grouped_errors[self::ERROR_WARNING],
                'warning',
                $use_compact_mode
            );
        }

        // Informativi
        if (!empty($grouped_errors[self::ERROR_INFO])) {
            $html .= $this->generateErrorSection(
                tr('Controlli Informativi'),
                $grouped_errors[self::ERROR_INFO],
                'info',
                $use_compact_mode
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Genera una sezione del report per un tipo di errore.
     */
    protected function generateErrorSection($title, $errors, $type, $compact_mode = false)
    {
        // Colori per i diversi tipi di errore
        $colors = [
            'danger' => ['bg' => '#f8d7da', 'border' => '#dc3545', 'text' => '#721c24'],
            'warning' => ['bg' => '#fff3cd', 'border' => '#ffc107', 'text' => '#856404'],
            'info' => ['bg' => '#d1ecf1', 'border' => '#17a2b8', 'text' => '#0c5460'],
        ];

        $color = $colors[$type] ?? $colors['info'];

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<div style="border: 1px solid '.$color['border'].'; border-radius: 6px; overflow: hidden;">';

        // Header della sezione compatto
        $html .= '<div style="background: '.$color['bg'].'; padding: 6px 10px; border-bottom: 1px solid '.$color['border'].';">';
        $html .= '<h5 style="margin: 0; color: '.$color['text'].'; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px;">';
        $html .= $this->getTypeIcon($type).' '.$title.' <span style="font-weight: normal; font-size: 11px;">('.count($errors).')</span>';
        $html .= '</h5>';
        $html .= '</div>';

        // Contenuto
        $html .= '<div style="background: white;">';

        // Raggruppa per categoria
        $categories = [];
        foreach ($errors as $error) {
            $categories[$error['category']][] = $error;
        }

        foreach ($categories as $category => $category_errors) {
            $category_title = $this->getCategoryTitle($category);
            $category_icon = $this->getCategoryIcon($category);

            $html .= '<div style="border-bottom: 1px solid #e9ecef; padding: 10px 15px;">';
            $html .= '<h6 style="margin: 0 0 8px 0; color: #495057; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">';
            $html .= '<i class="fa fa-'.$category_icon.'" style="margin-right: 6px; color: #6c757d; font-size: 11px;"></i>'.$category_title;
            $html .= '</h6>';

            // Layout a lista invece di tabella per evitare conflitti
            $html .= '<div style="margin-top: 10px;">';

            foreach ($category_errors as $error) {
                if ($compact_mode) {
                    // Modalità compatta ultra-ottimizzata con font più grande
                    $border_color = $error['type'] === self::ERROR_WARNING ? '#dc3545' :
                                   ($error['type'] === self::ERROR_WARNING ? '#ffc107' : '#17a2b8');

                    $html .= '<div style="background: #f8f9fa; border-left: 3px solid '.$border_color.'; padding: 5px 10px; margin-bottom: 3px; font-size: 12px; display: flex; align-items: center; gap: 8px; min-height: 28px;">';

                    // Campo + Messaggio in una riga compatta
                    $html .= '<div style="display: flex; align-items: center; gap: 6px; flex: 1 1 auto; min-width: 0;">';
                    $html .= '<code style="background: #e83e8c; color: white; padding: 2px 4px; border-radius: 3px; font-size: 9px; font-weight: 600; white-space: nowrap;">'.htmlspecialchars((string) $error['field']).'</code>';
                    $html .= '<span style="font-weight: 600; color: #495057; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">'.htmlspecialchars((string) $error['message']).'</span>';
                    $html .= '</div>';

                    // Valori in formato ultra-compatto con correzione simbolo euro
                    if (!empty($error['xml_value']) && $error['xml_value'] !== '-') {
                        $xml_short = strlen((string) $error['xml_value']) > 15 ? substr((string) $error['xml_value'], 0, 15).'…' : $error['xml_value'];
                        // Correggi &euro; con €
                        $xml_short = str_replace('&euro;', '€', $xml_short);
                        $html .= '<span style="font-size: 10px; color: #6c757d; white-space: nowrap;">XML: <span style="background: #e7f3ff; padding: 2px 4px; border-radius: 2px; color: #495057;">'.htmlspecialchars($xml_short).'</span></span>';
                    }

                    if (!empty($error['gestionale_value']) && $error['gestionale_value'] !== '-') {
                        $gest_short = strlen((string) $error['gestionale_value']) > 15 ? substr((string) $error['gestionale_value'], 0, 15).'…' : $error['gestionale_value'];
                        // Correggi &euro; con €
                        $gest_short = str_replace('&euro;', '€', $gest_short);
                        $html .= '<span style="font-size: 10px; color: #6c757d; white-space: nowrap;">Gest: <span style="background: #f0f8f0; padding: 2px 4px; border-radius: 2px; color: #495057;">'.htmlspecialchars($gest_short).'</span></span>';
                    }

                    $html .= '</div>';
                } else {
                    // Modalità normale: layout a card
                    $html .= '<div style="background: white; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';

                    // Header della card
                    $html .= '<div style="display: flex; align-items: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e9ecef;">';
                    $html .= '<code style="background: #e83e8c; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-right: 12px;">'.htmlspecialchars((string) $error['field']).'</code>';
                    $html .= '<span style="font-weight: 600; color: #495057; font-size: 14px;">'.htmlspecialchars((string) $error['message']).'</span>';
                    $html .= '</div>';

                    // Contenuto della card
                    $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">';

                    $html .= '<div>';
                    $html .= '<div style="font-size: 11px; color: #6c757d; margin-bottom: 4px; font-weight: 600;">XML</div>';
                    $html .= '<div style="background: #e7f3ff; padding: 6px 10px; border-radius: 4px; font-size: 12px; word-break: break-word;">'.htmlspecialchars($error['xml_value'] ?? '-').'</div>';
                    $html .= '</div>';

                    $html .= '<div>';
                    $html .= '<div style="font-size: 11px; color: #6c757d; margin-bottom: 4px; font-weight: 600;">Gestionale</div>';
                    $html .= '<div style="background: #f0f8f0; padding: 6px 10px; border-radius: 4px; font-size: 12px; word-break: break-word;">'.htmlspecialchars($error['gestionale_value'] ?? '-').'</div>';
                    $html .= '</div>';

                    $html .= '</div>';

                    // Suggerimento
                    $html .= '<div style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 8px 12px; font-size: 11px; color: #856404;">';
                    $html .= '<i class="fa fa-lightbulb-o" style="margin-right: 6px;"></i>'.htmlspecialchars((string) $error['suggestion']);
                    $html .= '</div>';

                    $html .= '</div>';
                }
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Restituisce l'icona per il tipo di errore.
     */
    protected function getTypeIcon($type)
    {
        return match ($type) {
            'danger' => '<i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i>',
            'warning' => '<i class="fa fa-exclamation-circle" style="color: #ffc107;"></i>',
            'info' => '<i class="fa fa-info-circle" style="color: #17a2b8;"></i>',
            default => '<i class="fa fa-question-circle"></i>',
        };
    }

    /**
     * Restituisce il titolo della categoria.
     */
    protected function getCategoryTitle($category)
    {
        return match ($category) {
            self::CATEGORY_ANAGRAFICA => tr('Dati Anagrafici'),
            self::CATEGORY_TOTALI => tr('Totali e Importi'),
            self::CATEGORY_DOCUMENTO => tr('Dati Documento'),
            self::CATEGORY_XML_STRUCTURE => tr('Struttura XML'),
            default => tr('Altri'),
        };
    }

    /**
     * Restituisce l'icona della categoria.
     */
    protected function getCategoryIcon($category)
    {
        return match ($category) {
            self::CATEGORY_ANAGRAFICA => 'user',
            self::CATEGORY_TOTALI => 'calculator',
            self::CATEGORY_DOCUMENTO => 'file-text',
            self::CATEGORY_XML_STRUCTURE => 'code',
            default => 'question',
        };
    }

    protected function diff($old, $new)
    {
        $matrix = [];
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) {
            return [['d' => $old, 'i' => $new]];
        }

        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }

    protected function htmlDiff($old, $new)
    {
        $ret = '';
        $diff = $this->diff(preg_split("/[\s]+/", (string) $old), preg_split("/[\s]+/", (string) $new));
        foreach ($diff as $k) {
            if (is_array($k)) {
                $ret .= (!empty($k['d']) ? '<del>'.implode(' ', $k['d']).'</del> ' : '').
                    (!empty($k['i']) ? '<span>'.implode(' ', $k['i']).'</span> ' : '');
            } else {
                $ret .= $k.' ';
            }
        }

        return $ret;
    }
}
