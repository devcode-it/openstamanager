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

namespace Modules\Fatture\Components;

use Common\Components\Article;

/**
 * @extends Article<\Modules\Fatture\Fattura>
 */
class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'co_righe_documenti';
    protected $serialRowID = 'documento';

    public function movimenta($qta)
    {
        $documento = $this->getDocument();
        if (!$documento->movimenta_magazzino) {
            return;
        }

        $movimenta = true;

        // Controllo sul documento di origine dell'articolo: effettua il movimento di magazzino solo se non è già stato effettuato
        // Se il documento corrente è una Nota (di credito/debito) si veda il controllo successivo
        if (!$documento->isNota() && $this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();
            $movimenta = !$original->getDocument()->movimenta_magazzino;
        }

        // Gestione casistica per Note (di credito/debito)
        if ($documento->isNota()) {
            // Correzione delle quantità per gestione dei movimenti invertiti
            $qta = -$qta;

            if ($this->hasOriginalComponent()) {
                $original = $this->getOriginalComponent();
                $original_document = $original->getDocument();
                $direzione_inversa = $original_document->direzione != $this->getDocument()->direzione;

                // Inversione aggiuntiva in caso di origine da documenti della tipologia inversa
                $qta = $direzione_inversa ? -$qta : $qta;

                // Controllo sul documento di origine dell'articolo: se i movimenti sono già stati effettuati e la direzione è invertita rispetto alla Nota, non si effettuano altri movimenti
                // Esempio: DDT in entrata (documento di uscita) -> Nota di credito (documento di entrata)
                if ($original_document->movimenta_magazzino && $direzione_inversa) {
                    $movimenta = false;
                }
            }
        }

        if ($movimenta) {
            $this->movimentaMagazzino($qta);
        }
    }
}
