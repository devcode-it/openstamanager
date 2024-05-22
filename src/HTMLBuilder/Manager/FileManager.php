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

namespace HTMLBuilder\Manager;

use Models\Upload;
use Util\FileSystem;

/**
 * Gestione allegati.
 *
 * @since 2.3
 */
class FileManager implements ManagerInterface
{
    /**
     * Gestione "filelist_and_upload".
     * Esempio: {( "name": "filelist_and_upload", "id_module": "2", "id_record": "1", "readonly": "false" )}.
     *
     * @param array $options
     *
     * @return string
     */
    public function manage($options)
    {
        $options['readonly'] = !empty($options['readonly']) ? true : false;
        $options['showcard'] ??= true;

        $options['id_plugin'] = !empty($options['id_plugin']) ? $options['id_plugin'] : null;

        // ID del form
        $attachment_id = 'attachments_'.$options['id_module'].'_'.$options['id_plugin'];

        if (ini_get('upload_max_filesize') < ini_get('post_max_size')) {
            $upload_max_filesize = ini_get('upload_max_filesize');
        } elseif (ini_get('upload_max_filesize') > ini_get('post_max_size')) {
            $upload_max_filesize = ini_get('post_max_size');
        } else {
            $upload_max_filesize = ini_get('upload_max_filesize');
        }

        $upload_max_filesize = substr($upload_max_filesize, 0, -1);

        $dbo = database();

        // Codice HTML
        $result = '
<div class="gestione-allegati" id="'.$attachment_id.'" data-id_module="'.$options['id_module'].'" data-id_plugin="'.$options['id_plugin'].'" data-id_record="'.$options['id_record'].'" data-max_filesize="'.$upload_max_filesize.'">';

        if (!empty($options['showcard'])) {
            $result .= '
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">'.tr('Allegati').'</h3>
        </div>
        <div class="card-body">';
        }

        $count = 0;

        $where = '`id_module` '.(!empty($options['id_module']) && empty($options['id_plugin']) ? '= '.prepare($options['id_module']) : 'IS NULL').' AND `id_plugin` '.(!empty($options['id_plugin']) ? '= '.prepare($options['id_plugin']) : 'IS NULL').'';

        // Categorie
        $categories = $dbo->fetchArray('SELECT DISTINCT(BINARY `category`) AS `category` FROM `zz_files` WHERE '.$where.' ORDER BY `category`');
        foreach ($categories as $category) {
            $category = $category['category'];

            $rs = $dbo->fetchArray('SELECT * FROM `zz_files` WHERE BINARY `category`'.(!empty($category) ? '= '.prepare($category) : 'IS NULL').' AND `id_record` = '.prepare($options['id_record']).' AND '.$where);

            if (!empty($rs)) {
                $result .= '
<div class="card card-success">
    <div class="card-header with-border">
        <h3 class="card-title">'.(!empty($category) ? $category : tr('Generale')).'</h3>

        <div class="card-tools pull-right">
            <button type="button" class="btn btn-card-tool" data-card-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body no-padding table-responsive">
    <table class="table table-striped table-condensed ">
	  <thead>
        <tr>
            <th scope="col" width="5%" class="text-center"></th>
            <th scope="col" >'.tr('Nome').'</th>
            <th scope="col" width="15%" >'.tr('Data').'</th>
            <th scope="col" width="10%" class="text-center">#</th>
        </tr>
	  </thead>
	  <tbody class="files">';

                foreach ($rs as $r) {
                    $file = Upload::find($r['id']);

                    $result .= '
        <tr id="row_'.$file->id.'" data-id="'.$file->id.'" data-filename="'.$file->filename.'" data-nome="'.$file->name.'">
            <td class="text-center">
                <input class="check_files unblockable" type="checkbox"/>
            </td>
            <td align="left">';

                    if ($file->user && $file->user->photo) {
                        $result .= '
                <img class="attachment-img tip" src="'.$file->user->photo.'" title="'.$file->user->nome_completo.'">';
                    } else {
                        $result .= '

                <i class="fa fa-user-circle-o attachment-img tip" title="'.tr('OpenSTAManager').'"></i>';
                    }

                    $result .= '

                <a href="'.base_path().'/view.php?file_id='.$file->id.'" target="_blank">
                    <i class="fa fa-external-link"></i> '.$file->name.'
                </a>

                <small> ('.$file->extension.')'.((!empty($file->size)) ? ' ('.FileSystem::formatBytes($file->size).')' : '').' '.(((setting('Logo stampe') == $file->filename) || (setting('Filigrana stampe') == $file->filename)) ? '<span class="tip" title="'.tr('Logo caricato correttamente').'." >✔️</span>' : '').'</small>'.'
            </td>

            <td>'.timestampFormat($file['created_at']).'</td>

            <td class="text-center">
                <button type="button" class="btn btn-xs btn-primary" onclick="scaricaAllegato(this)">
                    <i class="fa fa-download"></i>
                </button>';

                    // Anteprime supportate dal browser
                    if ($file->hasPreview()) {
                        $result .= '
                <button type="button" class="btn btn-xs btn-info" onclick="visualizzaAllegato(this)">
                    <i class="fa fa-eye"></i>
                </button>';
                    } else {
                        $result .= '
                <button type="button" class="btn btn-xs btn-default disabled" title="'.tr('Anteprima file non disponibile').'" disabled>
                    <i class="fa fa-eye"></i>
                </button>';
                    }

                    if (!$options['readonly']) {
                        $result .= '
                <button type="button" class="btn btn-xs btn-warning" onclick="modificaAllegato(this,[$(this).closest(\'tr\').data(\'id\')])">
                    <i class="fa fa-edit"></i>
                </button>';
                        if (!$file->isFatturaElettronica() || $options['abilita_genera']) {
                            $result .= '
                <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviAllegato(this)">
                    <i class="fa fa-trash"></i>
                </button>';
                        }
                    }

                    $result .= '
            </td>
        </tr>';

                    ++$count;
                }

                $result .= '
      </tbody>
	</table>
    </div>
</div>

        <div class="clearfix"></div>
        ';
            }
        }

        if (!empty($file)) {
            $result .= '
    <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default">
            <input class="pull-left unblockable" id="check_all_files" type="checkbox"/>
        </button>';
            if (!$options['readonly']) {
                $result .= '
        <button type="button" class="btn btn-xs btn-default disabled" id="modifica_files" onclick="modificaAllegato(this,JSON.stringify(getSelectFiles()));">
            <i class="fa fa-edit"></i>
        </button>';
            }
            $result .= '
        <button type="button" class="btn btn-xs btn-default disabled" id="zip_files" onclick="scaricaZipAllegati(this,JSON.stringify(getSelectFiles()));">
            <i class="fa fa-file-archive-o"></i>
        </button>
    </div>';
        }

        // Form per l'upload di un nuovo file
        if (!$options['readonly']) {
            $result .= '
    <div id="upload-form" class="row">
        <div class="col-md-12">
            <div class="dropzone dz-clickable" id="dragdrop">

            </div>
        </div>
    </div>';
        }
        // In caso di readonly, se non è stato caricato nessun allegato mostro almeno box informativo
        elseif ($count == 0) {
            $result .= '
        <div class="alert alert-info" style="margin-bottom:0px;" >
            <i class="fa fa-info-circle"></i>
            '.tr('Nessun allegato è stato caricato').'.
        </div>';
        }

        if (!empty($options['showcard'])) {
            $result .= '
    </div>
</div>
</div>';
        }

        $source = array_clean(array_column($categories, 'category'));

        $result .= '
<script>$(document).ready(init)</script>

<script>
$(document).ready(function() {
    const container = $("#'.$attachment_id.'");

    initGestioneAllegati(container);
    impostaCategorieAllegatiDisponibili(container, '.json_encode($source).');
});

// Modifica categoria
$("#'.$attachment_id.' .category-edit").click(function() {
    const container = $(this).closest(".gestione-allegati");

    modificaCategoriaAllegati(container, this);
});

$("#'.$attachment_id.' .category-save").click(function() {
    const container = $(this).closest(".gestione-allegati");

    salvaCategoriaAllegati(container, this);
});

$("#'.$attachment_id.' .category-cancel").click(function() {
    const container = $(this).closest(".gestione-allegati");

    ricaricaAllegati(gestione);
});

// Upload
$("#'.$attachment_id.' #upload").click(function(){
    const container = $(this).closest(".gestione-allegati");

    aggiungiAllegato(container);
});

// Estraggo le righe spuntate
function getSelectFiles() {
    let data=new Array();
    $(".files").find(".check_files:checked").each(function (){ 
        data.push($(this).closest("tr").data("id"));
    });

    return data;
}

$(".check_files").on("change", function() {
    let checked = 0;
    $(".check_files").each(function() {
        if ($(this).is(":checked")) {
            checked = 1;
        }
    });

    if (checked) {
        $("#zip_files").removeClass("disabled");
        $("#modifica_files").removeClass("disabled");
    } else {
        $("#zip_files").addClass("disabled");
        $("#modifica_files").addClass("disabled");
    }
});

$("#check_all_files").click(function(){    
    if( $(this).is(":checked") ){
        $(".check_files").each(function(){
            if( !$(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    }else{
        $(".check_files").each(function(){
            if( $(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    }
});
</script>';

        return $result;
    }
}
