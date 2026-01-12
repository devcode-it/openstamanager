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

include_once __DIR__.'/../../core.php';
use Modules\Articoli\Marca;

$immagine_articolo = $articolo->image ?: App::getPaths()['img'].'/logo_header.png';

$validita = [
    'days' => 'giorni',
    'months' => 'mesi',
    'years' => 'anni',
];

echo '
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-vcard"></i> '.tr('Articolo').'</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <img src="'.$immagine_articolo.'" class="img-fluid img-thumbnail">
                    </div>

                    <div class="col-md-9">';
// Articolo
if ($articolo->marca) {
    echo '
                            <p class="float-right badge badge-info p-2"><i class="fa fa-tag mr-1"></i>
                                '.($articolo->marca ? ($articolo->marca->link ? '<a href="'.$articolo->marca->link.'" target="_blank" rel="noopener noreferrer" class="text-white"> '.$articolo->marca->name.'</a>' : $articolo->marca->name.' ') : '').
        ($articolo->id_modello ? ' <small><i class="fa fa-chevron-right"></i></small> '.Marca::where('parent', $articolo->id_marca)->where('id', $articolo->id_modello)->first()->name.' ' : '')
    .'</p>';
}
if ($articolo->id_categoria) {
    echo '
                            <p class="text-muted mb-2"><i class="fa fa-folder-open mr-1"></i>'.$articolo->categoria->getTranslation('title').
    ($articolo->sottocategoria ? ' <small><i class="fa fa-chevron-right"></i></small> '.$articolo->sottocategoria->getTranslation('title') : '').
    '</p>';
}
echo '
                        <h4 class="mb-2 text-primary"><b>'.$articolo->getTranslation('title').'</b> '.($articolo->attivo ? '<span class="badge badge-success"><i class="fa fa-check"></i> '.tr('Attivo').'</span>' : '<span class="badge badge-danger"><i class="fa fa-times"></i> '.tr('Disattivato').'</span>').'</h4>
                        <p class="mb-2"><b>'.$articolo->codice.'</b></p>
                        '.($articolo->note ? '<p class="alert alert-warning p-2 mt-2"><i class="fa fa-pencil-square-o mr-1"></i> '.$articolo->note.'</p>' : '');
if (!empty($articolo->barcode)) {
    echo '
                            <div class="readmore">
                                <table class="table">
                                    <tbody>';
    foreach ($articolo->barcodes as $barcode) {
        echo '
                                        <tr>
                                            <td><i class="fa fa-barcode mr-1"></i> '.$barcode.'</td>
                                        </tr>';
    }
    echo '
                                    </tbody>
                                </table>
                            </div>';
}
echo '
                    </div>
                </div>
            </div>
        </div>
    </div>';

if ($user->is_admin) {
    $sedi = $dbo->fetchArray('SELECT * FROM ((SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi WHERE idanagrafica='.prepare(setting('Azienda predefinita')).')) sedi WHERE id IN(SELECT idsede FROM mg_movimenti WHERE idarticolo='.prepare($articolo->id).')');
} else {
    $sedi = $dbo->fetchArray('SELECT * FROM ((SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi WHERE idanagrafica='.prepare(setting('Azienda predefinita')).')) sedi WHERE id IN(SELECT idsede FROM mg_movimenti WHERE idarticolo='.prepare($articolo->id).') AND id IN(SELECT idsede FROM zz_user_sedi WHERE id_user='.prepare($user['id']).')');
}

$giacenze = $articolo->getGiacenze();

// Giacenze
echo '
    <div class="col-md-4">
        <div class="card card-success card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-archive"></i> '.tr('Giacenze').'</h3>
            </div>
            <div class="card-body">';
if ($articolo->servizio) {
    echo '
                <div class="alert alert-info text-center" role="alert">
                    <i class="fa fa-info-circle mr-1"></i> '.tr('Questo articolo è un servizio').'.
                </div>';
} else {
    echo '
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>'.tr('Sede').'</th>
                            <th class="text-right">'.tr('Giacenza').'</th>
                            '.($articolo->fattore_um_secondaria != 0 ? '<th class="text-right">'.tr('U.m. secondaria').'</th>' : '').'
                        </tr>
                    </thead>
                    <tbody>';
    foreach ($sedi as $sede) {
        $threshold_sede = $dbo->fetchOne('SELECT `threshold_qta` FROM `mg_scorte_sedi` WHERE `id_sede` = '.prepare($sede['id']).' AND `id_articolo` = '.prepare($articolo->id))['threshold_qta'];
        $giacenza_value = $giacenze[$sede['id']][0];
        $is_low = $giacenza_value < $threshold_sede;

        // Format the quantity with the appropriate decimal places
        $formatted_qty = numberFormat($giacenza_value, null);
        $formatted_secondary = $articolo->fattore_um_secondaria != 0 ? numberFormat($giacenza_value * $articolo->fattore_um_secondaria, null) : '';

        echo '
                    <tr class="'.($is_low ? 'text-danger' : '').'">
                        <td>'.($is_low ? '<i class="fa fa-exclamation-triangle mr-1"></i>' : '').$sede['nomesede'].'</td>
                        <td class="text-right">'.$formatted_qty.' '.$articolo->um.'</td>
                        '.($articolo->fattore_um_secondaria != 0 ? '<td class="text-right"><i class="fa fa-chevron-right pull-left"></i> '.$formatted_secondary.' '.$articolo->um_secondaria.'</td>' : '').'
                    </tr>';
    }
    echo '
                    </tbody>
                </table>';
}
echo '
            </div>
        </div>
    </div>';
// Panoramica
echo '
    <div class="col-md-2">
        <div class="card card-warning card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-info-circle"></i> '.tr('Informazioni').'</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover">
                    <tbody>
                        <tr>
                            <td><i class="fa fa-calendar-check-o mr-1"></i> '.tr('Garanzia').'</td>
                            <td class="text-right font-weight-bold">'.($articolo->garanzia ? $articolo->garanzia.' '.$validita[$articolo->tipo_garanzia] : '<span class="text-muted">-</span>').'</td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-qrcode mr-1"></i> '.tr('Serial number').'</td>
                            <td class="text-right">'.($articolo->abilita_serial ? '<span class="badge badge-success"><i class="fa fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fa fa-times"></i></span>').'</td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-map-marker mr-1"></i> '.tr('Ubicazione').'</td>
                            <td class="text-right font-weight-bold">'.($articolo->ubicazione ?: '<span class="text-muted">-</span>').'</td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-balance-scale mr-1"></i> '.tr('Peso lordo').'</td>
                            <td class="text-right font-weight-bold">'.($articolo->peso_lordo ? numberFormat($articolo->peso_lordo, null).' '.tr('kg') : '<span class="text-muted">-</span>').'</td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-cube mr-1"></i> '.tr('Volume').'</td>
                            <td class="text-right font-weight-bold">'.($articolo->volume ? numberFormat($articolo->volume, null).' '.tr('m³') : '<span class="text-muted">-</span>').'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';
