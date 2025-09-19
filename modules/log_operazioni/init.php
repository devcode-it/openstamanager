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
declare(strict_types=1);

include_once __DIR__.'/../../core.php';

use Ergebnis\Json\Printer;

if (!empty($id_record)) {
    $record = $dbo->fetchOne("SELECT * FROM zz_operations WHERE id=".prepare($id_record));

    $printer = new Printer\Printer();
    if( !empty($record['context']) ){
        $record['context'] = str_replace('"','\"',$printer->print( $record['context'], ' ' ));
    }

    if( !empty($record['message']) ){
        $record['message'] = str_replace('"','\"',$printer->print( $record['message'], ' ' ));
    }
}
