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

echo '
$default_header$

<div class="row">

    <!-- Dati Ordine -->
    <div class="col-xs-6">
		<div class="text-center" style="height:5mm;">
			<b>$tipo_doc$</b>
		</div>

		<table class="table">
			<tr>
				<td class="border-full text-center">
					<p class="small-bold">'.tr('Nr. documento', [], ['upper' => true]).'</p>
					<p>$numero$</p>
				</td>

				<td class="border-right border-bottom border-top text-center">
					<p class="small-bold">'.tr('Data documento', [], ['upper' => true]).'</p>
					<p>$data$</p>
				</td>

                <td class="border-right border-bottom border-top text-center">
                    <p class="small-bold">'.($documento->direzione == 'entrata' ? tr('Cliente', [], ['upper' => true]) : tr('Fornitore', [], ['upper' => true])).'</p>
                    <p>$c_codice$</p>
                </td>

				<td class="border-right border-bottom border-top center text-center">
					<p class="small-bold">'.tr('Foglio', [], ['upper' => true]).'</p>
					<p>{PAGENO}/{nb}</p>
				</td>
			</tr>

			<tr>
                <td colspan="4" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
                    <p>$pagamento$</p>
                </td>
            </tr>
		</table>
	</div>

	<!-- Dati Cliente/Fornitore -->
	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td class="border-full" style="height:20mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
					<p>$c_indirizzo$<br> $c_citta_full$</p>
					<p>$c_telefono$ $c_cellulare$</p>
                </td>
            </tr>
        </table>
    </div>


</div>';
