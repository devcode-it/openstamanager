<?php

echo '
<!-- Intestazione fornitore -->
$default_header$
<br>

<div class="row">
    <!-- Dati Ddt -->
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>DDT</b>
        </div>

        <table class="table">
            <tr>
                <td valign="top" class="border-full text-center">
                    <p class="small-bold">'.tr('Nr. documento', [], ['upper' => true]).'</p>
                    <p>$numero_doc$</p>
                </td>

                <td class="border-right border-bottom border-top text-center">
                    <p class="small-bold">'.tr('Data documento', [], ['upper' => true]).'</p>
                    <p>$data$</p>
                </td>

                <td class="border-right border-bottom border-top text-center">
                    <p class="small-bold">'.tr('Cliente', [], ['upper' => true]).'</p>
                    <p>$c_codice$</p>
                </td>

                <td class="border-right border-bottom border-top center text-center">
                    <p class="small-bold">'.tr('Foglio', [], ['upper' => true]).'</p>
                    <p>{PAGENO}/{nb}</p>
                </td>
            </tr>

            <tr>
                <td colspan=2 class="border-bottom border-left" style="height:11mm;">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                    <small>$c_piva$</small>
                </td>
                <td colspan=2 class="border-bottom border-right">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>

            <tr>
                <td colspan="4" class="border-full" style="height:11mm;">
                    <p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
                    <p>$pagamento$</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td class="border-full" style="height:20mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$<br>$c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Destinazione diversa', [], ['upper' => true]).'</p>
                    <small>$c_destinazione$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';
