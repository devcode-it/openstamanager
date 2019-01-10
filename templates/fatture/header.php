<?php

if ($options['hide_header']) {
    echo '
	<!-- Intestazione vuota fornitore -->
	<div class="row" style="height:111px;">
		<div class="col-xs-6">
		</div>
		<div class="col-xs-6 text-right">
		</div>
	</div>';
} else {
    echo '
	<!-- Intestazione fornitore -->
	$default_header$';
}

echo '
<br>
<div class="row">
    <!-- Dati Fattura -->
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>$tipo_doc$</b>
        </div>

        <table class="table" style="overflow: visible">
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
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
                    <p>$pagamento$</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Banca di appoggio', [], ['upper' => true]).'</p>
                    <p><small>$appoggiobancario$</small></p>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('IBAN').'</p>
                    <p>$codiceiban$</p>
                </td>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('BIC').'</p>
                    <p>$bic$</p>
                </td>
            </tr>

        </table>
    </div>

    <div class="col-xs-5 col-xs-offset-1">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full"'.(!$fattura_accompagnatoria ? ' style="height:20mm;"' : '').'>
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$<br>$c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>';

if ($fattura_accompagnatoria) {
    echo '
            <tr>
                <td colspan=2 class="border-full">
                    <p class="small-bold">'.tr('Destinazione diversa', [], ['upper' => true]).'</p>
                    <p>$c_destinazione$</p>
                </td>
            </tr>';

    $settings['header-height'] += 13;
}

echo '
        </table>
    </div>
</div>';
