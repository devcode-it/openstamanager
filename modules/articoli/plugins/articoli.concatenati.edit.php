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

include_once __DIR__.'/../../../core.php';

$articolo_concatenato = $dbo->fetchOne('SELECT * FROM mg_articoli_concatenati WHERE id = '.prepare($_GET['id']));

echo '
<input type="hidden" id="id" value="'.$articolo_concatenato['id'].'">
<div class="row">
    <div id="prezzo" class="col-md-6">
        {[ "type": "number", "label": "'.tr('Prezzo').'", "name": "prezzo", "value": "'.$articolo_concatenato['prezzo'].'", "icon-after": "'.currency().'" ]}
    </div>';

echo '
</div>

<div class="row">
    <div class="col-md-12">
        <button class="btn btn-primary pull-right btn-save">
            <i class="fa fa-edit"></i> '.tr('Salva').'
        </button>
    </div>
</div>';

echo '
<script>
    $(document).ready(function(){
        $("body").on("click", ".btn-save", function(){
            $.ajax({
                url: "'.$rootdir.'/modules/articoli/actions.php",
                type: "post",
                data: {
                    op: "update_concatenato",
                    prezzo: $("#prezzo").find("input").val(),
                    id: $("#id").val(),
                },
                success: function(data){
                    //reload page
                    setTimeout(function(){
                        location.reload();
                    }, 1000);
                },
            });
        });
    });
</script>';
