<?php

echo '
<!-- Intestazione fornitore -->
<div class="row">
    <div class="col-xs-6">
        <img src="'.__DIR__.'/logo_azienda.jpg" alt="Logo" border="0"/>
    </div>
    <div class="col-xs-6 text-right">
        <p><b>$f_ragionesociale$</b></p>
        <p>$f_indirizzo$ $f_citta_full$</p>
        <p>'.(!empty($f_telefono) ? tr('Tel').': ' : '').'$f_telefono$</p>
        <p>'.(!empty($f_piva) ? tr('P.Iva').': ' : '').'$f_piva$</p>
    </div>
</div>

<br>

<div class="row">
    <!-- Dati Fattura -->
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>$tipo_doc$</b>
        </div>

        <table class="table">
            <tr>
                <td valign="top" class="border-full text-center">
                    <p class="small-bold">'.strtoupper(tr('Nr. documento')).'</p>
                    <p>$numero_doc$</p>
                </td>

                <td class="border-right border-bottom border-top text-center">
                    <p class="small-bold">'.strtoupper(tr('Data documento')).'</p>
                    <p>$data$</p>
                </td>

                <td class="border-right border-bottom border-top text-center">
                    <p class="small-bold">'.strtoupper(tr('Cliente')).'</p>
                    <p>$c_codice$</p>
                </td>

                <td class="border-right border-bottom border-top center text-center">
                    <p class="small-bold">'.strtoupper(tr('Foglio')).'</p>
                    <p>{PAGENO}/{nb}</p>
                </td>
            </tr>

            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.strtoupper(tr('Pagamento')).'</p>
                    <p>$pagamento$</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.strtoupper(tr('Banca di appoggio')).'</p>
                    <p>$f_appoggiobancario$</p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('IBAN').'</p>
                    <p>$f_codiceiban$</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('BIC').'</p>
                    <p>$f_bic$</p>
                </td>
            </tr>

        </table>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.strtoupper(tr('Spett.le')).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$ $c_citta$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.strtoupper(tr('Partita IVA')).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.strtoupper(tr('Codice fiscale')).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>
        </table>
    </div>
</div>';
