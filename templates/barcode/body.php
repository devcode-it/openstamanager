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

use Modules\Articoli\Articolo;

include_once __DIR__.'/../../core.php';

echo '<style>
.barcode {
    padding: 0;
    margin: 0;
    vertical-align: top;
}
.barcode-cell {
    text-align: center;
    vertical-align: middle;
}
</style>';

if (!empty($_SESSION['superselect']['id_articolo_barcode'])) {
    $articoli = Articolo::whereIn('id', $_SESSION['superselect']['id_articolo_barcode'])->get();
    unset($_SESSION['superselect']['id_articolo_barcode']);
} else {
    $articoli = Articolo::where('id', '=', $id_record)->get();
}

$pages = count($articoli);
$page = 0;
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

foreach ($articoli as $articolo) {
    echo '
    <div class="barcode-cell">
        <p style="font-size:11pt;"><b>'.$articolo->codice.'</b></p>
        <p style="font-size:10pt;">'.$articolo->descrizione.'</p><br>
        <p style="font-size:15pt;"><b>'.moneyFormat($prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita).'</b></p>
        <barcode code="'.$articolo->barcode.'" type="C39" height="2" size="0.65" class="barcode" />
        <p><b>'.$articolo->barcode.'</b></p>
    </div>';

    ++$page;

    if ($page < $pages) {
        echo '<pagebreak>';
    }
}
