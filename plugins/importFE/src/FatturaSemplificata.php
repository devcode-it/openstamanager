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
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Riga;
use UnexpectedValueException;
use Util\XML;

/**
 * Classe per la gestione della fatturazione elettronica in XML.
 *
 * @since 2.4.2
 */
class FatturaSemplificata extends FatturaElettronica
{
    public function __construct($name)
    {
        parent::__construct($name);

        if ($this->getHeader()['DatiTrasmissione']['FormatoTrasmissione'] != 'FSM10') {
            throw new UnexpectedValueException();
        }
    }

    public function getAnagrafe()
    {
        $anagrafe = $this->getHeader()['CedentePrestatore'];

        $rea = $anagrafe['IscrizioneREA'];
        $sede = $anagrafe['Sede'];
        $contatti = $anagrafe['Contatti'];

        $info = [
            'partita_iva' => $anagrafe['IdFiscaleIVA']['IdCodice'],
            'codice_fiscale' => $anagrafe['CodiceFiscale'],
            'ragione_sociale' => $anagrafe['Denominazione'],
            'nome' => $anagrafe['Nome'],
            'cognome' => $anagrafe['Cognome'],
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
        ];

        return $info;
    }

    public function getRighe()
    {
        $result = $this->getBody()['DatiBeniServizi'];
        $result = $this->forceArray($result);

        foreach ($result as $index => $item) {
            $result[$index]['Quantita'] = 1;

            if (!empty($item['DatiIVA']['Aliquota'])) {
                $result[$index]['AliquotaIVA'] = $item['DatiIVA']['Aliquota'];
            } else {
                $imposta = floatval($item['DatiIVA']['Imposta']);
                $importo = floatval($item['Importo']);

                $prezzo = $importo - $imposta;

                $aliquota = !empty($prezzo) ? $imposta / $prezzo * 100 : 0;
                $result[$index]['AliquotaIVA'] = $aliquota;
            }
        }

        return $result;
    }

    public function saveRighe($articoli, $iva, $conto, $movimentazione = true, $crea_articoli = false, $tipi_riferimenti = [], $id_riferimenti = [])
    {
        $righe = $this->getRighe();
        $fattura = $this->getFattura();

        foreach ($righe as $key => $riga) {
            $articolo = ArticoloOriginale::find($articoli[$key]);

            $imposta = floatval($riga['DatiIVA']['Imposta']);
            $importo = floatval($riga['Importo']);

            $prezzo_non_ivato = $importo - $imposta;
            $riga['Importo'] = !empty($prezzo_non_ivato) ? $prezzo_non_ivato : $importo;

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
            $prezzo = $riga['Importo'];
            $prezzo = $prezzo < 0 ? -$prezzo : $prezzo;
            $qta = 1;
            $qta = $riga['Importo'] < 0 ? -$qta : $qta;

            if ($fattura->isNota()) {
                $qta = -$qta;
            }

            // Prezzo e quantità
            $obj->prezzo_unitario = $prezzo;
            $obj->qta = $qta;

            $obj->save();
        }
    }
}
