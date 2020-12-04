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

namespace Modules\Fatture\Gestori;

use Modules\Fatture\Fattura;
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
    private $fattura;

    public function __construct(Fattura $fattura)
    {
        $this->fattura = $fattura;
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

        $direzione = $this->fattura->tipo->dir;
        $ritenuta_acconto = $this->fattura->ritenuta_acconto;

        // Se c'è una ritenuta d'acconto, la aggiungo allo scadenzario al 15 del mese dopo l'ultima scadenza di pagamento
        if ($direzione == 'uscita' && $ritenuta_acconto > 0) {
            $ultima_scadenza = $this->fattura->scadenze->last();
            $scadenza = $ultima_scadenza->scadenza->copy()->startOfMonth()->addMonth();
            $scadenza->setDate($scadenza->year, $scadenza->month, 15);

            $importo = -$ritenuta_acconto;

            self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato, 'ritenutaacconto');
        }
    }

    /**
     * Elimina le scadenze della fattura.
     */
    public function rimuovi()
    {
        database()->delete('co_scadenziario', ['iddocumento' => $this->fattura->id]);
    }

    /**
     * Registra una specifica scadenza nel database.
     *
     * @param float  $importo
     * @param string $data_scadenza
     * @param bool   $is_pagato
     * @param string $type
     */
    protected function registraScadenza(Fattura $fattura, $importo, $data_scadenza, $is_pagato, $type = 'fattura')
    {
        $numero = $fattura->numero_esterno ?: $fattura->numero;
        $descrizione = $fattura->tipo->descrizione.' numero '.$numero;

        $scadenza = Scadenza::build($descrizione, $importo, $data_scadenza, $type, $is_pagato);

        $scadenza->documento()->associate($fattura);
        $scadenza->data_emissione = $fattura->data;

        $scadenza->save();
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

        $pagamenti = $xml['FatturaElettronicaBody']['DatiPagamento'];
        $pagamenti = isset($pagamenti[0]) ? $pagamenti : [$pagamenti];
        foreach ($pagamenti as $pagamento) {
            $rate = $pagamento['DettaglioPagamento'];
            $rate = isset($rate[0]) ? $rate : [$rate];

            foreach ($rate as $rata) {
                $scadenza = !empty($rata['DataScadenzaPagamento']) ? FatturaElettronicaImport::parseDate($rata['DataScadenzaPagamento']) : $this->fattura->data;
                $importo = ($this->fattura->isNota()) ? $rata['ImportoPagamento'] : -$rata['ImportoPagamento'];

                self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato);
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
        $rate = $this->fattura->pagamento->calcola($this->fattura->netto, $this->fattura->data);
        $direzione = $this->fattura->tipo->dir;

        foreach ($rate as $rata) {
            $scadenza = $rata['scadenza'];
            $importo = $direzione == 'uscita' ? -$rata['importo'] : $rata['importo'];

            self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato);
        }
    }
}
