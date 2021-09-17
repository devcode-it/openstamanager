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

namespace Modules\Fatture\Gestori;

use Modules\Fatture\Fattura;
use Modules\Scadenzario\Gruppo;
use Modules\Scadenzario\Scadenza;
use Plugins\ImportFE\FatturaElettronica as FatturaElettronicaImport;
use Util\XML;

/**
 * Classe dedicata alla gestione delle procedure di registrazione delle Scadenze di pagamento per una Fattura, con relativo supporto alla Fatturazione Elettronica per permettere l'importazione delle scadenze eventualmente registrate.
 *
 * @since 2.4.17
 */
class Scadenze
{
    /**
     * @var Fattura
     */
    private $fattura;

    /**
     * @var Gruppo
     */
    private $gruppo;

    public function __construct(Fattura $fattura)
    {
        $this->fattura = $fattura;
    }

    public function getGruppo(){
        if (isset($this->gruppo)){
            return $this->gruppo;
        }

        // Ricerca Gruppo Scadenza associato alla Fattura
        $this->gruppo = Gruppo::where('id_documento', '=', $this->fattura->id)->first();

        // Generazione Gruppo Scadenza associato alla Fattura
        if (empty($this->gruppo)){
            $this->gruppo = Gruppo::build($this->fattura->getReference(), $this->fattura);
        }

        return $this->gruppo;
    }

    /**
     * Registra le scadenze della fattura.
     *
     * @param bool $is_pagato
     * @param bool $ignora_fe
     */
    public function registra($is_pagato = false, $ignora_fe = false)
    {
        // Rimozione degli elementi pre-esistenti
        $this->rimuovi();

        if (!$ignora_fe && $this->fattura->module == 'Fatture di acquisto' && $this->fattura->isFE()) {
            $scadenze_fe = $this->registraScadenzeFE($is_pagato);
        }

        if (empty($scadenze_fe)) {
            $this->registraScadenzeTradizionali($is_pagato);
        }

        // Registrazione scadenza per Ritenuta d'Acconto
        // Inversione di segno per le note
        $ritenuta_acconto = $this->fattura->ritenuta_acconto;
        $ritenuta_acconto = $this->fattura->isNota() ? -$ritenuta_acconto : $ritenuta_acconto;

        if (!empty($this->fattura->sconto_finale_percentuale)) {
            $ritenuta_acconto = $ritenuta_acconto * (1 - $this->fattura->sconto_finale_percentuale / 100);
        }

        $direzione = $this->fattura->tipo->dir;
        $is_ritenuta_pagata = $this->fattura->is_ritenuta_pagata;

        // Se c'è una ritenuta d'acconto, la aggiungo allo scadenzario al 15 del mese dopo l'ultima scadenza di pagamento
        if ($direzione == 'uscita' && $ritenuta_acconto > 0 && empty($is_ritenuta_pagata)) {
            $ultima_scadenza = $this->fattura->scadenze->last();
            $scadenza = $ultima_scadenza->scadenza->copy()->startOfMonth()->addMonth();
            $scadenza->setDate($scadenza->year, $scadenza->month, 15);

            $importo = -$ritenuta_acconto;

            $this->registraScadenza($importo, $scadenza, $is_pagato, 'ritenutaacconto');
        }
    }

    /**
     * Elimina le scadenze della fattura.
     */
    public function rimuovi()
    {
        $this->getGruppo()->rimuoviScadenze();
    }

    /**
     * Registra una specifica scadenza nel database.
     *
     * @param float  $importo
     * @param string $data_scadenza
     * @param bool   $is_pagato
     * @param string $tipo
     */
    protected function registraScadenza($importo, $data_scadenza, $is_pagato, $tipo = 'fattura')
    {
        return Scadenza::build($this->getGruppo(), $importo, $data_scadenza, $tipo, $is_pagato);
    }

    /**
     * Registra le scadenze della fattura elettronica collegata al documento.
     *
     * @param bool $is_pagato
     *
     * @return bool
     */
    protected function registraScadenzeFE($is_pagato = false)
    {
        $xml = XML::read($this->fattura->getXML());

        $fattura_body = $xml['FatturaElettronicaBody'];

        // Gestione per fattura elettroniche senza pagamento definito
        $pagamenti = [];
        if (isset($fattura_body['DatiPagamento'])) {
            $pagamenti = $fattura_body['DatiPagamento'];
            $pagamenti = isset($pagamenti[0]) ? $pagamenti : [$pagamenti];
        }

        foreach ($pagamenti as $pagamento) {
            $rate = $pagamento['DettaglioPagamento'];
            $rate = isset($rate[0]) ? $rate : [$rate];

            foreach ($rate as $rata) {
                $scadenza = !empty($rata['DataScadenzaPagamento']) ? FatturaElettronicaImport::parseDate($rata['DataScadenzaPagamento']) : $this->fattura->data;
                $importo = $this->fattura->isNota() ? $rata['ImportoPagamento'] : -$rata['ImportoPagamento'];

                $this->registraScadenza($importo, $scadenza, $is_pagato);
            }
        }

        return !empty($pagamenti);
    }

    /**
     * Registra le scadenze tradizionali del gestionale.
     *
     * @param bool $is_pagato
     */
    protected function registraScadenzeTradizionali($is_pagato = false)
    {
        // Inversione di segno per le note
        $netto = $this->fattura->netto;
        $netto = $this->fattura->isNota() ? -$netto : $netto;

        // Calcolo delle rate
        $rate = $this->fattura->pagamento->calcola($netto, $this->fattura->data);
        $direzione = $this->fattura->tipo->dir;

        foreach ($rate as $rata) {
            $scadenza = $rata['scadenza'];
            $importo = $direzione == 'uscita' ? -$rata['importo'] : $rata['importo'];

            $this->registraScadenza($importo, $scadenza, $is_pagato);
        }
    }
}
