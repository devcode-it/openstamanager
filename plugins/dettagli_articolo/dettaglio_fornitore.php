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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\DettagliArticolo\DettaglioFornitore;

include_once __DIR__.'/../../core.php';

$id_articolo = get('id_articolo');
$articolo = Articolo::find($id_articolo);

$id_anagrafica = get('id_anagrafica');
$anagrafica = Anagrafica::find($id_anagrafica);

$id_riga = get('id_riga');
$fornitore = [];
if (!empty($id_riga)) {
    $fornitore = DettaglioFornitore::find($id_riga);
} else {
    $fornitore = $articolo->dettaglioFornitore($id_anagrafica);
}

echo '
<p>'.tr('Informazioni relative al fornitore _NAME_', [
    '_NAME_' => $anagrafica->ragione_sociale,
]).'.</p>

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_fornitore">

    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_riga" value="'.$id_riga.'">
    <input type="hidden" name="id_anagrafica" value="'.$id_anagrafica.'">
    <input type="hidden" name="id_articolo" value="'.$id_articolo.'">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Codice fornitore').'", "name": "codice_fornitore", "required": 1, "value": "'.$fornitore['codice_fornitore'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "required": 1, "value": "'.$fornitore['descrizione'].'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Qta minima ordinabile').'", "name": "qta_minima", "required": 0, "value": "'.$fornitore['qta_minima'].'", "icon-after": "'.$articolo->um.'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Tempi di consegna').'", "name": "giorni_consegna", "class": "text-right", "required": 0, "value": "'.$fornitore['giorni_consegna'].'", "icon-after": "'.tr('gg').'" ]}
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-primary pull-right">
                <i class="fa fa-edit"></i> '.tr('Modifica').'
            </button>
        </div>
    </div>
</form>

<script>$(document).ready(init);</script>';
