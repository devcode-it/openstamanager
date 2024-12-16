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
<div class="col-xs-5">
    <div class="text-center" style="height:5mm;">
        <b>PREVENTIVO</b>
    </div>
    <br>

    <table class="table text-center">
        <tr>
            <td valign="top" class="border-bottom border-top">
                <p class="small-bold text-muted">'.tr('Nr. documento', [], ['upper' => true]).'</p>
                <p>'.$documento['numero'].'</p>
            </td>

            <td class="border-bottom border-top">
                <p class="small-bold text-muted">'.tr('Data documento', [], ['upper' => true]).'</p>
                <p>'.Translator::dateToLocale($documento['data_bozza']).'</p>
            </td>

            <td class="border-bottom border-top">
                <p class="small-bold text-muted">'.tr('Foglio', [], ['upper' => true]).'</p>
                <p> {PAGENO}/{nb} </p>
            </td>
        </tr>';
if (!empty($impianti)) {
    $list = [];
    foreach ($impianti as $impianto) {
        $list[] = $impianto['nome']." <span style='color:#777;'>(".$impianto['matricola'].')</span>';
    }

    echo '
                <br>
                <p class="small-bold text-muted">'.tr('Impianti', [], ['upper' => true]).'</p>
                <p><small>'.implode(', ', $list).'</small></p>';
}
echo '
    </table>
</div>
	<div class="col-xs-6 pull-right">
        <table class="table border-bottom">
            <tr>
                <td colspan=2 style="height:16mm;">
                    <p class="small-bold text-muted ">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$</p>
                    <p>$c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom">
                    <p class="small-bold text-muted">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom">
                    <p class="small-bold text-muted">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>';

if (!empty($destinazione)) {
    echo '
            <tr>
                <td class="border-bottom">
                    <p class="small-bold text-muted">'.tr('Destinazione diversa', [], ['upper' => true]).'</p>
                </td>
                <td class="border-bottom text-right">
                    <small>'.$destinazione.'</small>
                </td>
            </tr>';
}
echo '
        </table>
    </div>
</div>';
