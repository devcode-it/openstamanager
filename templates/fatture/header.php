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

if ($options['hide-header']) {
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
        <br>

		<table class="table">
            <tr>
                <td valign="top" class="border-bottom border-top text-center">
                    <p class="small-bold text-muted">'.tr('Nr. documento', [], ['upper' => true]).'</p>
                    <p>$numero$</p>
                </td>

                <td class="border-bottom border-top text-center">
                    <p class="small-bold text-muted">'.tr('Data documento', [], ['upper' => true]).'</p>
                    <p>$data$</p>
                </td>

                <td class="border-bottom border-top center text-center">
                    <p class="small-bold text-muted">'.tr('Foglio', [], ['upper' => true]).'</p>
                    <p>{PAGENO}/{nb}</p>
                </td>
            </tr>


        </table>
    </div>

	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table border-bottom" >
            <tr>
                <td colspan=2 '.(!$fattura_accompagnatoria ? ' style="height:20mm;"' : '').'>
                    <p class="small-bold text-muted">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
					<p>'.(!empty($c_indirizzo) ? $c_indirizzo : '').(!empty($c_citta_full) ? '<br>'.$c_citta_full : '').'</p>';
if (empty($destinazione)) {
    echo '                
					<small>'.(!empty($c_codice_destinatario) ? tr('Cod.Fatturazione').': '.$c_codice_destinatario : '').'</small>';
}
echo '
                </td>
            </tr>

            <tr>
                <td>
                    <p class="small-bold text-muted">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td >
                    <p class="small-bold text-muted">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>';

if (!empty($destinazione)) {
    echo '
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold text-muted">'.tr('Destinazione diversa', [], ['upper' => true]).'</p>
                    <p><small>$c_destinazione$</small></p>
                    <p><small>'.(!empty($c_codice_destinatario) ? tr('Cod.Fatturazione').': '.$c_codice_destinatario : '').'</small></p>
                </td>
            </tr>';
}

echo '
        </table>
    </div>
</div>';
