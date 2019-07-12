<?php

include_once __DIR__.'/../../core.php';

echo '
<h4><b>'.tr('Riepilogo interventi dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($date_start),
    '_END_' => Translator::dateToLocale($date_end),
    ], ['upper' => true]).'</b></h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th colspan="2">'.tr('Documento', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Imponibile', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Sconto', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Totale imponibile', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';
