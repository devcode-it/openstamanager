<?php

namespace HTMLBuilder\Manager;

/**
 * @since 2.3
 */
class FileManager implements ManagerInterface
{
    public function manage($options)
    {
        $options['showpanel'] = isset($options['showpanel']) ? $options['showpanel'] : true;
        $options['label'] = isset($options['label']) ? $options['label'] : 'Nuovo allegato:';

        $dbo = \Database::getConnection();

        $result .= '
<a name="attachments"></a>';

        if (!empty($options['showpanel'])) {
            $result .= '
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'._('Allegati').'</h3>
        </div>
        <div class="panel-body">';
        }

        // Visualizzo l'elenco di file già caricati
        $rs = $dbo->fetchArray('SELECT * FROM zz_files WHERE id_module='.prepare($options['id_module']).' AND id_record='.prepare($options['id_record']));

        if (!empty($rs)) {
            $result .= '
    <table class="table table-condensed table-hover table-bordered">
        <tr>
            <th>'._('Nome').'</th>
            <th>'._('Data').'</th>
            <th style="width:5%;text-align:center;">#</th>
        </tr>';

            foreach ($rs as $r) {
                $result .= '
        <tr>
            <td align="left">
                <a href="'.ROOTDIR.'/files/'.\Modules::getModule($options['id_module'])['directory'].'/'.$r['filename'].'" target="_blank">
                    <i class="fa fa-external-link"></i> '.$r['nome'].'
                </a>
            </td>
            <td>'.\Translator::timestampToLocale($r['created_at']).'</td>
            <td>
                <a class="btn btn-danger ask" data-backto="record-edit" data-msg="'._('Vuoi eliminare questo file?').'" data-op="unlink_file" data-id="'.$r['id'].'" data-filename="'.$r['filename'].'">
                    <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>';
            }

            $result .= '
    </table>
    <div class="clearfix"></div>
    <br>';
        }

        // Form per l'upload di un nuovo file
        $result .= '
    <b>'.$options['label'].'</b>
    <div class="row">
        <div class="col-lg-4">
            {[ "type": "text", "placeholder": "'._('Nome').'", "name": "nome_allegato", "required": 1 ]}
        </div>

        <div class="col-lg-6">
            {[ "type": "file", "placeholder": "'._('File').'", "name": "blob", "required": 1 ]}
        </div>

        <div class="col-lg-2 text-right">
            <button type="button" class="btn btn-success" id="upload_button"  onclick="SaveFile();">
                <i class="fa fa-upload"></i> '._('Carica').'
            </button>
        </div>
    </div>';

        $result .= '
    <script>
        function SaveFile(){
            if(!$("#blob").val()){
                alert("Devi selezionare un file con il tasto Sfoglia...");
                return false;
            } else if(!$("input[name=nome_allegato]").val()){
                alert("Devi inserire un nome per il file!");
                return false;
            }

            var file_data = $("#blob").prop("files")[0];
            var form_data = new FormData();
            form_data.append("blob", file_data);
            form_data.append("nome_allegato", $("input[name=nome_allegato]").val());
            form_data.append("op","link_file");
            form_data.append("id_record","'.$options['id_record'].'");
            form_data.append("id_module", "'.$options['id_module'].'");

            $.ajax({
                url: "'.ROOTDIR.'/actions.php",
                cache: false,
                type: "post",
                processData: false,
                contentType: false,
                dataType : "html",
                data: form_data,
                success: function(data) {
                    location.href = globals.rootdir + "/editor.php?id_module='.$options['id_module'].'&id_record='.$options['id_record'].'";
                },
                error: function(data) {
                    alert(data);
                }
            })
        }
    </script>';

        if (!empty($options['showpanel'])) {
            $result .= '
    </div>
</div>';
        }

        return $result;
    }
}
