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
    $record = $dbo->fetchOne('SELECT * FROM zz_otp_tokens WHERE id='.prepare($id_record));

    // Verifica se il token è scaduto
    $is_not_active = false;
    if (!empty($record['valido_dal']) && !empty($record['valido_al'])) {
        $is_not_active = strtotime((string) $record['valido_dal']) > time() || strtotime((string) $record['valido_al']) < time();
    } elseif (!empty($record['valido_dal']) && empty($record['valido_al'])) {
        $is_not_active = strtotime((string) $record['valido_dal']) > time();
    } elseif (empty($record['valido_dal']) && !empty($record['valido_al'])) {
        $is_not_active = strtotime((string) $record['valido_al']) < time();
    }
}
