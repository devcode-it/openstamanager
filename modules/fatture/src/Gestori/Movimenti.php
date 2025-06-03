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
use Modules\Iva\Aliquota;
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
    protected $mastrino;

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
        $descrizione = $this->fattura->getReference(1);
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
        if ($totale == 0) {
            return;
        }

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
        // Per le fatture con split payment, l'importo dell'anagrafica è sempre il totale senza IVA
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
            $id_conto = $riga->idconto ?: $this->fattura->idconto;

            $indetraibile = $riga->iva_indetraibile;
            $imponibile = $riga->totale_imponibile + $indetraibile;
            $imponibile = $is_nota ? -$imponibile : $imponibile; // Inversione di segno per le note

            $rivalsa_inps = $is_nota ? -$riga->rivalsa_inps : $riga->rivalsa_inps;

            if ($this->fattura->direzione == 'uscita') {
                $imponibile = sum($imponibile, $rivalsa_inps);
            }

            if (!empty($imponibile)) {
                $movimenti[] = [
                    'id_conto' => $id_conto,
                    'avere' => $imponibile,
                ];
            }
        }

        /*
         * 3) IVA detraibile sul relativo conto
         * IVA detraibile -> AVERE per Vendita, DARE per Acquisto
         */
        if (!empty($iva_detraibile)) {
            // Caso 1: Fattura di acquisto con split payment - IVA su vendite
            if ($is_acquisto && $split_payment) {
                $id_conto = setting('Conto per Iva su vendite');
                $movimenti[] = [
                    'id_conto' => $id_conto,
                    'avere' => $iva_detraibile,
                ];

                // Aggiunta dell'IVA al conto di costo per fatture di acquisto con split payment
                foreach ($righe as $riga) {
                    $id_conto = $riga->idconto ?: $this->fattura->idconto;
                    $iva_riga = $riga->iva;

                    if (!empty($iva_riga)) {
                        $movimenti[] = [
                            'id_conto' => $id_conto,
                            'avere' => $iva_riga,
                        ];
                    }
                }
            }
            // Caso 2: Fattura senza split payment - IVA normale (acquisti o vendite)
            elseif (empty($split_payment)) {
                $id_conto = $is_acquisto ? setting('Conto per Iva su acquisti') : setting('Conto per Iva su vendite');
                $movimenti[] = [
                    'id_conto' => $id_conto,
                    'avere' => $iva_detraibile,
                ];
            }
            // Caso 3: Fattura di vendita con split payment - Non si registra l'IVA
        }

        /*
        * 5) Rivalsa INPS sul relativo conto
        * Rivalsa INPS (senza IVA) -> AVERE per Vendita, DARE per Acquisto
        */
        if (!empty($rivalsa_inps) && $this->fattura->direzione == 'entrata') {
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
        * 7) Ritenuta previdenziale
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
        $totale_dare = 0;
        $totale_avere = 0;

        $mastrino = $this->generateMastrino();

        foreach ($movimenti as $element) {
            $dare = $element['dare'] ? round($element['dare'], 8) : 0;
            $avere = $element['avere'] ? round($element['avere'], 8) : 0;

            $totale_dare += $dare;
            $totale_avere += $avere;

            $movimento = Movimento::build($mastrino, $element['id_conto'], $this->fattura);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        if ($is_acquisto && !$is_nota) {
            $is_fornitore_italiano = $this->fattura->anagrafica->nazione && $this->fattura->anagrafica->nazione->iso2 == 'IT';

            $righe_reverse_charge = [];
            $totale_imponibile_reverse = 0;
            $aliquota_iva = null;

            foreach ($this->fattura->getRighe() as $riga) {
                if ($riga->aliquota != null && $riga->aliquota->codice_natura_fe !== null && str_starts_with($riga->aliquota->codice_natura_fe, 'N6')) {
                    $righe_reverse_charge[] = $riga;
                    $totale_imponibile_reverse += $riga->totale_imponibile;

                    if ($aliquota_iva === null) {
                        $aliquota_iva = Aliquota::find(setting('Iva predefinita'));
                    }
                }
            }

            if ($is_fornitore_italiano && !empty($righe_reverse_charge) && $aliquota_iva) {
                $importo_iva = $totale_imponibile_reverse * $aliquota_iva->percentuale / 100;

                $movimento = Movimento::build($mastrino, setting('Conto per Iva su vendite'), $this->fattura);
                $movimento->setTotale(0, $importo_iva);
                $movimento->save();

                $movimento = Movimento::build($mastrino, setting('Conto per Iva su acquisti'), $this->fattura);
                $movimento->setTotale($importo_iva, 0);
                $movimento->save();
            }
        }

        /*
         * Controllo per giroconto IVA su fatture di acquisto
         * Se la data di registrazione è in un mese successivo alla data di competenza
         * e il giorno è minore del 16, viene effettuato un giroconto per spostare l'IVA a credito
         */
        if ($is_acquisto && !$is_nota && !empty($iva_detraibile)) {
            $data_registrazione = \Carbon\Carbon::parse($this->fattura->data_registrazione);
            $data_competenza = \Carbon\Carbon::parse($this->fattura->data_competenza);

            // Verifica se il mese della data di registrazione è successivo al mese della data di competenza
            // e se il giorno della data di registrazione è minore del 16
            $mese_registrazione_successivo = false;

            if (($data_registrazione->year == $data_competenza->year && $data_registrazione->month > $data_competenza->month && $data_registrazione->day >= 16) || ($data_registrazione->year > $data_competenza->year)) {
                $mese_registrazione_successivo = true;
            }

            if ($mese_registrazione_successivo) {
                // Data del giroconto: primo giorno del mese successivo alla data di registrazione
                $data_giroconto = $this->fattura->data_registrazione;
                $descrizione_giroconto = 'Giroconto IVA al mese successivo';
            

                if (isset($data_giroconto)) {
                    // Crea un mastrino per il giroconto con data di competenza (per lo storno)
                    $mastrino_storno = Mastrino::build('Storno IVA da data competenza - ' . $this->fattura->getReference(1), $this->fattura->data_competenza, false, false);

                    // Movimento di storno: Azzera l'IVA dalla data di competenza (DARE)
                    $movimento_storno = Movimento::build($mastrino_storno, setting('Conto per Iva transitoria'), $this->fattura);
                    $movimento_storno->setTotale(0, $iva_detraibile);
                    $movimento_storno->descrizione = 'Storno IVA da data competenza - ' . $this->fattura->getReference(1);
                    $movimento_storno->save();

                    // Movimento di storno: Azzera l'IVA dalla data di competenza (AVERE)
                    $movimento_storno = Movimento::build($mastrino_storno, setting('Conto per Iva su acquisti'), $this->fattura);
                    $movimento_storno->setTotale($iva_detraibile, 0);
                    $movimento_storno->descrizione = 'Storno IVA da data competenza - ' . $this->fattura->getReference(1);
                    $movimento_storno->save();

                    // Crea un mastrino per il giroconto con la data corretta (per la registrazione)
                    $mastrino_giroconto = Mastrino::build($descrizione_giroconto . ' - ' . $this->fattura->getReference(1), $data_giroconto, false, false);

                    // Movimento di giroconto: Registra l'IVA nella data corretta (DARE)
                    $movimento_giroconto = Movimento::build($mastrino_giroconto, setting('Conto per Iva su acquisti'), $this->fattura);
                    $movimento_giroconto->setTotale(0, $iva_detraibile);
                    $movimento_giroconto->descrizione = $descrizione_giroconto . ' - ' . $this->fattura->getReference(1);
                    $movimento_giroconto->save();

                    // Movimento di giroconto: Registra l'IVA nella data corretta (AVERE)
                    $movimento_giroconto = Movimento::build($mastrino_giroconto, setting('Conto per Iva transitoria'), $this->fattura);
                    $movimento_giroconto->setTotale($iva_detraibile, 0);
                    $movimento_giroconto->descrizione = $descrizione_giroconto . ' - ' . $this->fattura->getReference(1);
                    $movimento_giroconto->save();
                }
            }
        }

        // Nel penultimo conto del mastrino inserisco l'eventuale differenza per evitare sbilanci nel totale,
        // evitando di mettere differenze nell'iva
        $diff = round($totale_avere - $totale_dare, 4);

        if ($diff) {
            $movimenti = $mastrino->movimenti()->where('primanota', '=', 0)->get();
            $m = 0;
            foreach ($movimenti as $movimento) {
                if ($m++ == $movimenti->count() - 2) {
                    if ($movimento->totale < 0) {
                        $movimento->setTotale(abs($movimento->totale) - $diff, 0);
                    } else {
                        $movimento->setTotale(0, abs($movimento->totale) + $diff);
                    }

                    $movimento->save();
                }
            }
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

        // Elimina anche tutti i mastrini correlati ai giroconti IVA
        $this->rimuoviGirocontiIVA();
    }

    /**
     * Elimina tutti i mastrini correlati ai giroconti IVA della fattura.
     */
    protected function rimuoviGirocontiIVA()
    {
        $riferimento_fattura = $this->fattura->getReference(1);

        // Cerca tutti i mastrini che contengono il riferimento della fattura e sono giroconti IVA
        $mastrini_giroconti = Mastrino::where('primanota', false)
            ->where(function ($query) use ($riferimento_fattura) {
                $query->where('descrizione', 'like', "%Giroconto IVA a credito anno precedente - {$riferimento_fattura}%")
                      ->orWhere('descrizione', 'like', "%Giroconto IVA al mese successivo - {$riferimento_fattura}%")
                      ->orWhere('descrizione', 'like', "%Storno IVA da data competenza - {$riferimento_fattura}%");
            })
            ->get();

        // Elimina tutti i mastrini trovati
        foreach ($mastrini_giroconti as $mastrino_giroconto) {
            $mastrino_giroconto->delete();
        }
    }
}
