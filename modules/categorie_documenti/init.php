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

use Modules\CategorieDocumentali\Categoria;

if (isset($id_record)) {
    $categoria = Categoria::find($id_record);

    $record = $dbo->fetchOne("SELECT *,
        (SELECT COUNT(id) FROM do_documenti WHERE idcategoria = '.prepare($id_record).') AS doc_associati,
        GROUP_CONCAT(do_permessi.id_gruppo SEPARATOR ',') AS permessi
    FROM do_categorie
        LEFT JOIN do_permessi ON do_permessi.id_categoria = do_categorie.id
    WHERE id=".prepare($id_record).'
    GROUP BY do_categorie.id');
}
