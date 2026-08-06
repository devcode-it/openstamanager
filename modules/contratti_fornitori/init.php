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

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $record = $dbo->fetchOne(
        'SELECT c.*, a.ragione_sociale AS fornitore, s.nome AS stato_nome, s.colore AS stato_colore, cat.nome AS categoria_nome
        FROM ac_contratti_fornitori c
        INNER JOIN an_anagrafiche a ON a.idanagrafica = c.id_fornitore
        INNER JOIN ac_stati_contratti_fornitori s ON s.id = c.id_stato
        LEFT JOIN ac_categorie_contratti_fornitori cat ON cat.id = c.id_categoria
        WHERE c.id = '.prepare($id_record)
    );
}
