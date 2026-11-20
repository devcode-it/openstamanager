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

namespace Modules\Interventi\Components;

use Common\Components\Article;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'in_righe_interventi';
    protected $serialRowID = 'intervento';

    public function save(array $options = [])
    {
        if (!$this->exists || $this->isDirty(['idarticolo', 'impegnato_magazzino'])) {
            if ($this->getDocument()->impegna_materiali) {
                $articolo = $this->exists && $this->isDirty('idarticolo') ? $this->articolo()->first() : $this->articolo;
                $origine = $this->getOriginalComponent();

                // Una riga già coperta dal documento di origine non impegna di nuovo il magazzino.
                $this->impegnato_magazzino = $articolo && !$articolo->servizio
                    && !$origine?->getDocument()?->movimenta_magazzino;
            } elseif (array_key_exists('impegnato_magazzino', $this->attributes)) {
                $this->impegnato_magazzino = false;
            }
        }

        return parent::save($options);
    }

    public function movimenta($qta)
    {
        // La modalità dell'attività vale anche per le copie che movimentano prima di salvare la riga.
        if (!$this->getDocument()->impegna_materiali) {
            parent::movimenta($qta);
        }
    }
}
