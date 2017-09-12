<?php

echo '
<div class="row">
    <div class="col-xs-6">
        <img src="'.__DIR__.'/logo_azienda.jpg" alt="Logo" border="0"/>
    </div>
    <div class="col-xs-6 text-right">
        <p><b>$f_ragionesociale$</b></p>
        <p>$f_indirizzo$ $f_citta_full$</p>
        <p>'.(!empty($f_piva) ? tr('P.Iva').': ' : '').'$f_piva$</p>
        <p>'.(!empty($f_codicefiscale) ? tr('C.F.').': ' : '').'$f_codicefiscale$</p>
        <p>'.(!empty($f_capsoc) ? tr('Cap.Soc.').': ' : '').'$f_capsoc$</p>
        <p>'.(!empty($f_telefono) ? tr('Tel').': ' : '').'$f_telefono$</p>
    </div>
</div>

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
