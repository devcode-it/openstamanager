<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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

<div class="row">
    <!-- Dati Fattura -->
    <div class="col-xs-6">
		<div class="text-center" style="height:5mm;">
			<b>$tipo_doc$</b>
		</div>

		<table class="table">
            <tr>
                <td valign="top" class="border-full text-center">
                    <p class="small-bold">'.tr('Nr. documento', [], ['upper' => true]).'</p>
                    <p>$numero$</p>
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
                <td colspan="2" style="height:10mm;padding-top:2mm;white-space: nowrap;">
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

	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full"'.(!$fattura_accompagnatoria ? ' style="height:20mm;"' : '').'>
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
					<p>'.(!empty($c_indirizzo) ? $c_indirizzo : '').(!empty($c_citta_full) ? '<br>'.$c_citta_full : '').'</p>
					<small>'.(!empty($c_codice_destinatario) ? tr('Cod.Fatturazione').': '.$c_codice_destinatario : '').'</small>
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

if (!empty($destinazione)) {
    echo '
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Destinazione diversa', [], ['upper' => true]).'</p>
                    <small>$c_destinazione$</small>
                </td>
            </tr>';
}

echo '
        </table>
    </div>
</div>';
