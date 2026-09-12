<?php

include_once __DIR__.'/../../core.php';

echo '
<style>
    .shipping-label {
        width: 100%;
        padding: 3mm 4mm;
        box-sizing: border-box;
        font-family: sans-serif;
        color: #222;
    }
    .shipping-label .section-title {
        margin: 0 0 2mm;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .shipping-label .separator {
        border-top: .5mm solid #000;
        margin: 4mm 0;
    }
    .shipping-label .sender {
        min-height: 24mm;
        font-size: 10pt;
        line-height: 1.35;
    }
    .shipping-label .recipient {
        min-height: 52mm;
        padding: 3mm 0;
        font-size: 16pt;
        line-height: 1.4;
    }
    .shipping-label .recipient strong {
        font-size: 18pt;
    }
    .shipping-label .reference {
        padding-top: 1mm;
        font-size: 9pt;
    }
    .shipping-label .reference-table {
        width: 100%;
        border-collapse: collapse;
    }
    .shipping-label .reference-table td {
        padding: 1mm 2mm 0 0;
        vertical-align: top;
    }
    .shipping-label .notes {
        margin-top: 3mm;
        font-size: 8.5pt;
        line-height: 1.35;
    }
</style>

<div class="shipping-label">
    <div class="sender">
        <div class="section-title">'.tr('Mittente').'</div>
        $mittente$
    </div>

    <div class="separator"></div>

    <div class="recipient">
        <div class="section-title">'.tr('Destinatario').'</div>
        $destinatario$
    </div>

    <div class="separator"></div>

    <div class="reference">
        <table class="reference-table">
            <tr>
                <td><strong>$tipo_doc$</strong></td>
                <td class="text-right"><strong>'.tr('N.').' $numero$</strong></td>
            </tr>
            <tr>
                <td>'.tr('Data').': $data$</td>
                <td class="text-right">'.tr('Colli').': $n_colli$</td>
            </tr>
        </table>
        $notes_block$
    </div>
</div>';
