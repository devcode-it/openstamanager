<?php

use Modules\Anagrafiche\Anagrafica;

include_once __DIR__.'/../../core.php';

/*
    REGISTRI VIAGGIO DELL'AUTOMEZZO
*/
$q_viaggi = 'SELECT * FROM an_automezzi_viaggi WHERE idsede='.prepare($id_record).' ORDER BY data_inizio DESC';
$rs_viaggi = $dbo->fetchArray($q_viaggi);

if (!empty($rs_viaggi)) {
    echo '
<div style="max-height: 300px; overflow: auto;">
    <table class="table table-striped table-hover table-sm">
        <tr>
            <th width="10%">'.tr('Data inizio').'</th>
            <th width="10%">'.tr('Data fine').'</th>
            <th width="12%">'.tr('Tecnico').'</th>
            <th width="7%">'.tr('Km inizio').'</th>
            <th width="7%">'.tr('Km fine').'</th>
            <th width="15%">'.tr('Destinazione').'</th>
            <th>'.tr('Motivazione').'</th>
            <th width="10%">'.tr('Rifornimenti').'</th>
            <th width="10%" class="text-center">'.tr('Azioni').'</th>
            <th width="3%" class="text-center">'.tr('Firma').'</th>
        </tr>';

    foreach ($rs_viaggi as $viaggio) {
        $disabled = !empty($viaggio['firma_data']) && $user->gruppo == 'Tecnici' ? 'disabled' : '';
        $firma_disabled = '';
        $tecnico = Anagrafica::find($viaggio['idtecnico']);

        if ($user->gruppo == 'Tecnici' && $user->idanagrafica != $viaggio['idtecnico']) {
            $disabled = 'disabled';
            $firma_disabled = 'disabled';
        }

        // Recupero i rifornimenti per questo viaggio
        $q_rifornimenti = 'SELECT * FROM an_automezzi_rifornimenti WHERE idviaggio='.prepare($viaggio['id']).' ORDER BY data ASC';
        $rifornimenti = $dbo->fetchArray($q_rifornimenti);

        echo '
        <tr>
            <td>
                '.Translator::timestampToLocale($viaggio['data_inizio']).'
            </td>
            <td>
                '.Translator::timestampToLocale($viaggio['data_fine'] ?: '').'
            </td>
            <td>
                '.$tecnico->ragione_sociale.'
            </td>
            <td>
                '.$viaggio['km_inizio'].'
            </td>
            <td>
                '.$viaggio['km_fine'].'
            </td>
            <td>
                '.$viaggio['destinazione'].'
            </td>
            <td>
                '.$viaggio['motivazione'].'
            </td>
            <td class="text-center">';

        // Pulsanti rifornimenti - uno per ogni rifornimento
        if (!empty($rifornimenti)) {
            foreach ($rifornimenti as $rifornimento) {
                echo '
                <button class="btn btn-sm btn-info" data-href="'.$module->fileurl('modals/manage_rifornimento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idrifornimento='.$rifornimento['id'].'" data-card-widget="modal" data-title="'.tr('Modifica rifornimento').'" data-toggle="tooltip" title="'.tr('Rifornimento del').' '.Translator::timestampToLocale($rifornimento['data']).' - '.$rifornimento['luogo'].' ('.moneyFormat($rifornimento['costo']).')" '.$disabled.'>
                    <i class="fa fa-tint"></i>
                </button> ';
            }
        }

        // Pulsante firma - verde se firmato, grigio se non firmato
        $firmato = !empty($viaggio['firma_data']);
        $btn_firma_class = $firmato ? 'btn-success' : 'btn-warning';
        $btn_firma_icon = $firmato ? 'fa-check' : 'fa-clock-o';
        $btn_firma_title = $firmato ? tr('Firmato da').' '.$viaggio['firma_nome'].' '.tr('il').' '.Translator::timestampToLocale($viaggio['firma_data']) : tr('Firma viaggio');

        echo '
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-primary" data-href="'.$module->fileurl('modals/manage_rifornimento.php').'?idviaggio='.$viaggio['id'].'" data-card-widget="modal" data-title="'.tr('Aggiungi rifornimento').'" data-toggle="tooltip" title="'.tr('Aggiungi rifornimento').'" '.$disabled.'>
                    <i class="fa fa-plus"></i> <i class="fa fa-tint"></i>
                </button>
                <button class="btn btn-warning btn-sm" data-href="'.$module->fileurl('modals/manage_viaggio.php').'?id_module='.$id_module.'&id_record='.$id_record.'&idviaggio='.$viaggio['id'].'" data-card-widget="modal" data-title="'.tr('Modifica viaggio').'" '.$disabled.'>
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm ask" data-backto="record-edit" data-op="delviaggio" data-id="'.$viaggio['id'].'" data-msg="'.tr("Rimuovere il viaggio dal registro?").'" '.$disabled.'>
                    <i class="fa fa-trash"></i>
                </button>
            </td>
            <td class="text-center">
                <button class="btn btn-sm '.$btn_firma_class.'" data-href="'.$module->fileurl('modals/firma_viaggio.php').'?id_record='.$id_record.'&idviaggio='.$viaggio['id'].'" data-card-widget="modal" data-title="'.tr('Firma viaggio').'" data-toggle="tooltip" title="'.$btn_firma_title.'" '.$firma_disabled.'>
                    <i class="fa '.$btn_firma_icon.'"></i> <i class="fa fa-pencil"></i>
                </button>
            </td>
        </tr>';
    }

    echo '
    </table>
</div>';
} else {
    echo '
<p>'.tr('Nessun viaggio inserito').'...</p>';
}

