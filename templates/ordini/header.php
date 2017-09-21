<?php

echo '
$default_header$
<br>

<div class="text-center" style="height:5mm;">
    <b>$tipo_doc$</b>
</div>

<table class="table table-bordered">
    <tr>
        <td class="border-full text-center">
            <p class="small-bold">'.tr('Nr. documento', [], ['upper' => true]).'</p>
            <p>$numero_doc$</p>
        </td>

        <td class="border-right border-bottom border-top text-center">
            <p class="small-bold">'.tr('Data documento', [], ['upper' => true]).'</p>
            <p>$data$</p>
        </td>

        <td class="border-right border-bottom border-top text-center">
            <p class="small-bold">'.tr('Pagamanto', [], ['upper' => true]).'</p>
            <p>$pagamento$</p>
        </td>

        <td class="border-right border-bottom border-top center text-center">
            <p class="small-bold">'.tr('Foglio', [], ['upper' => true]).'</p>
            <p>{PAGENO}/{nb}</p>
        </td>
    </tr>
</table>';
