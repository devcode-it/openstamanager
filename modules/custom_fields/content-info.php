<?php

echo '
<!-- Istruzioni per il contenuto -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title">'.tr('Istruzioni per il campo _FIELD_', [
            '_FIELD_' => tr('Contenuto'),
        ]).'</h3>
    </div>

    <div class="box-body">
        <p>'.tr('Le seguenti sequenze di testo vengono sostituite nel seguente modo').':</p>
        <ul>';

$list = [
    'label' => tr('Nome'),
    'name' => tr('Nome HTML'),
];

foreach ($list as $key => $value) {
    echo '
            <li>'.tr('_TEXT_ con il valore del campo "_FIELD_"', [
                '_TEXT_' => '<code>|'.$key.'|</code>',
                '_FIELD_' => $value,
            ]).'</li>';
}

echo '
            <li>'.tr('_TEXT_ con il valore impostato per il record', [
                '_TEXT_' => '<code>|value|</code>',
            ]).'</li>';

echo '
        </ul>
    </div>
</div>';
