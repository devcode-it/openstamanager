<?php

use Modules\Articoli\Articolo;

include_once __DIR__.'/../../core.php';

$articolo_originale = Articolo::find($id_record);
$combinazione = $articolo_originale->combinazione;

if (empty($combinazione)) {
    echo '
<script>
$(document).ready(function (){
    $("#link-tab_'.$id_plugin.'").addClass("disabled");
})
</script>';
}

echo '
<button type="button" class="btn btn-warning pull-right" onclick="visualizzaCombinazione(this)">
    <i class="fa fa-external-link"></i> '.tr('Visualizza combinazione').'
</button>

<div class="clearfix"></div>
<br>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Varianti disponibili (Articoli)').'</h3>
    </div>

    <div class="card-body">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th width="10%">'.tr('Foto').'</th>
                    <th>'.tr('Variante').'</th>
                    <th>'.tr('Articolo').'</th>
                </tr>
            </thead>

            <tbody>';

$articoli = $combinazione->articoli;
foreach ($articoli as $articolo) {
    echo '
                <tr data-id="'.$articolo->id.'">
                    <td><img class="img-thumbnail img-responsive" src="'.$articolo->image.'"></td>
                    <td>'.$articolo->nome_variante.'</td>
                    <td>
                        '.Modules::link('Articoli', $articolo->id, $articolo->codice.' - '.$articolo->getTranslation('title')).'
                        '.($articolo->id == $articolo_originale->id ? '<span class="badge pull-right">'.tr('Articolo corrente').'</span>' : '').'
                    </td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>
</div>

<script>
function visualizzaCombinazione(button) {
    // Redirect
    redirect(globals.rootdir + "/editor.php", {
       id_module: "'.$combinazione->getModule()->id.'",
       id_record: "'.$combinazione->id.'",
   });
}
</script>';
