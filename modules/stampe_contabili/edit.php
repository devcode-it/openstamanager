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
                <h3 class="panel-title">'.tr('Registri iva dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Registro IVA', $id_record, 'btn-primary', '<br>'.tr('Stampa registro').'<br>'.tr('IVA vendite'), '|default| fa-2x', 'dir=entrata').'

                '.Prints::getLink('Registro IVA', $id_record, 'btn-primary', '<br>'.tr('Stampa registro').'<br>'.tr('IVA acquisti'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Comunicazione dati fatture (ex-spesometro) dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Spesometro', $id_record, 'btn-primary', '<br>'.tr('Stampa').'<br>'.tr('dati fatture'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Fatturato dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Fatturato', $id_record, 'btn-primary', '<br>'.tr('Stampa fatturato').'<br>'.tr('in entrata'), '|default| fa-2x', 'dir=entrata').'

                '.Prints::getLink('Fatturato', $id_record, 'btn-primary', '<br>'.tr('Stampa fatturato').'<br>'.tr('in uscita'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>
</div>';
