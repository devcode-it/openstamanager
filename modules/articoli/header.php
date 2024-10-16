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

use Modules\Articoli\Categoria;

$immagine_articolo = $articolo->immagine ? base_path().'/files/articoli/'.$articolo->immagine : App::getPaths()['img'].'/logo_header.png';

echo '
<hr>
<div class="row">
    <div class="col-md-4">
        <div class="card card-info card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-vcard"></i> '.tr('Articolo').'</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <img src="'.$immagine_articolo.'" " class="img-fluid">
                    </div>
                    
                    <div class="col-md-10">';

// Articolo
echo '
                        <h4><b>'.$articolo->getTranslation('title').'</b></h4>
                        '.($articolo->codice ? '<p><small>Codice:</small> '.$articolo->codice.'</p>' : '').'
                        '.($articolo->barcode ? '<p><i class="fa fa-barcode"></i> '.$articolo->barcode.'</p>' : '').'
                        '.($articolo->id_categoria ? '<p><small>Categoria:</small> '.Categoria::where('id', $articolo->id_categoria)->first()->getTranslation('title') : '').'</p>
                        '.($articolo->id_sottocategoria ? '<p><small>Sottocategoria:</small> '.Categoria::where('id', $articolo->id_sottocategoria)->first()->getTranslation('title') : '').'</p>
                        '.($articolo->id_marchio ? '<p><small>Marchio:</Small> '.$dbo->fetchOne('select name from mg_marchi where id = '.$articolo->id_marchio)['name'] : '').'</p>
                        '.($articolo->note ? '<p><i class="fa fa-pencil-square-o"></i> '.$articolo->note.'</p>' : '').'
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Panoramica
echo '
    <div class="col-md-4">
        <div class="card card-info card-outline shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-info-circle"></i> '.tr('Informazioni').'</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">

                        <p>'.($articolo->um ? '<small>Unità di misura:</Small> '.$articolo->um : '').'</p>
                        <p>'.($articolo->gg_garanzia ? '<small>Garanzia:</Small> '.$articolo->gg_garanzia.' giorni' : '').'</p>
                        <p>'.($articolo->um_secondaria ? '<small>Unità di misura secondaria:</Small> '.$articolo->um_secondaria : '').'</p>
                        <p>'.($articolo->fattore_um_secondaria ? '<small>Fattore:</Small> '.numberFormat($articolo->fattore_um_secondaria, $decimals) : '').'</p>
                        <p>'.($articolo->qta_multipla ? '<small>Quantità multipla:</Small> '.numberFormat($articolo->qta_multipla, $decimals) : '').'</p>
                    </div>

                    <div class="col-md-6">
                        <p><small> '.tr('Serial number').':</small> '.($articolo->abilita_serial ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i> ').'</p>
                        <p><small> '.tr('Attivo').':</small> '.($articolo->attivo ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i> ').'</p>
                        <p>'.($articolo->ubicazione ? '<small>Ubicazione:</Small> '.$articolo->ubicazione : '').'</p>
                        <p>'.($articolo->peso_lordo ? '<small>Peso lordo:</Small> '.numberFormat($articolo->peso_lordo, $decimals).' kg' : '').'</p>
                        <p>'.($articolo->volume ? '<small>Volume:</Small> '.numberFormat($articolo->volume, $decimals).' m3' : '').'</p>
                    </div>
                </div>  
            </div>
        </div>
    </div>';

if ($user->is_admin) {
    $sedi = $dbo->fetchArray('(SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi)');
} else {
    $sedi = $dbo->fetchArray('SELECT nomesede FROM zz_user_sedi INNER JOIN ((SELECT "0" AS id, "Sede legale" AS nomesede) UNION (SELECT id, nomesede FROM an_sedi)) sedi ON zz_user_sedi.idsede=sedi.id WHERE id_user='.prepare($user['id']).' GROUP BY id_user, nomesede');
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
foreach ($sedi as $sede) {
    echo '<p><small>'.$sede['nomesede'].':</small> '.numberFormat($giacenze[$sede['id']][0], 'qta').' '.$articolo->um.'</p>';
}
echo '
            </div>
        </div>
    </div>            
</div>';
