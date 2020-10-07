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
        if (!$this->getDocument()->movimenta_magazzino) {
            return;
        }

        $movimenta = true;

        // Movimenta il magazzino solo se l'articolo non è già stato movimentato da un documento precedente
        // Movimentazione forzata per Note di credito/debito
        if ($this->hasOriginalComponent() && !$this->getDocument()->isNota()) {
            $original = $this->getOriginalComponent();
            $movimenta = !$original->getDocument()->movimenta_magazzino;
        }

        if ($movimenta) {
            $this->movimentaMagazzino($qta);
        }
    }
}
