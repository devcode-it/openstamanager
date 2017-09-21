<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="row">
    <div class="col-xs-12 col-md-4">
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

    <div class="col-xs-12 col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Spesometro dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                '.Prints::getLink('Spesometro', $id_record, 'btn-primary', '<br>'.tr('Stampa').'<br>'.tr('spesometro'), '|default| fa-2x', 'dir=uscita').'
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-md-4">
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
