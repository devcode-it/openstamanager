<?php

include_once __DIR__.'/../../core.php';

echo '
<h4><b>'.tr('Scadenze dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($date_start),
    '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]).'</b></h4>

<table class="table table-striped table-bordered" id="contents">
    <thead>
        <tr>
            <th width="10%">'.tr('Documento', [], ['upper' => true]).'</th>
            <th width="30%">'.tr('Anagrafica', [], ['upper' => true]).'</th>
            <th width="30%">'.tr('Tipo di pagamento', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Data scadenza', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Importo', [], ['upper' => true]).'</th>
            <th width="10%" class="text-center">'.tr('Già pagato', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';
