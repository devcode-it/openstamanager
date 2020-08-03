<?php

include_once __DIR__.'/../../core.php';

// Informazioni generali sulla riga
$source_type = $source_type ?: filter('source_type');
$source_id = $source_id ?: filter('source_id');
if (empty($source_type) || empty($source_id)) {
    return;
}

$source = $source_type::find($source_id);

$riferimenti = $source->referenceTargets;
$elenco_riferimenti = [];
if (!$riferimenti->isEmpty()) {
    echo '
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Riferimento').'</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($riferimenti as $riferimento) {
        $riga = $riferimento->target;
        $riga_class = get_class($source);

        echo '
        <tr data-id="'.$riga->id.'" data-type="'.$riga_class.'">
            <td>
                <button type="button" class="btn btn-xs btn-danger pull-right" onclick="rimuoviRiferimento(this, \''.addslashes($source_type).'\',\''.$source_id.'\')">
                    <i class="fa fa-trash"></i>
                </button>

                '.$riferimento->target->descrizione.'

                <br>
                <small>'.reference($riferimento->target->parent).'</small>
            </td>
        </tr>';

        // Aggiunta all'elenco dei riferimenti
        $elenco_riferimenti[] = addslashes($riga_class).'|'.$riga->id;
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessun riferimento presente').'.
</div>';
}
