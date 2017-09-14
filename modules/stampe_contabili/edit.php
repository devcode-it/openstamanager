<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Registri iva').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <a class="btn btn-primary btn-block" href="'.$rootdir.'/pdfgen.php?ptype=registro_iva&dir=entrata" target="_blank">
                    <i class="fa fa-print"></i> '.tr('Stampa registro iva vendite').'
                </a>
            </div>

            <div class="col-md-6">
                <a class="btn btn-primary btn-block" href="'.$rootdir.'/pdfgen.php?ptype=registro_iva&dir=uscita" target="_blank">
                    <i class="fa fa-print"></i> '.tr('Stampa registro iva acquisti').'
                </a>
            </div>
        </div>
    </div>
</div>';
