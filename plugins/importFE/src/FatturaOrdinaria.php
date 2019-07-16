<?php

namespace Plugins\ImportFE;

use Modules\Articoli\Articolo as ArticoloOriginale;
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
                'indirizzo' => $sede['Indirizzo'],
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

    public function saveRighe($articoli, $iva, $conto, $movimentazione = true)
    {
        $righe = $this->getRighe();
        $fattura = $this->getFattura();

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            $riga['PrezzoUnitario'] = floatval($riga['PrezzoUnitario']);
            $riga['Quantita'] = floatval($riga['Quantita']);

            if (!empty($articolo)) {
                $obj = Articolo::build($fattura, $articolo);

                $obj->movimentazione($movimentazione);
            } else {
                $obj = Riga::build($fattura);
            }

            $obj->descrizione = $riga['Descrizione'];
            $obj->id_iva = $iva[$key];
            $obj->idconto = $conto[$key];

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
            $obj->prezzo_unitario_vendita = $prezzo;
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
                        'prezzo' => $obj->prezzo_unitario_vendita,
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

                $obj->sconto_unitario = $sconto_unitario;
                $obj->tipo_sconto = $tipo;
            }

            $obj->save();
        }

        // Ricaricamento della fattura
        $fattura->refresh();

        // Arrotondamenti differenti nella fattura XML
        $totali_righe = array_column($righe, 'PrezzoTotale');
        $totale_righe = sum($totali_righe);

        $dati_generali = $this->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
        $totale_documento = $dati_generali['ImportoTotaleDocumento'];

        $diff = $totale_documento ? floatval($totale_documento) - abs($fattura->totale) : $totale_righe - abs($fattura->imponibile_scontato);
        if (!empty($diff)) {
            // Rimozione dell'IVA calcolata automaticamente dal gestionale
            $iva_arrotondamento = database()->fetchOne('SELECT * FROM co_iva WHERE id='.prepare($iva[0]));
            $diff = $diff * 100 / (100 + $iva_arrotondamento['percentuale']);

            $obj = Riga::build($fattura);

            $obj->descrizione = tr('Arrotondamento calcolato in automatico');
            $obj->id_iva = $iva[0];
            $obj->idconto = $conto[0];
            $obj->prezzo_unitario_vendita = round($diff, 4);
            $obj->qta = 1;

            $obj->save();
        }
    }
}
