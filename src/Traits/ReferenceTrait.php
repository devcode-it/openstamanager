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

namespace Traits;

trait ReferenceTrait
{
    abstract public function getReferenceName();

    abstract public function getReferenceNumber();

    abstract public function getReferenceDate();

    abstract public function getReferenceRagioneSociale();

    public function getReference($show_ragione_sociale = null)
    {
        // Informazioni disponibili
        $name = $this->getReferenceName();

        $number = $this->getReferenceNumber();
        $date = $this->getReferenceDate();

        $ragione_sociale = $this->getReferenceRagioneSociale();

        // Testi predefiniti
        if (!empty($date) && !empty($number) && !empty($ragione_sociale) && !empty($show_ragione_sociale)) {
            $description = tr('_DOC_ num. _NUM_ del _DATE_ (_RAGIONE_SOCIALE_)');
        } elseif (!empty($date) && !empty($number)) {
            $description = tr('_DOC_ num. _NUM_ del _DATE_');
        } elseif (!empty($number)) {
            $description = tr('_DOC_ num. _NUM_');
        } elseif (!empty($date)) {
            $description = tr('_DOC_ del _DATE_');
        } else {
            $description = tr('_DOC_');
        }

        // Creazione descrizione
        $description = replace($description, [
            '_DOC_' => $name,
            '_NUM_' => $number,
            '_RAGIONE_SOCIALE_' => $ragione_sociale,
            '_DATE_' => dateFormat($date),
        ]);

        return $description;
    }
}
