<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Articoli\Categoria;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use UnexpectedValueException;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaOrdinaria extends FatturaElettronica
{
    public function __construct($name)
    {
        parent::__construct($name);

        if ($this->getHeader()['DatiTrasmissione']['FormatoTrasmissione'] == 'FSM10') {
            throw new UnexpectedValueException();
        }
    }

    public function getAnagrafe()
    {
        $dati = $this->getHeader()['CedentePrestatore'];

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
                'codice' => $rea['Ufficio'].'-'.$rea['NumeroREA'],
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
        $result = $this->getBody()['DatiBeniServizi']['DettaglioLinee'];

        // Aggiunta degli arrotondamenti IVA come righe indipendenti
        $riepolighi = $this->getBody()['DatiBeniServizi']['DatiRiepilogo'];
        foreach ($riepolighi as $riepilogo) {
            if (!empty($riepilogo['Arrotondamento'])) {
                $descrizione = tr('Arrotondamento IVA _VALUE_', [
                    '_VALUE_' => empty($riepilogo['Natura']) ? numberFormat($riepilogo['AliquotaIVA']).'%' : $riepilogo['Natura'],
                ]);

                $result[] = [
                    'Descrizione' => $descrizione,
                    'PrezzoUnitario' => $riepilogo['Arrotondamento'],
                    'Quantita' => 1,
                    'AliquotaIVA' => $riepilogo['AliquotaIVA'],
                    'Natura' => $riepilogo['Natura'],
                ];
            }
        }

        return $this->forceArray($result);
    }

    public function saveRighe($articoli, $iva, $conto, $movimentazione = true, $crea_articoli = false, $tipi_riferimenti = [], $id_riferimenti = [])
    {
        $info = $this->getRitenutaRivalsa();

        $righe = $this->getRighe();
        $fattura = $this->getFattura();

        $id_ritenuta_acconto = $info['id_ritenuta_acconto'];
        $id_rivalsa = $info['id_rivalsa'];
        $calcolo_ritenuta_acconto = $info['rivalsa_in_ritenuta'] ? 'IMP+RIV' : 'IMP';
        $ritenuta_contributi = !empty($fattura->id_ritenuta_contributi);

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            $riga['PrezzoUnitario'] = floatval($riga['PrezzoUnitario']);
            $riga['Quantita'] = floatval($riga['Quantita']);

            $codici = $riga['CodiceArticolo'] ?: [];
            $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

            // Creazione articolo relativo
            if (!empty($codici) && !empty($crea_articoli) && empty($articolo)) {
                $codice = $codici[0]['CodiceValore'];
                $articolo = ArticoloOriginale::where('codice', $codice)->first();

                if (empty($articolo)) {
                    $nome_categoria = 'Importazione automatica';
                    $categoria = Categoria::where('nome', $nome_categoria)->first();
                    if (empty($categoria)) {
                        $categoria = Categoria::build($nome_categoria);
                    }

                    $articolo = ArticoloOriginale::build($codice, $riga['Descrizione'], $categoria);
                    $articolo->prezzo_acquisto = $riga['PrezzoUnitario'];
                    $articolo->id_fornitore = $fattura->idanagrafica;
                    $articolo->save();
                }
            }

            if (!empty($articolo)) {
                $obj = Articolo::build($fattura, $articolo);

                $obj->movimentazione($movimentazione);
            } else {
                $obj = Riga::build($fattura);
            }

            $obj->descrizione = $riga['Descrizione'];

            // Collegamento al documento di riferimento
            if (!empty($tipi_riferimenti[$key])) {
                list($riferimento_precedente, $nuovo_riferimento) = $obj->impostaOrigine($tipi_riferimenti[$key], $id_riferimenti[$key]);

                // Correzione della descrizione
                $obj->descrizione = str_replace($riferimento_precedente, '', $obj->descrizione);
                $obj->descrizione .= $nuovo_riferimento;
            }

            $obj->id_iva = $iva[$key];
            $obj->idconto = $conto[$key];

            $obj->id_rivalsa_inps = $id_rivalsa;
            $obj->ritenuta_contributi = $ritenuta_contributi;

            if (!empty($riga['Ritenuta'])) {
                $obj->id_ritenuta_acconto = $id_ritenuta_acconto;
                $obj->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            }

            // Nel caso il prezzo sia negativo viene gestito attraverso l'inversione della quantità (come per le note di credito)
            // TODO: per migliorare la visualizzazione, sarebbe da lasciare negativo il prezzo e invertire gli sconti.
            $prezzo = $riga['PrezzoUnitario'];
            $prezzo = $riga['PrezzoUnitario'] < 0 ? -$prezzo : $prezzo;
            $qta = $riga['Quantita'] ?: 1;
            $qta = $riga['PrezzoUnitario'] < 0 ? -$qta : $qta;

            if ($fattura->isNota()) {
                $qta = -$qta;
            }

            // Prezzo e quantità
            $obj->prezzo_unitario = $prezzo;
            $obj->qta = $qta;

            if (!empty($riga['UnitaMisura'])) {
                $obj->um = $riga['UnitaMisura'];
            }

            // Sconti e maggiorazioni
            $sconti = $riga['ScontoMaggiorazione'];
            if (!empty($sconti)) {
                $sconti = $sconti[0] ? $sconti : [$sconti];
                $tipo = !empty($sconti[0]['Percentuale']) ? 'PRC' : 'UNT';

                $lista = [];
                foreach ($sconti as $sconto) {
                    $unitario = $sconto['Percentuale'] ?: $sconto['Importo'];

                    // Sconto o Maggiorazione
                    $lista[] = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;
                }

                if ($tipo == 'PRC') {
                    $elenco = implode('+', $lista);
                    $sconto = calcola_sconto([
                        'sconto' => $elenco,
                        'prezzo' => $obj->prezzo_unitario,
                        'tipo' => 'PRC',
                        'qta' => $obj->qta,
                    ]);

                    /*
                     * Trasformazione di sconti multipli in sconto percentuale combinato.
                     * Esempio: 40% + 30% è uno sconto del 42%.
                     */
                    $sconto_unitario = $sconto * 100 / $obj->imponibile;
                } else {
                    $sconto_unitario = sum($lista);
                }

                $obj->setSconto($sconto_unitario, $tipo);
            }

            $obj->save();
        }

        // Ricaricamento della fattura
        $fattura->refresh();

        // Arrotondamenti differenti nella fattura XML
        $totali_righe = array_column($righe, 'PrezzoTotale');
        $totale_righe = sum($totali_righe);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $totale_documento = floatval($dati_generali['ImportoTotaleDocumento']);

        $diff = $totale_documento ? abs($totale_documento) - abs($fattura->totale) : abs($totale_righe) - abs($fattura->totale_imponibile);
        if (!empty($diff)) {
            // Rimozione dell'IVA calcolata automaticamente dal gestionale
            $iva_arrotondamento = database()->fetchOne('SELECT * FROM co_iva WHERE id='.prepare($iva[0]));
            $diff = $diff * 100 / (100 + $iva_arrotondamento['percentuale']);

            $obj = Riga::build($fattura);

            $obj->descrizione = tr('Arrotondamento calcolato in automatico');
            $obj->id_iva = $iva[0];
            $obj->idconto = $conto[0];
            $obj->prezzo_unitario = round($diff, 4);
            $obj->qta = 1;

            $obj->save();
        }
    }

    protected function prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura)
    {
        $fattura = parent::prepareFattura($id_tipo, $data, $id_sezionale, $ref_fattura);
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

                        preg_match('/^(.+?) - (.+?) \((.+?)%\)$/', trim($descrizione), $m);

                        $nome = ucwords(strtolower($m[2]));
                        $percentuale = $m[3];

                        $totale_previsto = round($importo / $percentuale * 100, 2);
                        $percentuale_importo = round($totale_previsto / $totale * 100, 2);

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
            $totali = array_column($righe, 'PrezzoTotale');
            $totale = sum($totali);

            $casse = isset($casse[0]) ? $casse : [$casse];

            $importi = [];
            foreach ($casse as $cassa) {
                $importi[] = floatval($cassa['ImportoContributoCassa']);
                if ($cassa['Ritenuta']) {
                    $rivalsa_in_ritenuta = true;
                }
            }
            $importo = sum($importi);

            $percentuale = round($importo / $totale * 100, 2);

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

        // Ritenuta d'Acconto
        $ritenuta = $dati_generali['DatiRitenuta'];
        if (!empty($ritenuta)) {
            $totali = [];
            foreach ($righe as $riga) {
                if (!empty($riga['Ritenuta'])) {
                    $totali[] = $riga['PrezzoTotale'];
                }
            }
            $totale = sum($totali);

            $percentuale = floatval($ritenuta['AliquotaRitenuta']);
            $importo = floatval($ritenuta['ImportoRitenuta']);

            $totale_previsto = round($importo / $percentuale * 100, 2);
            $percentuale_importo = round($totale_previsto / $totale * 100, 2);
            $percentuale_importo = min($percentuale_importo, 100); // Nota: Fix per la percentuale che superava il 100% nel caso di importi con Rivalsa compresa

            $ritenuta_acconto = $database->fetchOne('SELECT * FROM`co_ritenutaacconto` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));
            if (empty($ritenuta_acconto)) {
                $descrizione = tr('Ritenuta _PRC_% sul _TOT_%', [
                    '_PRC_' => numberFormat($percentuale),
                    '_TOT_' => numberFormat($percentuale_importo),
                ]);

                $database->query('INSERT INTO `co_ritenutaacconto` (`descrizione`, `percentuale`, `percentuale_imponibile`) VALUES ('.prepare($descrizione).', '.prepare($percentuale).', '.prepare($percentuale_importo).')');
            }

            $ritenuta_acconto = $database->fetchOne('SELECT * FROM`co_ritenutaacconto` WHERE `percentuale` = '.prepare($percentuale).' AND `percentuale_imponibile` = '.prepare($percentuale_importo));

            $id_ritenuta_acconto = $ritenuta_acconto['id'];
        }

        return [
            'id_ritenuta_acconto' => $id_ritenuta_acconto,
            'id_rivalsa' => $id_rivalsa,
            'rivalsa_in_ritenuta' => $rivalsa_in_ritenuta,
        ];
    }
}
