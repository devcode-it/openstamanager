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
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;

/**
 * Classe indirizzata alla gestione dei Movimenti automatici (non contabili, ovvero non di Prima Nota) associati a una Fattura.
 *
 * @since 2.4.17
 */
class Movimenti
{
    protected $fattura;
    protected $mastrino = null;

    public function __construct(Fattura $fattura)
    {
        $this->fattura = $fattura;
    }

    public function getMastrino()
    {
        if (!isset($this->mastrino)) {
            $this->mastrino = Mastrino::where('iddocumento', $this->fattura->id)
                ->where('primanota', false)
                ->first();
        }

        return $this->mastrino;
    }

    public function generateMastrino()
    {
        $descrizione = $this->fattura->getReference();
        $data = $this->fattura->data_competenza;

        $mastrino = Mastrino::build($descrizione, $data, false, false);
        $this->mastrino = $mastrino;

        return $this->mastrino;
    }

    /**
     * Registra i movimenti relativi alla fattura.
     */
    public function registra()
    {
        // Rimozione degli elementi pre-esistenti
        $this->rimuovi();

        $movimenti = [];

        // Informazioni generali sul documento
        $direzione = $this->fattura->direzione;
        $is_acquisto = $direzione == 'uscita';
        $split_payment = $this->fattura->split_payment;
        $is_nota = $this->fattura->isNota();

        // Totali utili per i movimenti
        $totale = $this->fattura->totale;
        $iva_indetraibile = $this->fattura->iva_indetraibile;
        $iva_detraibile = $this->fattura->iva - $iva_indetraibile;

        // Inversione di segno per le note
        $totale = $is_nota ? -$totale : $totale;
        $iva_indetraibile = $is_nota ? -$iva_indetraibile : $iva_indetraibile;
        $iva_detraibile = $is_nota ? -$iva_detraibile : $iva_detraibile;

        /*
         * 1) Movimento relativo al conto dell'anagrafica del documento
         *
         * Totale (Split Payment disabilitato), oppure Totale - IVA detraibile (Split Payment abilitato) -> DARE per Vendita, AVERE per Acquisto
         */
        $anagrafica = $this->fattura->anagrafica;

        $id_conto = $is_acquisto ? $anagrafica->idconto_fornitore : $anagrafica->idconto_cliente;
        if (empty($id_conto)) {
            $id_conto = $is_acquisto ? setting('Conto per Riepilogativo fornitori') : setting('Conto per Riepilogativo clienti');
        }
        $id_conto_controparte = $id_conto; // Salvataggio del conto dell'anagrafica per usi successivi

        $importo_anagrafica = $totale;
        if ($split_payment) {
            $importo_anagrafica = sum($importo_anagrafica, -$iva_detraibile, 2);
        }

        $movimenti[] = [
            'id_conto' => $id_conto,
            'dare' => $importo_anagrafica,
        ];

        /*
         * 2) Movimento per ogni riga del documento
         * Imponibile -> AVERE per Vendita, DARE per Acquisto
         */
        $righe = $this->fattura->getRighe();
        foreach ($righe as $riga) {
            // Retro-compatibilità per versioni <= 2.4
            $id_conto = $riga->id_conto ?: $this->fattura->idconto;

            $imponibile = $riga->imponibile;
            $imponibile = $is_nota ? -$imponibile : $imponibile; // Inversione di segno per le note
            if (!empty($imponibile)) {
                $movimenti[] = [
                    'id_conto' => $id_conto,
                    'avere' => $imponibile,
                ];
            }
        }

        /*
         * 3) IVA detraibile sul relativo conto (Split Payment disabilitato)
         * IVA detraibile -> AVERE per Vendita, DARE per Acquisto
         */
        if (!empty($iva_detraibile) && empty($split_payment)) {
            $id_conto = $is_acquisto ? setting('Conto per Iva su acquisti') : setting('Conto per Iva su vendite');
            $movimenti[] = [
                'id_conto' => $id_conto,
                'avere' => $iva_detraibile,
            ];
        }

        /*
        * 4) IVA indetraibile sul relativo conto (Split Payment disabilitato)
        * IVA indetraibile -> AVERE per Vendita, DARE per Acquisto
        */
        if (!empty($iva_indetraibile) && empty($split_payment)) {
            $id_conto = setting('Conto per Iva indetraibile');
            $movimenti[] = [
                'id_conto' => $id_conto,
                'avere' => $iva_indetraibile,
            ];
        }

        /*
        * 5) Rivalsa INPS sul relativo conto
        * Rivalsa INPS (senza IVA) -> AVERE per Vendita, DARE per Acquisto
        */
        $rivalsa_inps = $this->fattura->rivalsa_inps;
        $rivalsa_inps = $is_nota ? -$rivalsa_inps : $rivalsa_inps; // Inversione di segno per le note
        if (!empty($rivalsa_inps)) {
            $id_conto = setting('Conto per Erario c/INPS');
            $movimenti[] = [
                'id_conto' => $id_conto,
                'avere' => $rivalsa_inps,
            ];
        }

        /*
        * 6) Ritenuta d'acconto
        * Conto "Conto per Erario c/ritenute d'acconto": DARE per Vendita, AVERE per Acquisto
        * Conto della controparte: AVERE per Vendita, DARE per Acquisto
        */
        $ritenuta_acconto = $this->fattura->ritenuta_acconto;
        $ritenuta_acconto = $is_nota ? -$ritenuta_acconto : $ritenuta_acconto; // Inversione di segno per le note
        if (!empty($ritenuta_acconto)) {
            $id_conto = setting("Conto per Erario c/ritenute d'acconto");
            $movimenti[] = [
                'id_conto' => $id_conto,
                'dare' => $ritenuta_acconto,
            ];

            $movimenti[] = [
                'id_conto' => $id_conto_controparte,
                'avere' => $ritenuta_acconto,
            ];
        }

        /*
        * 7) Ritenuta contributi
        * Conto "Conto per Erario c/enasarco": DARE per Vendita, AVERE per Acquisto
        * Conto della controparte: AVERE per Vendita, DARE per Acquisto
        */
        $ritenuta_contributi = $this->fattura->totale_ritenuta_contributi;
        $ritenuta_contributi = $is_nota ? -$ritenuta_contributi : $ritenuta_contributi; // Inversione di segno per le note
        if (!empty($ritenuta_contributi)) {
            $id_conto = setting('Conto per Erario c/enasarco');
            $movimenti[] = [
                'id_conto' => $id_conto,
                'dare' => $ritenuta_contributi,
            ];

            $movimenti[] = [
                'id_conto' => $id_conto_controparte,
                'avere' => $ritenuta_contributi,
            ];
        }

        // Inversione contabile per i documenti di acquisto
        if ($is_acquisto) {
            foreach ($movimenti as $key => $movimento) {
                $movimenti[$key]['avere'] = $movimento['dare'];
                $movimenti[$key]['dare'] = $movimento['avere'];
            }
        }

        // Registrazione dei singoli Movimenti nel relativo Mastrino
        $mastrino = $this->generateMastrino();
        foreach ($movimenti as $element) {
            $movimento = Movimento::build($mastrino, $element['id_conto'], $this->fattura);
            $movimento->setTotale($element['avere'] ?: 0, $element['dare'] ?: 0);
            $movimento->save();
        }
    }

    /**
     * Elimina i movimenti del mastrino relativo alla fattura.
     */
    public function rimuovi()
    {
        $mastrino = $this->getMastrino();

        if (!empty($mastrino)) {
            $mastrino->delete();
        }
    }
}
