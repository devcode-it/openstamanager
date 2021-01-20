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

if (empty($_SESSION['giacenze_sedi']['idsede'])) {
    $_SESSION['giacenze_sedi']['idsede'] = 0;
}
$id_sede = $_SESSION['giacenze_sedi']['idsede'];

echo '
<div class="row">
    <div class="col-md-offset-8 col-md-4">
        {["type":"select", "label":"'.tr('Sede').'", "name": "id_sede", "ajax-source": "sedi_azienda", "value":"'.$id_sede.'" ]}
    </div>
</div>

<script>
    $("#id_sede").change(function(){
        session_set("giacenze_sedi,idsede", $(this).val(), 0);
        setTimeout(function(){
            location.reload();
        }, 500);
    });
</script>';
