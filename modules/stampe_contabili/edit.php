<?php

include_once __DIR__.'/../../core.php';

echo '

<div class="alert alert-warning">
    <i class="fa fa-warning"></i> <b>'.tr('Attenzione', [], ['upper']).':</b> '.tr('le suddette stampe contabili non sono da considerarsi valide ai fini fiscali').'.
</div>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Registri IVA').'</h3>
            </div>

            <div class="panel-body">';

echo '
    <button type="button" class="btn btn-primary col-md-5" data-toggle="modal" data-title="'.tr('Stampa registro IVA vendite').'" data-href="'.ROOTDIR.'/modules/stampe_contabili/stampe_contabili.php?dir=entrata&nome_stampa=Registro IVA&id_record='.$id_record.'" ><i class="fa fa-print fa-2x"></i><br>'.tr('Stampa registro').'<br>'.tr('IVA vendite').'</button>';

echo '
    <button type="button" class="btn btn-primary col-md-5 col-md-push-2" data-toggle="modal" data-title="'.tr('Stampa registro IVA acquisti').'" data-href="'.ROOTDIR.'/modules/stampe_contabili/stampe_contabili.php?dir=uscita&nome_stampa=Registro IVA&id_record='.$id_record.'" ><i class="fa fa-print fa-2x"></i><br>'.tr('Stampa registro').'<br>'.tr('IVA acquisti').'</button>';

echo '
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Comunicazione dati fatture (ex-spesometro)<br> dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Spesometro', $id_record, 'btn-primary col-md-5', '<br>'.tr('Stampa dati fatture').'<br>'.tr(' vendite e acquisti'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Fatturato<br> dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Fatturato', $id_record, 'btn-primary col-md-5', '<br>'.tr('Stampa fatturato').'<br>'.tr('in entrata'), '|default| fa-2x', 'dir=entrata').'

                '.Prints::getLink('Fatturato', $id_record, 'btn-primary col-md-5 col-md-push-2', '<br>'.tr('Stampa fatturato').'<br>'.tr('in uscita'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>
</div>';
