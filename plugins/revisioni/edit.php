<?php 
include_once __DIR__.'/../../../core.php';

echo "<form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post'>";
echo "  <input type='hidden' name='backto' value='record-edit'>";
echo "  <input type='hidden' name='op' value='edit_revision'>";
echo "  <input type='hidden' name='id_plugin' value='".$id_plugin."'>";
echo "  <input type='hidden' name='id_record' value='".$id_record."'>";

$rs_master_revision = $dbo->fetchArray('SELECT master_revision FROM co_preventivi WHERE id='.prepare($id_record));

echo "  <input type='hidden' name='master_revision' value='".$rs_master_revision[0]['master_revision']."'>";

$rs_revisioni = $dbo->fetchArray('SELECT * FROM co_preventivi WHERE master_revision='.prepare($rs_master_revision[0]['master_revision']).' OR id='.prepare($rs_master_revision[0]['master_revision']).' ORDER BY created_at');

echo "  <div class='row'>";
echo "      <div class='col-md-12'>";

echo "          <div class='alert alert-info'><i class='fa fa-info-circle'></i> Seleziona la spunta e clicca salva per cambiare la revisione del preventivo</div>";
echo "          <table class='table table-condensed table-bordered'>";
echo "              <tr>
                        <th style='width:50px;' class='text-center'>#</th>
                        <th>Descrizione</th>
                        <th style='width:50px;'></th>
                    </tr>";
for ($i = 0; $i < sizeof($rs_revisioni); ++$i) {
    if ($rs_revisioni[$i]['master_revision'] == $rs_revisioni[$i]['id'] || $rs_revisioni[$i]['default_revision'] == 1) {
        $disabled = 'disabled';
    } else {
        $disabled = '';
    }
    if ($rs_revisioni[$i]['default_revision']) {
        $cheched = 'checked';
    } else {
        $cheched = '';
    }
    echo "          <tr>
                        <td class='text-center'>
                            <input type='radio' class='revision_changer' name='idrevisione' value='".$rs_revisioni[$i]['id']."' ".$cheched.'>
                        </td>
                        <td>
                            Revisione '.($i + 1).' creata il '.Translator::dateToLocale($rs_revisioni[$i]['created_at']).' alle '.date('H:i', strtotime($rs_revisioni[$i]['created_at']))."
                        </td>
                        <td class='text-center'>"; ?>
                            <button type='button' class='btn btn-danger <?php echo $disabled; ?>' onclick='if(confirm("Vuoi cancellare questa revisione?")){$("#idrevisione").val("<?php echo $rs_revisioni[$i]['id']; ?>");$("#form_deleterevision").submit();}' <?php echo $disabled; ?>><i class='fa fa-trash'></i></button>
    <?php
    echo '
                        </td>
                    </tr>';
}
echo '          </table>';

echo '      </div>';
echo '  </div>';
echo "  <div class='row'>";
echo "      <div class='col-md-12 text-center'>";
echo "           <button ".((sizeof($rs_revisioni)<2) ? 'disabled' : '')." type='submit' class='btn btn-primary' ><i class='fa fa-refresh'></i> ".tr('Cambia revisione')."</button>";
echo "      </div>";
echo "  </div>";
echo "</form>";

echo "<form action='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."' method='post' id='form_deleterevision'>";
echo "  <input type='hidden' name='backto' value='record-edit'>";
echo "  <input type='hidden' name='op' value='delete_revision'>";
echo "  <input type='hidden' name='id_plugin' value='".$id_plugin."'>";
echo "  <input type='hidden' name='id_record' value='".$id_record."'>";
echo "  <input type='hidden' name='idrevisione' id='idrevisione' value=''>";
echo '</form>';
?>