<?php

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Contratto;
use Modules\Contratti\Stato;

$contratto = Contratto::find($id_record);
if (empty($contratto)) {
    return;
}

$is_pianificabile = $contratto->stato->is_pianificabile && !empty($contratto['data_accettazione']); // Contratto permette la pianificazione
$elenco_promemoria = $contratto->promemoria->sortBy('data_richiesta');

$stati_pianificabili = Stato::where('is_pianificabile', 1)->get();
$elenco_stati = $stati_pianificabili->implode('descrizione', ', ');

echo '
<p>'.tr('Puoi <b>pianificare dei "promemoria" o direttamente gli interventi</b> da effettuare entro determinate scadenze').'. '.tr('Per poter pianificare i promemoria, il contratto deve avere <b>data accettazione</b> e <b>data conclusione</b> definita ed essere in uno dei seguenti stati: _LINK_', [
    '_LINK_' => '<b>'.$elenco_stati.'</b>',
]).'

<span class="tip" title="'.tr("I promemoria verranno visualizzati sulla 'Dashboard' e serviranno per semplificare la pianificazione del giorno dell'intervento, ad esempio nel caso di interventi con cadenza mensile").'">
    <i class="fa fa-question-circle-o"></i>
</span></p>';

echo '
<hr>
<div class="row">
    <div class="col-md-9">
        {[ "type": "select", "placeholder": "'.tr('Tipo di promemoria').'", "name": "id_tipo_promemoria", "required": 1, "ajax-source": "tipiintervento", "class": "unblockable" ]}
    </div>

    <div class="col-md-3">
        <button type="button" '.(!empty($is_pianificabile) ? '' : 'disabled').' title="Aggiungi un nuovo promemoria da pianificare." data-toggle="tooltip" class="btn btn-primary btn-block tip" id="add_promemoria">
            <i class="fa fa-plus"></i> '.tr('Nuovo promemoria').'
        </button>
    </div>
</div>
<hr>';

// Nessun intervento pianificato
if (!$elenco_promemoria->isEmpty()) {
    echo '
<table class="table table-condensed table-striped table-hover">
    <thead>
        <tr>
            <th>'.tr('Data').'</th>
            <th>'.tr('Tipo intervento').'</th>
            <th>'.tr('Descrizione').'</th>
            <th>'.tr('Intervento').'</th>
            <th>'.tr('Sede').'</th>
            <th>'.tr('Impianti').'</th>
            <th>'.tr('Materiali').'</th>
            <th>'.tr('Allegati').'</th>
            <th class="text-right" >'.tr('Opzioni').'</th>
        </tr>
    </thead>
    <tbody>';

    // Elenco promemoria
    foreach ($elenco_promemoria as $promemoria) {
        // Sede
        if ($promemoria['idsede'] == '-1') {
            echo '- '.tr('Nessuna').' -';
        } elseif (empty($promemoria['idsede'])) {
            $info_sede = tr('Sede legale');
        } else {
            $info_sede = $dbo->fetchOne("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($promemoria->idsede))['descrizione'];
        }

        // Intervento svolto
        $intervento = $promemoria->intervento;
        if (!empty($intervento)) {
            $info_intervento = Modules::link('Interventi', $intervento['id'], tr('Intervento num. _NUM_ del _DATE_', [
                '_NUM_' => $intervento->codice,
                '_DATE_' => dateFormat($intervento->data_richiesta),
            ]));

            $disabled = 'disabled';
            $title = 'Per eliminare il promemoria, eliminare prima l\'intervento associato.';
        } else {
            $info_intervento = '- '.('Nessuno').' -';
            $disabled = '';
            $title = 'Elimina promemoria...';
        }

        // data_conclusione contratto
        if (date('Y', strtotime($contratto['data_conclusione'])) < 1971) {
            $contratto['data_conclusione'] = '';
        }

        // Informazioni sugli impianti
        $info_impianti = '';
        if (!empty($promemoria['idimpianti'])) {
            $impianti = $dbo->fetchArray('SELECT id, matricola, nome FROM my_impianti WHERE id IN ('.($promemoria['idimpianti']).')');

            foreach ($impianti as $impianto) {
                $info_impianti .= Modules::link('MyImpianti', $impianto['id'], tr('_NOME_ (_MATRICOLA_)', [
                    '_NOME_' => $impianto['nome'],
                    '_MATRICOLA_' => $impianto['matricola'],
                ])).'<br>';
            }
        }

        // Informazioni sulle righe
        $info_righe = '';
        $righe = $promemoria->getRighe();
        foreach ($righe as $riga) {
            $info_righe .= tr('_QTA_ _UM_ x _DESC_', [
                '_DESC_' => ($riga->isArticolo() ? Modules::link('Articoli', $riga['idarticolo'], $riga['descrizione']) : $riga['descrizione']),
                '_QTA_' => Translator::numberToLocale($riga['qta']),
                '_UM_' => $riga['um'],
            ]).'<br>';
        }

        // Informazioni sugli allegati
        $info_allegati = '';
        $allegati = $promemoria->uploads();
        foreach ($allegati as $allegato) {
            $info_allegati .= tr(' _NOME_ (_ORIGINAL_)', [
                '_ORIGINAL_' => $allegato['original_name'],
                '_NOME_' => $allegato['name'],
            ]).'<br>';
        }

        echo '
            <tr>
                <td>'.Translator::dateToLocale($promemoria['data_richiesta']).'</td>
                <td>'.$promemoria['tipointervento'].'</td>
                <td>'.nl2br($promemoria['richiesta']).'</td>
                <td>'.$info_intervento.'</td>
                <td>'.$info_sede.'</td>
                <td>'.$info_impianti.'</td>
                <td>'.$info_righe.'</td>
                <td>'.$info_allegati.'</td>
                <td class="text-right">

                <button type="button" class="btn btn-warning btn-sm" title="Pianifica..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica\', \''.$structure->fileurl('pianificazione.php').'?id_module='.$id_module.'&id_plugin='.$structure['id'].'&id_parent='.$id_record.'&id_record='.$promemoria['id'].'\');"'.((!empty($is_pianificabile)) ? '' : ' disabled').'>
                    <i class="fa fa-clock-o"></i>
                </button>

                <button type="button" '.$disabled.' class="btn btn-primary btn-sm '.$disabled.' " title="Pianifica intervento ora..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.$rootdir.'/add.php?id_module='.Modules::get('Interventi')['id'].'&ref=interventi_contratti&idcontratto='.$id_record.'&idcontratto_riga='.$promemoria['id'].'\');"'.(!empty($is_pianificabile) ? '' : ' disabled').'>
                    <i class="fa fa-calendar"></i>
                </button>

                <button type="button" '.$disabled.' title="'.$title.'" class="btn btn-danger btn-sm ask '.$disabled.'" data-op="delete-promemoria" data-id="'.$promemoria['id'].'" data-id_plugin="'.$id_plugin.'" data-backto="record-edit">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>';
    }
    echo '
    </tbody>
</table>';

    if (!empty($promemorias)) {
        echo '
<br>
<div class="pull-right">
    <button type="button" title="Elimina tutti i promemoria non associati ad intervento" class="btn btn-danger ask tip" data-op="delete-non-associati" data-id_plugin="'.$id_plugin.'" data-backto="record-edit">
        <i class="fa fa-trash"></i> '.tr('Elimina promemoria').'
    </button>
</div>';
    }
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-warning"></i> '.tr('Nessun promemoria pianificato per il contratto corrente').'.
</div>';
}

echo '
<script type="text/javascript">
    $("#add_promemoria").click(function() {
        var id_tipo = $("#id_tipo_promemoria").val();
        if (!id_tipo){
            swal("'.tr('Nessun tipo di promemoria selezionato!').'", "'.tr('Per continuare devi selezionare una tipologia per il promemoria!').'", "error");
            return;
        }

        var restore = buttonLoading("#add_promemoria");
        $.post(globals.rootdir + "/actions.php?id_plugin='.$structure['id'].'&id_parent='.$id_record.'", {
            op: "add-promemoria",
            data_richiesta: "'.$contratto->data_accettazione.'",
            idtipointervento: id_tipo,
        }).done(function(data) {
            launch_modal("Nuovo promemoria", globals.rootdir + "/plugins/'.$structure['directory'].'/pianificazione.php?id_plugin='.$structure['id'].'&id_parent='.$id_record.'&id_record=" + data + "&add=1");

            buttonRestore("#add_promemoria", restore);
        });
    });
</script>';
