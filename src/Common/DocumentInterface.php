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

namespace Common;

use Common\Components\Component;

interface DocumentInterface
{
    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti.
     *
     * @return iterable
     */
    public function getRighe();

    /**
     * Restituisce la riga identificata dall'ID indicato.
     *
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    public function getRiga($type, $id);

    /**
     * Restituisce la collezione di righe e articoli con valori rilevanti per i conti, raggruppate sulla base dei documenti di provenienza.
     * La chiave è la serializzazione del documento di origine, oppure null in caso non esista.
     *
     * @return iterable
     */
    public function getRigheRaggruppate();

    /**
     * Restituisce la direzione in relazione al flusso di denaro impostata per il documento.
     *
     * @return string 'entrata'|'uscita'
     */
    public function getDirezioneAttribute();

    /**
     * Metodo richiamato a seguito di modifiche sull'evasione generale delle righe del documento.
     * Utilizzabile per l'impostazione automatica degli stati.
     */
    public function triggerEvasione(Component $trigger);

    /**
     * Metodo richiamato a seguito della modifica o creazione di una riga del documento.
     * Utilizzabile per l'impostazione automatica di campi statici del documento.
     */
    public function triggerComponent(Component $trigger);
}
