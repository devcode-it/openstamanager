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
use Modules\Scadenzario\Scadenza;
use Plugins\AssicurazioneCrediti\AssicurazioneCrediti;
use Plugins\ImportFE\FatturaElettronica as FatturaElettronicaImport;
use Util\XML;

/**
 * Classe dedicata alla gestione delle procedure di registrazione delle Scadenze di pagamento per una Fattura, con relativo supporto alla Fatturazione Elettronica per permettere l'importazione delle scadenze eventualmente registrate.
 *
 * @since 2.4.17
 */
class Scadenze
{
    public function __construct(private readonly Fattura $fattura)
    {
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
            $ultima_scadenza = $this->fattura->scadenze()->orderBy('scadenza', 'desc')->first();
            $scadenza = $ultima_scadenza->scadenza->copy()->startOfMonth()->addMonth();
            $scadenza->setDate($scadenza->year, $scadenza->month, 15);
            $id_pagamento = $this->fattura->idpagamento;
            $id_banca_azienda = $this->fattura->id_banca_azienda;
            $id_banca_controparte = $this->fattura->id_banca_controparte;
            $importo = -$ritenuta_acconto;

            self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato, $id_pagamento, $id_banca_azienda, $id_banca_controparte, 'ritenutaacconto');
        }
    }

    /**
     * Elimina le scadenze della fattura.
     */
    public function rimuovi()
    {
        $scadenze = $this->fattura->scadenze;
        foreach ($scadenze as $scadenza) {
            $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $scadenza->idanagrafica)->where('data_inizio', '<=', $scadenza->scadenza)->where('data_fine', '>=', $scadenza->scadenza)->first();
            if (!empty($assicurazione_crediti)) {
                $assicurazioni[] = $assicurazione_crediti;
            }
        }

        database()->delete('co_scadenziario', ['iddocumento' => $this->fattura->id]);

        foreach ($assicurazioni as $assicurazione) {
            $assicurazione->fixTotale();
            $assicurazione->save();
        }
    }

    /**
     * Registra una specifica scadenza nel database.
     *
     * @param float  $importo
     * @param string $data_scadenza
     * @param bool   $is_pagato
     * @param string $type
     */
    protected function registraScadenza(Fattura $fattura, $importo, $data_scadenza, $is_pagato, $id_pagamento, $id_banca_azienda, $id_banca_controparte, $type = 'fattura')
    {
        $numero = $fattura->numero_esterno ?: $fattura->numero;
        $descrizione = $fattura->tipo->getTranslation('title').' numero '.$numero;
        $idanagrafica = $fattura->idanagrafica;

        $scadenza = Scadenza::build($idanagrafica, $descrizione, $importo, $data_scadenza, $id_pagamento, $id_banca_azienda, $id_banca_controparte, $type, $is_pagato);

        $scadenza->documento()->associate($fattura);
        $scadenza->data_emissione = $fattura->data;

        $scadenza->save();

        $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $scadenza->idanagrafica)->where('data_inizio', '<=', $scadenza->scadenza)->where('data_fine', '>=', $scadenza->scadenza)->first();
        if (!empty($assicurazione_crediti)) {
            $assicurazione_crediti->fixTotale();
            $assicurazione_crediti->save();
        }
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
            $id_banca_azienda = $this->fattura->id_banca_azienda;
            $id_banca_controparte = $this->fattura->id_banca_controparte;
            $id_pagamento = $this->fattura->idpagamento;

            foreach ($rate as $rata) {
                $scadenza = !empty($rata['DataScadenzaPagamento']) ? FatturaElettronicaImport::parseDate($rata['DataScadenzaPagamento']) : $this->fattura->data;
                $importo = $this->fattura->isNota() ? $rata['ImportoPagamento'] : -$rata['ImportoPagamento'];

                self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato, $id_pagamento, $id_banca_azienda, $id_banca_controparte);
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
        $rate = $this->fattura->pagamento->calcola($netto, $this->fattura->data, $this->fattura->idanagrafica);
        $direzione = $this->fattura->tipo->dir;

        foreach ($rate as $rata) {
            $scadenza = $rata['scadenza'];
            $importo = $direzione == 'uscita' ? -$rata['importo'] : $rata['importo'];
            $id_pagamento = $this->fattura->idpagamento;
            $id_banca_azienda = $this->fattura->id_banca_azienda;
            $id_banca_controparte = $this->fattura->id_banca_controparte;

            self::registraScadenza($this->fattura, $importo, $scadenza, $is_pagato, $id_pagamento, $id_banca_azienda, $id_banca_controparte);
        }
    }
}
