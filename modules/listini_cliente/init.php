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

use Modules\ListiniCliente\Listino;

if (isset($id_record)) {
    $listino = Listino::find($id_record);
    $record = $dbo->fetchOne('SELECT * FROM `mg_listini` WHERE id='.prepare($id_record));

    $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
    $articoli = $dbo->fetchArray('SELECT mg_listini_articoli.*, mg_articoli.codice, mg_articoli.descrizione,  mg_articoli.'.($prezzi_ivati ? 'minimo_vendita_ivato' : 'minimo_vendita').' AS minimo_vendita FROM mg_listini_articoli LEFT JOIN mg_articoli ON mg_listini_articoli.id_articolo=mg_articoli.id WHERE id_listino='.prepare($id_record));
}
