<?php

include_once __DIR__.'/../../core.php';

echo '

<div class="alert alert-warning">
    <i class="fa fa-warning"></i> <b>'.tr('ATTENZIONE', ['upper']).':</b> '.tr('le suddette stampe contabili non sono da considerarsi valide ai fini fiscali').'.
</div>

<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Registri iva dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                <a class="btn btn-primary" href="'.$rootdir.'/pdfgen.php?ptype=registro_iva&dir=entrata" target="_blank">
                    <i class="fa fa-print fa-2x"></i><br>
                    '.tr('Stampa registro').'<br>
                    '.tr('IVA vendite').'
                </a>

                <a class="btn btn-primary" href="'.$rootdir.'/pdfgen.php?ptype=registro_iva&dir=uscita" target="_blank">
                    <i class="fa fa-print fa-2x"></i><br>
                    '.tr('Stampa registro').'<br>
                    '.tr('IVA acquisti').'
                </a>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">'.tr('Comunicazione dati fatture (ex-spesometro) dal _START_ al _END_', [
                    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
                    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
                ]).'</h3>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <a class="btn btn-primary" href="'.$rootdir.'/pdfgen.php?ptype=spesometro" target="_blank">
                            <i class="fa fa-print fa-2x"></i><br>
                            '.tr('Stampa').'<br>
                            '.tr('dati fatture').'
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';
