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

$immagine_articolo = $articolo->immagine ? base_path().'/files/articoli/'.$articolo->immagine : App::getPaths()['img'].'/logo_header.png';

echo '
<hr>
<div class="row">
    <div class="col-md-6">
        <div class="card card-info card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-vcard"></i> '.tr('Articolo').'</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <img src="'.$immagine_articolo.'" " class="img-fluid">
                    </div>
                    
                    <div class="col-md-9">';
// Articolo
if ($articolo->marchio || $articolo->modello) {
    echo '
                            <p class="float-right"><i class="fa fa-tag"></i>
                                '.($articolo->marchio ? ($articolo->marchio->link ? '<a href="'.$articolo->marchio->link.'" target="_blank" rel="noopener noreferrer"> '.$articolo->marchio->name.'</a>' : $articolo->marchio->name.' ') : '').
        ($articolo->modello ? ' <small><i class="fa fa-chevron-right"></i></small> '.$articolo->modello.' ' : '')
    .'</p>';
}
if ($articolo->id_categoria) {
    echo '
                            <p class="text-muted">'.$articolo->categoria->getTranslation('title').
    ($articolo->sottocategoria ? ' <small><i class="fa fa-chevron-right"></i></small> '.$articolo->sottocategoria->getTranslation('title') : '').
    '</p>';
}
echo '
                        <p><h4><b>'.$articolo->getTranslation('title').'</b> '.($articolo->attivo ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i> ').'</h4></p>
                        <p><b>'.$articolo->codice.'</b> '.($articolo->barcode ? ' - <i class="fa fa-barcode"></i> '.$articolo->barcode.'</p>' : '').'</p>
                        '.($articolo->note ? '<p class="text-danger"><i class="fa fa-pencil-square-o"></i> '.$articolo->note.'</p>' : '').'
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
        <div class="card card-info card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-archive"></i> '.tr('Giacenze').'</h3>
            </div>
            <div class="card-body">';
if ($articolo->servizio) {
    echo '
                <div class="alert alert-info text-center" role="alert">
                    <i class="fa fa-info-circle"></i> '.tr('Questo articolo è un servizio').'.
                </div>';
} else {
    echo '
                <table class="table table-sm">
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

        echo '
                    <tr class="'.($giacenze[$sede['id']][0] < $threshold_sede ? 'text-danger' : '').'">
                        <td>'.$sede['nomesede'].'</td>
                        <td class="text-right">'.numberFormat($giacenze[$sede['id']][0], 'qta').' '.$articolo->um.'</td>
                        '.($articolo->fattore_um_secondaria != 0 ? '<td class="text-right"><i class="fa fa-chevron-right pull-left"></i> '.$giacenze[$sede['id']][0] * $articolo->fattore_um_secondaria.' '.$articolo->um_secondaria.'</td>' : '').'
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
        <div class="card card-info card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-info-circle"></i> '.tr('Informazioni').'</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td>'.tr('Garanzia').'</td>
                            <td class="text-right">'.($articolo->gg_garanzia ? $articolo->gg_garanzia.' giorni' : '').'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Serial number').'</td>
                            <td class="text-right">'.($articolo->abilita_serial ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>').'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Ubicazione').'</td>
                            <td class="text-right">'.($articolo->ubicazione ?: '').'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Peso lordo').'</td>
                            <td class="text-right">'.($articolo->peso_lordo ? numberFormat($articolo->peso_lordo, $decimals).' '.tr('kg') : '').'</td>
                        </tr>
                        <tr>
                            <td>'.tr('Volume').'</td>
                            <td class="text-right">'.($articolo->volume ? numberFormat($articolo->volume, $decimals).' '.tr('m³') : '').'</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>  
</div>';
