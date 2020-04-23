<?php

include_once __DIR__.'/../../../core.php';

$revisione_principale = $dbo->fetchOne('SELECT master_revision FROM co_preventivi WHERE id = '.prepare($id_record));

$revisioni = $dbo->fetchArray('SELECT * FROM co_preventivi WHERE master_revision = '.prepare($revisione_principale['master_revision']).' OR id = '.prepare($revisione_principale['master_revision']).' ORDER BY created_at');

echo "
<form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post'>
    <input type='hidden' name='backto' value='record-edit'>
    <input type='hidden' name='op' value='edit_revision'>
    <input type='hidden' name='id_plugin' value='".$id_plugin."'>
    <input type='hidden' name='id_record' value='".$id_record."'>
    <input type='hidden' name='master_revision' value='".$revisione_principale['master_revision']."'>

    <div class='alert alert-info'>
        <i class='fa fa-info-circle'></i> ".tr('Seleziona la spunta e clicca salva per cambiare la revisione del preventivo')."
    </div>

    <table class='table table-condensed table-bordered'>
        <tr>
            <th style='width:50px;' class='text-center'>#</th>
            <th>Descrizione</th>
            <th style='width:50px;'></th>
        </tr>";

foreach ($revisioni as $i => $revisione) {
    if ($revisione['master_revision'] == $revisione['id'] || $revisione['default_revision'] == 1) {
        $disabled = 'disabled';
    } else {
        $disabled = '';
    }

    if ($revisione['default_revision']) {
        $cheched = 'checked';
    } else {
        $cheched = '';
    }

    echo "
        <tr>
            <td class='text-center'>
                <input type='radio' class='revision_changer' name='idrevisione' value='".$revisione['id']."' ".$cheched.'>
            </td>
            <td>
                '.tr('Revisione _NUM_ creata il _DATE_ alle _TIME_', [
                    '_NUM_' => ($revisione['numero_revision']),
                    '_DATE_' => dateFormat($revisione['created_at']),
                    '_TIME_' => timeFormat($revisione['created_at']),
                ])."
            </td>
            <td class='text-center'>
                <button type='button' class='btn btn-danger ".$disabled."' onclick='delete_revision(".$revisione['id'].")' ".$disabled.">
                    <i class='fa fa-trash'></i>
                </button>
            </td>
        </tr>";
}

echo '
    </table>';

echo "
    <div class='row'>
        <div class='col-md-12 text-center'>
            <button ".((count($revisioni) < 2) ? 'disabled' : '')." type='submit' class='btn btn-primary'>
                <i class='fa fa-refresh'></i> ".tr('Cambia revisione').'
            </button>
        </div>
    </div>
</form>';

echo "
<form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='form_deleterevision'>
    <input type='hidden' name='backto' value='record-edit'>
    <input type='hidden' name='op' value='delete_revision'>
    <input type='hidden' name='id_plugin' value='".$id_plugin."'>
    <input type='hidden' name='id_record' value='".$id_record."'>
    <input type='hidden' name='idrevisione' id='idrevisione' value=''>
</form>";

echo '
<script>
function delete_revision(id) {
    if(confirm("'.tr('Vuoi cancellare questa revisione?').'")){
        $("#idrevisione").val(id);
        $("#form_deleterevision").submit();
    }
}
</script>';
