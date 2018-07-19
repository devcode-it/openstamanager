<?php

namespace HTMLBuilder\Manager;

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
        $options['showpanel'] = isset($options['showpanel']) ? $options['showpanel'] : true;
        $options['label'] = isset($options['label']) ? $options['label'] : tr('Nuovo allegato').':';

        $options['id_plugin'] = !empty($options['id_plugin']) ? $options['id_plugin'] : null;

        // ID del form
        $attachment_id = 'attachments_'.$options['id_module'].'-'.$options['id_plugin'];

        // Cartella delle anteprime
        $directory = \Uploads::getDirectory($options['id_module'], $options['id_plugin']);

        $dbo = \Database::getConnection();

        // Codice HTML
        $result = '
<div id="'.$attachment_id.'" >';

        if (!empty($options['showpanel'])) {
            $result .= '
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Allegati').'</h3>
        </div>
        <div class="panel-body">';
        }

        $count = 0;

        $where = '`id_module` '.(!empty($options['id_module']) ? '= '.prepare($options['id_module']) : 'IS NULL').' AND `id_plugin` '.(!empty($options['id_plugin']) ? '= '.prepare($options['id_plugin']) : 'IS NULL').'';

        // Categorie
        $categories = $dbo->fetchArray('SELECT DISTINCT `category` FROM `zz_files` WHERE '.$where.' ORDER BY `category`');
        foreach ($categories as $category) {
            $category = $category['category'];

            $rs = $dbo->fetchArray('SELECT * FROM `zz_files` WHERE `category`'.(!empty($category) ? '= '.prepare($category) : 'IS NULL').' AND `id_record` = '.prepare($options['id_record']).' AND '.$where);

            if (!empty($rs)) {
                $result .= '
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">'.(!empty($category) ? $category : tr('Generale')).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body no-padding">
    <table class="table">
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Data').'</th>
            <th width="15%" class="text-center">'.tr('Opzioni').'</th>
        </tr>';

                foreach ($rs as $r) {
                    $result .= '
        <tr>
            <td align="left">
                <a href="'.ROOTDIR.'/'.$directory.'/'.$r['filename'].'" target="_blank">
                    <i class="fa fa-external-link"></i> '.$r['nome'].'
                </a>
            </td>
            <td>'.\Translator::timestampToLocale($r['created_at']).'</td>
            <td class="text-center">
                <a class="btn btn-xs btn-primary" href="'.ROOTDIR.'/actions.php?id_module='.$options['id_module'].'&op=download_file&id='.$r['id'].'&filename='.$r['filename'].'" target="_blank">
                    <i class="fa fa-download"></i>
                </a>';

                    // Anteprime supportate dal browser
                    $extension = pathinfo($r['original'])['extension'];
                    $supported_extensions = ['pdf', 'jpg', 'png', 'gif', 'jpeg', 'bmp'];
                    if (in_array($extension, $supported_extensions)) {
                        $result .= "
                <div class='hide' id='view-".$r['id']."'>";

                        if ($extension == 'pdf') {
                            $result .= '
                    <iframe src="'.\Prints::getPDFLink($directory.'/'.$r['filename']).'" frameborder="0" width="100%" height="550"></iframe>';
                        } else {
                            $result .= '
                    <img src="'.ROOTDIR.'/'.$directory.'/'.$r['filename'].'" width="100%"></img>';
                        }

                        $result .= '
                </div>';

                        $result .= '
                <button class="btn btn-xs btn-info" data-target="#bs-popup2"  type="button" data-title="'.prepareToField($r['nome']).' <small><em>('.$r['filename'].')</em></small>" data-href="#view-'.$r['id'].'">
                    <i class="fa fa-eye"></i>
                </button>';
                    } else {
                        $result .= '
                <button class="btn btn-xs btn-default" title="'.tr('Anteprima file non disponibile').'." onclick="alert(\''.tr('Anteprima file di tipo "'.$extension.'" non supportata.').'\');">
                    <i class="fa fa-eye"></i>
                </button>';
                    }

                    if (!$options['readonly']) {
                        $result .= '
                <a class="btn btn-xs btn-danger ask" data-backto="record-edit" data-msg="'.tr('Vuoi eliminare questo file?').'" data-op="unlink_file" data-id="'.$r['id'].'" data-filename="'.$r['filename'].'">
                    <i class="fa fa-trash"></i>
                </a>';
                    }

                    $result .= '
            </td>
        </tr>';

                    ++$count;
                }

                $result .= '
    </table>
    </div>
</div>

        <div class="clearfix"></div>
        <br>';
            }
        }

        // Form per l'upload di un nuovo file
        if (!$options['readonly']) {
            $result .= '
    <b>'.$options['label'].'</b>
    <div id="upload-form" class="row">
        <div class="col-md-4">
            {[ "type": "text", "placeholder": "'.tr('Nome').'", "name": "nome_allegato", "class": "unblockable" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "text", "placeholder": "'.tr('Categoria').'", "name": "categoria", "class": "unblockable" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "file", "placeholder": "'.tr('File').'", "name": "blob", "class": "unblockable" ]}
        </div>

		<div class="col-md-2 text-right">
			<button type="button" class="btn btn-success" id="upload">
				<i class="fa fa-upload"></i> '.tr('Carica').'
			</button>
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

        if (!empty($options['showpanel'])) {
            $result .= '
    </div>
</div>
</div>';
        }

        $result .= '
<script src="'.ROOTDIR.'/lib/init.js"></script>

<script>
$(document).ready(function(){
    $("#'.$attachment_id.' #categoria").autocomplete({
        source: [';

        foreach ($categories as $category) {
            $result .= '
            "'.$category['category'].'",';
        }

        $result .= '
        ],
        minLength: 0
    }).focus(function() {
        $(this).autocomplete("search", $(this).val())
    });;

    data = {
        op: "link_file",
        id_module: "'.$options['id_module'].'",
        id_plugin: "'.$options['id_plugin'].'",
        id_record: "'.$options['id_record'].'",
    };

    $("#'.$attachment_id.' #upload").click(function(){
        $("#'.$attachment_id.' #upload-form").ajaxSubmit({
            url: globals.rootdir + "/actions.php",
            beforeSubmit: function(arr, $form, options) {
                return $form.find("input[name=nome_allegato]").val() && $form.find("input[name=blob]").val();
            },
            data: data,
            type: "post",
            uploadProgress: function(event, position, total, percentComplete) {
                $("#'.$attachment_id.' #upload").prop("disabled", true).html(percentComplete + "%").removeClass("btn-success").addClass("btn-info");
            },
            success: function(data){
                $("#'.$attachment_id.'").load(globals.rootdir + "/ajax.php?op=list_attachments&id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id_plugin='.$options['id_plugin'].'");
            },
            error: function(data) {
                alert("'.tr('Errore').': " + data);
            }
        });
    });
});
</script>';

        return $result;
    }
}
