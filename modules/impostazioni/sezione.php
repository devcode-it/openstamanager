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

use Models\Setting;

include_once __DIR__.'/../../core.php';

$sezione = filter('sezione');
$impostazioni = Setting::where('sezione', $sezione)
    ->get();

foreach ($impostazioni as $impostazione) {
    echo '
    <div class="col-md-4">
        '.Settings::input($impostazione->id).'
    </div>

    <script>';

    if ($impostazione->tipo == 'media') {
        echo '
    $("#media_'.$impostazione->id.'").change(function() {
        caricaMediaImpostazione('.$impostazione->id.');
    });';
    } elseif ($impostazione->tipo == 'time' || $impostazione->tipo == 'date') {
        echo '
    input("setting['.$impostazione->id.']");
    $(document).on("blur", "#setting'.$impostazione->id.'", function (e) {
      salvaImpostazione('.$impostazione->id.', $("#setting'.$impostazione->id.'").val());
    });
    ';
    } else {
        echo '

    input("setting['.$impostazione->id.']").change(function (){
        salvaImpostazione('.$impostazione->id.', input(this).get());
    });';
    }

    echo '
    </script>';
}

?>

<script>
    init();

    function caricaMediaImpostazione(id) {
        var input = document.getElementById("media_" + id);
        if (!input || !input.files.length) {
            return;
        }

        var formData = new FormData();
        formData.append("media_" + id, input.files[0]);
        formData.append("op", "upload_media");
        formData.append("id_module", globals.id_module);
        formData.append("id", id);

        var container = $(input).closest(".form-group");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: formData,
            dataType: "JSON",
            cache: false,
            contentType: false,
            processData: false,
            success: function(data) {
                renderMessages();
                if (data.result) {
                    $("#setting" + id).val(data.file_id);
                    aggiornaAnteprimaMedia(id, data.file_url, data.file_name);
                } else {
                    Swal.fire("'.tr('Errore').'", data.message || "'.tr('Errore durante il caricamento').'", "error");
                    input.value = "";
                }
            },
            error: function() {
                Swal.fire("'.tr('Errore').'", "'.tr('Errore durante il caricamento del file').'", "error");
                input.value = "";
            }
        });
    }

    function rimuoviMediaImpostazione(id) {
        Swal.fire({
            title: "'.tr('Eliminare il file?').'",
            text: "'.tr('L\\'immagine verrà rimossa definitivamente').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Elimina').'",
            cancelButtonText: "'.tr('Annulla').'",
        }).then(function(result) {
            if (!result.value) {
                return;
            }

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    op: "delete_media",
                    id_module: globals.id_module,
                    id: id
                },
                success: function(data) {
                    renderMessages();
                    if (data.result) {
                        $("#setting" + id).val("");
                        $("#media_" + id).val("");
                        $("#media_preview_" + id).remove();
                    } else {
                        Swal.fire("'.tr('Errore').'", data.message || "'.tr('Errore durante l\\'eliminazione').'", "error");
                    }
                }
            });
        });
    }

    function aggiornaAnteprimaMedia(id, fileUrl, fileName) {
        $("#media_preview_" + id).remove();
        var html = '<div class="setting-media-preview mb-2" id="media_preview_' + id + '">' +
            '<img src="' + fileUrl + '" class="img-thumbnail" style="max-height: 80px;">' +
            '<div class="mt-1">' +
                '<a href="' + fileUrl + '" target="_blank"><i class="fa fa-external-link"></i> ' + fileName + '</a> ' +
                '<button type="button" class="btn btn-xs btn-danger ml-2" onclick="rimuoviMediaImpostazione(' + id + ')"><i class="fa fa-trash"></i></button>' +
            '</div>' +
        '</div>';
        $("#media_" + id).before(html);
    }
</script>
