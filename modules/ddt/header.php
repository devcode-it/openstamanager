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

use Models\Module;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Contratti\Contratto;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\Scadenzario\Scadenza;

// Anagrafica
$anagrafica = $ddt->anagrafica;

// Sede
if ($ddt->idsede_destinazione) {
    $sede = $dbo->selectOne('an_sedi', '*', ['id' => $ddt->idsede_destinazione]);
} else {
    $sede = $anagrafica->toArray();
}

// Referente
$referente = null;
if ($ddt->idreferente) {
    $referente = $dbo->selectOne('an_referenti', '*', ['id' => $ddt->idreferente]);
}

// Contratto
$contratto = null;
$ore_erogate = 0;
$ore_previste = 0;
$perc_ore = 0;
$color = 'danger';
if ($ddt->id_contratto) {
    $contratto = Contratto::find($ddt->id_contratto);
    $ore_erogate = $contratto->interventi->sum('ore_totali');
    $ore_previste = $contratto->getRighe()->where('um', 'ore')->sum('qta');
    $perc_ore = $ore_previste != 0 ? ($ore_erogate * 100) / $ore_previste : 0;
    if ($perc_ore < 75) {
        $color = 'success';
    } elseif ($perc_ore <= 100) {
        $color = 'warning';
    }
}

// Preventivo
$preventivo = null;
if ($ddt->id_preventivo) {
    $preventivo = Preventivo::find($ddt->id_preventivo);
}

// Ordine
$ordine = null;
if ($ddt->id_ordine) {
    $ordine = Ordine::find($ddt->id_ordine);
}

// Insoluti
$insoluti = Scadenza::where('idanagrafica', $ddt->idanagrafica)
    ->whereRaw('co_scadenziario.da_pagare > co_scadenziario.pagato')
    ->whereRaw('co_scadenziario.scadenza < NOW()')
    ->count();

// Logo
$logo = Upload::where('id_module', (new Module())->getByField('title', 'Anagrafiche'))->where('id_record', $ddt->idanagrafica)->where('name', 'Logo azienda')->first()->filename;

$logo = $logo ? base_path().'/files/anagrafiche/'.$logo : App::getPaths()['img'].'/logo_header.png';

echo '
<hr>
<div class="row">
    <div class="col-md-1">
        <img src="'.$logo.'" class="img-fluid">
    </div>';

// Cliente
echo '
    <div class="col-md-3">
        <h4 style="margin:4px 0;"><b>'.$anagrafica->ragione_sociale.'</b></h4>

        <p style="margin:3px 0;">
            '.($sede['nomesede'] ? $sede['nomesede'].'<br>' : '').'
            '.$sede['indirizzo'].'<br>
            '.$sede['cap'].' - '.$sede['citta'].' ('.$sede['provincia'].')
        </p>

        <p style="margin:3px 0;">
            '.($sede['telefono'] ? '<a class="btn btn-default btn-xs" href="tel:'.$sede['telefono'].'" target="_blank"><i class="fa fa-phone text-maroon"></i> '.$sede['telefono'].'</a>' : '').'
            '.($sede['email'] ? '<a class="btn btn-default btn-xs" href="mailto:'.$sede['email'].'"><i class="fa fa-envelope text-maroon"></i> '.$sede['email'].'</a>' : '').'
            '.($referente['nome'] ? '<p></p><i class="fa fa-user-o text-muted"></i> '.$referente['nome'].'<br>' : '').'
            '.($referente['telefono'] ? '<a class="btn btn-default btn-xs" href="tel:'.$referente['telefono'].'" target="_blank"><i class="fa fa-phone text-maroon"></i> '.$referente['telefono'].'</a>' : '').'
            '.($referente['email'] ? '<a class="btn btn-default btn-xs" href="mailto:'.$referente['email'].'"><i class="fa fa-envelope text-maroon"></i> '.$referente['email'].'</a>' : '').'
        </p>
    </div>';

// Panoramica
echo '
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-map"></i> '.tr('Panoramica ddt num. ').$ddt->codice.'</h3>
            </div>
            <div class="card-body">

                <p style="margin:3px 0;"><i class="fa fa-'.($insoluti ? 'warning text-danger' : 'check text-success').'"></i>  
                    '.($insoluti ? tr('Sono presenti insoluti') : tr('Non sono presenti insoluti')).'
                </p>';

// Contratto
if ($contratto) {
    echo '
                <p style="margin:3px 0;"><i class="fa fa-book text-info"></i>
                    '.Modules::link('Contratti', $contratto->id, tr('Contratto num. _NUM_ del _DATA_', ['_NUM_' => $contratto->numero, '_DATA_' => Translator::dateToLocale($contratto->data_bozza)]));
    if ($ore_previste > 0) {
        echo '
                    - '.$ore_erogate.'/'.$ore_previste.' '.tr('ore').'<br>

                    <div class="progress" style="margin:0; height:8px;">
                        <div class="progress-bar progress-bar-'.$color.'" style="width:'.$perc_ore.'%"></div>
                    </div>';
    }
    echo '
                </p>';
}

// Preventivo
if ($preventivo) {
    echo '
                <p style="margin:3px 0;"><i class="fa fa-book text-info"></i>
                '.Modules::link('Preventivi', $preventivo->id, tr('Preventivo num. _NUM_ del _DATA_', ['_NUM_' => $preventivo->numero, '_DATA_' => Translator::dateToLocale($preventivo->data_bozza)])).'
                </p>';
}

// Ordine
if ($ordine) {
    echo '
                <p style="margin:3px 0;"><i class="fa fa-book text-info"></i>
                '.Modules::link('Ordini cliente', $ordine->id, tr('Ordine num. _NUM_ del _DATA_', ['_NUM_' => $ordine->numero, '_DATA_' => Translator::dateToLocale($ordine->data)])).'
                </p>';
}
echo '                
            </div>
        </div>
    </div>
</div>';
