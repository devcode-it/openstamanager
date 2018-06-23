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
     * Esempio: {( "name": "filelist_and_upload", "id_module": "2", "id_record": "1", "readonly": "false", "ajax": "true" )}.
     *
     * @param array $options
     *
     * @return string
     */
    public function manage($options)
    {
        $options['readonly'] = !empty($options['readonly']) ? true : false;
        $options['ajax'] = isset($options['ajax']) ? $options['ajax'] : false;
        $options['showpanel'] = isset($options['showpanel']) ? $options['showpanel'] : true;
        $options['label'] = isset($options['label']) ? $options['label'] : tr('Nuovo allegato').':';

        // Riferimento ad un plugin
        $plugin = null;
        if (!empty($options['id_plugin'])) {
            $plugin = '_'.$options['id_plugin'];
        }

        $dbo = \Database::getConnection();

        $result = '
<div id="attachments_'.$options['id_record'].$plugin.'" >
		<a name="attachments_'.rand().'"></a>';

        if (!empty($options['showpanel'])) {
            $result .= '
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Allegati').'</h3>
        </div>
        <div class="panel-body">';
        }

        // Visualizzo l'elenco di file già caricati
        $query = 'SELECT * FROM zz_files WHERE id_record = '.prepare($options['id_record']).' AND id_module'.((!empty($options['id_module'])) ? ' = '.prepare($options['id_module']) : ' IS NULL').' AND id_plugin'.((!empty($options['id_plugin'])) ? ' = '.prepare($options['id_plugin']) : ' IS NULL').'';

        $rs = $dbo->fetchArray($query);

        if (!empty($rs)) {
            $result .= '
    <table class="table table-condensed table-hover table-bordered">
        <tr>
            <th>'.tr('Nome').'</th>
            <th>'.tr('Data').'</th>
            <th width="15%" class="text-center">#</th>
        </tr>';

            foreach ($rs as $r) {
                $result .= '
        <tr>
            <td align="left">
                <a href="'.ROOTDIR.'/files/'.\Modules::get($options['id_module'])['directory'].'/'.$r['filename'].'" target="_blank">
                    <i class="fa fa-external-link"></i> '.$r['nome'].'
                </a>
            </td>
            <td>'.\Translator::timestampToLocale($r['created_at']).'</td>
            <td class="text-center">

                <a class="btn btn-sm btn-primary" href="'.ROOTDIR.'/actions.php?id_module='.$options['id_module'].'&op=download_file&id='.$r['id'].'&filename='.$r['filename'].'" target="_blank">
                    <i class="fa fa-download"></i>
                </a>';

                //Anteprime supportate dal browser
                $extension = strtolower(end((explode('.', $r['original']))));
                $supported_extensions = ['pdf', 'jpg', 'png', 'gif', 'jpeg', 'bmp'];
                if (in_array($extension, $supported_extensions)) {
                    $result .= "<div class='hide' id='view-".$r['id']."'  >";

                    if ($extension == 'pdf') {
                        $result .= '
							 <iframe src="'.ROOTDIR.'/files/'.\Modules::get($options['id_module'])['directory'].'/'.$r['filename'].'"  frameborder="0" scrolling="no"  width="100%" height="550"></iframe>';
                    } else {
                        $result .= '
							<img src="'.ROOTDIR.'/files/'.\Modules::get($options['id_module'])['directory'].'/'.$r['filename'].'" width="100%" ></img>';
                    }

                    $result .= '</div>';

                    $result .= ' <button class="btn btn-sm btn-info" data-target="#bs-popup"  type="button" data-title="'.htmlentities($r['nome'], ENT_QUOTES, 'UTF-8').' <small><em>('.$r['filename'].')</em></small>" data-href="#view-'.$r['id']."\" ><i class='fa fa-eye'></i></button>";
                } else {
                    $result .= ' <button class="btn btn-sm btn-default" title="'.tr('Anteprima file non disponibile').".\" onclick=\"alert('".tr('Anteprima file di tipo "'.$extension.'" non supportata.')."');\" ><i class='fa fa-eye'></i></button>\n";
                }

                if (!$options['readonly']) {
                    $result .= '
                <a class="btn btn-sm btn-danger ask" data-backto="record-edit" data-msg="'.tr('Vuoi eliminare questo file?').'" data-op="unlink_file" data-id="'.$r['id'].'" data-filename="'.$r['filename'].'">
                    <i class="fa fa-trash"></i>
                </a>';
                }

                $result .= '
            </td>
        </tr>';
            }

            $result .= '
    </table>
    <div class="clearfix"></div>
    <br>';
        } else {
            //in caso di readonly, se non è stato caricato nessun allegato mostro almeno box informativo
            if ($options['readonly']) {
                $result .= '
			<div class="alert alert-info" style="margin-bottom:0px;" >
				<i class="fa fa-info-circle"></i>
				'.tr('Nessun allegato è stato caricato', []).'.
			</div>';
            }
        }

        if (!$options['readonly']) {
            // Form per l'upload di un nuovo file
            $result .= '
    <b>'.$options['label'].'</b>
    <div class="row">

        <div class="col-lg-4">
            {[ "type": "text", "placeholder": "'.tr('Nome').'", "name": "nome_allegato", "id": "nome_allegato_'.$options['id_record'].$plugin.'" ]}
        </div>

        <div class="col-lg-6">
            {[ "type": "file", "placeholder": "'.tr('File').'", "name": "blob", "id": "blob_'.$options['id_record'].$plugin.'", "required": 0 ]}
        </div>';

            $result .= '
		<div class="col-lg-2 text-right">
			<button type="button" class="btn btn-success" onclick="saveFile_'.$options['id_record'].$plugin.' ( $(this) );">
				<i class="fa fa-upload"></i> '.tr('Carica').'
			</button>
		</div>';

            $result .= '
    </div>';
        }

        $result .= '
    <script>
        function saveFile_'.$options['id_record'].$plugin.' (btn){
            if(!$("#blob_'.$options['id_record'].$plugin.'").val()){
                swal("'.addslashes(tr('Attenzione!')).'", "'.addslashes(tr('Devi selezionare un file con il tasto "Sfoglia"')).'...", "warning");
                return false;
            }

            var file_data = $("#blob_'.$options['id_record'].$plugin.'").prop("files")[0];

            var form_data = new FormData();

            form_data.append("blob", file_data);
            form_data.append("nome_allegato", $("input[id=nome_allegato_'.$options['id_record'].$plugin.']").val());
            form_data.append("op","link_file");
            form_data.append("id_record","'.$options['id_record'].'");
            form_data.append("id_module", "'.$options['id_module'].'");';

        if (!empty($options['id_plugin'])) {
            $result .= '
            form_data.append("id_plugin","'.$options['id_plugin'].'");';
        }

        $result .= '

            prev_html = btn.html();
            btn.html("<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>'.tr('Attendere...').'");
            btn.prop("disabled", true);

            $.ajax({
                url: "'.ROOTDIR.'/actions.php",
                cache: false,
                type: "post",
                processData: false,
                contentType: false,
                dataType : "html",
                data: form_data,
                success: function(data) {

                    btn.html(prev_html);
                    btn.prop("disabled", false);';

        if ($options['ajax']) {
            $result .= '$("#attachments_'.$options['id_record'].$plugin.'").load( globals.rootdir + "/ajax.php?op=list_attachments&id_module='.$options['id_module'].'&id_record='.$options['id_record'].((!empty($options['id_plugin'])) ? '&id_plugin='.$options['id_plugin'].'#tab_'.$options['id_plugin'] : '').'" );';
        } else {
            $result .= 'location.href = globals.rootdir + "/editor.php?id_module='.$options['id_module'].'&id_record='.$options['id_record'].((!empty($options['id_plugin'])) ? '#tab_'.$options['id_plugin'] : '').'";';
        }

        $result .= '},
                error: function(data) {
                    alert(data);
                }
            })
        }
    </script>';

        if (!empty($options['showpanel'])) {
            $result .= '
    </div>
</div>
</div>';
        }

        return $result;
    }
}
