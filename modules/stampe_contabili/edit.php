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

echo '
<div class="row">
    <div class="col-md-12 text-center">
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i> <strong>'.tr('Attenzione', [], ['upper']).':</strong><br> '.tr('le seguenti stampe contabili possono essere utilizzate per fini fiscali previa verifica delle informazioni inserite nel gestionale.<br/> Rimane esclusiva responsabilità dell\'utente controllare la correttezza dei documenti qui prodotti').'.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 text-center">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Registri IVA').'</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary col-md-12" data-card-widget="modal" data-title="'.tr('Stampa registro IVA vendite').'" data-href="'.base_path().'/modules/stampe_contabili/stampe_contabili.php?dir=entrata&nome_stampa=Registro IVA&id_record='.$id_record.'" ><i class="fa fa-print fa-2x"></i><br>'.tr('Registro').'<br>'.tr('IVA vendite').'</button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary col-md-12" data-card-widget="modal" data-title="'.tr('Stampa registro IVA acquisti').'" data-href="'.base_path().'/modules/stampe_contabili/stampe_contabili.php?dir=uscita&nome_stampa=Registro IVA&id_record='.$id_record.'" ><i class="fa fa-print fa-2x"></i><br>'.tr('Registro').'<br>'.tr('IVA acquisti').'</button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary col-md-12" data-card-widget="modal" data-title="'.tr('Stampa liquidazione IVA').'" data-href="'.base_path().'/modules/stampe_contabili/stampe_contabili.php?nome_stampa=Liquidazione IVA&id_record='.$id_record.'" ><i class="fa fa-print fa-2x"></i><br>'.tr('Liquidazione').'<br>'.tr('IVA').'</button>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="col-md-6 text-center">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Contabilità').'</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary col-md-12" data-card-widget="modal" data-title="'.tr('Stampa Bilancio').'" data-href="'.base_path().'/modules/stampe_contabili/stampa_bilancio.php" ><i class="fa fa-print fa-2x"></i> <br>'.tr('Stampa').'<br>'.tr('Bilancio').'<br></button>
                    </div>
                    <div class="col-md-4">
                        '.Prints::getLink('Mastrino', 1, 'btn-primary col-md-12', '<br>'.tr('Situazione').'<br>'.tr('patrimoniale'), '|default| fa-2x', 'lev=1').'
                    </div>
                    <div class="col-md-4">
                        '.Prints::getLink('Mastrino', 2, 'btn-primary col-md-12', '<br>'.tr('Situazione').'<br>'.tr('economica'), '|default| fa-2x', 'lev=1').'
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 text-center">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Dati economici dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
]).'</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        '.Prints::getLink('Fatturato', $id_record, 'btn-primary col-md-12', '<br>'.tr('Stampa').'<br>'.tr('Fatturato'), '|default| fa-2x', 'dir=entrata').'
                    </div>
                    <div class="col-md-6">
                    '.Prints::getLink('Fatturato', $id_record, 'btn-primary col-md-12', '<br>'.tr('Stampa').'<br>'.tr('Acquisti').'<br>', '|default| fa-2x', 'dir=uscita').'
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 text-center">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Libro giornale').'</h3>
            </div>

            <div class="card-body">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary col-md-12" data-card-widget="modal" data-title="'.tr('Libro giornale').'" data-href="'.base_path().'/modules/stampe_contabili/stampe_contabili.php?nome_stampa=Libro giornale&id_record='.$id_record.'"><i class="fa fa-print fa-2x"></i><br>'.tr('Libro').'<br>'.tr('giornale').'</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 text-center">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Scadenzario').'</h3>
            </div>

            <div class="card-body">';

if (empty($dbo->fetchArray('SELECT * FROM co_scadenziario'))) {
    $class = 'muted';
    $disabled = 'disabled';
} else {
    $class = 'primary';
    $disabled = '';
}

echo '     
                <div class="col-md-6">
                    <button type="button" '.$disabled.' class="btn btn-'.$class.' col-md-12" data-card-widget="modal" data-title="'.tr('Stampa scadenzario').'" data-href="'.base_path().'/modules/stampe_contabili/stampa_scadenzario.php" >
                        <i class="fa fa-print fa-2x"></i><br>'.tr('Stampa<br>scadenzario').'
                    </button>
                </div>';

echo '
            </div>
        </div>
    </div>
</div>';
