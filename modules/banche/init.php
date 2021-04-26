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

use Modules\Banche\Banca;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $banca = Banca::find($id_record);

    if (!empty($banca)) {
        $record = $banca->toArray();
    }
}

$help_codice_bic = tr('Il codice BIC (o SWIFT) è composto da 8 a 11 caratteri (lettere e numeri) ed è suddiviso in:').'<br><br><ul><li>'.tr('AAAA - codice bancario').'</li><li>'.tr('BB - codice ISO della nazione').'</li><li>'.tr('CC - codice città presso la quale è ubicata la banca').'</li><li>'.tr('DD - codice della filiale (opzionale)').'</li></ul>';
