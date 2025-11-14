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
    <div class="col-md-12">
        <div class="alert alert-warning">
            <div class="row">
                <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
                    <i class="fa fa-warning fa-3x"></i>
                </div>
                <div class="col-md-11">
                    <strong class="text-blue">'.tr('Attenzione').':</strong>
                    <hr class="mt-1 mb-1">
                    <p>'.tr('Le seguenti stampe contabili possono essere utilizzate per fini fiscali previa verifica delle informazioni inserite nel gestionale.').'</p>
                    <p>'.tr('Rimane esclusiva responsabilità dell\'utente controllare la correttezza dei documenti qui prodotti.').'</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-blue">
                    <i class="fa fa-file-text-o mr-2"></i>'.tr('Registri IVA').'
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" data-card-widget="modal" data-title="'.tr('Stampa registro IVA vendite').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampe_contabili.php?dir=entrata&nome_stampa=Registro IVA&id_record='.$id_record.'" >
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Registro').'<br>'.tr('IVA vendite').'
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" data-card-widget="modal" data-title="'.tr('Stampa registro IVA acquisti').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampe_contabili.php?dir=uscita&nome_stampa=Registro IVA&id_record='.$id_record.'" >
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Registro').'<br>'.tr('IVA acquisti').'
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" data-card-widget="modal" data-title="'.tr('Stampa liquidazione IVA').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampe_contabili.php?nome_stampa=Liquidazione IVA&id_record='.$id_record.'" >
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Liquidazione').'<br>'.tr('IVA').'
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-blue">
                    <i class="fa fa-balance-scale mr-2"></i>'.tr('Contabilità').'
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" data-card-widget="modal" data-title="'.tr('Stampa Bilancio').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampa_bilancio.php" >
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Stampa').'<br>'.tr('Bilancio').'
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="window.open(\''.base_path_osm().'/pdfgen.php?id_print='.Prints::getPrints()['Mastrino'].'&id_record=1&lev=1\', \'_blank\')">
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Situazione').'<br>'.tr('patrimoniale').'
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="window.open(\''.base_path_osm().'/pdfgen.php?id_print='.Prints::getPrints()['Mastrino'].'&id_record=2&lev=1\', \'_blank\')">
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Situazione').'<br>'.tr('economica').'
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-blue">
                    <i class="fa fa-bar-chart mr-2"></i>'.tr('Dati economici dal _START_ al _END_', [
    '_START_' => Translator::dateToLocale($_SESSION['period_start']),
    '_END_' => Translator::dateToLocale($_SESSION['period_end']),
]).'</h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="window.open(\''.base_path_osm().'/pdfgen.php?id_print='.Prints::getPrints()['Fatturato'].'&id_record='.$id_record.'&dir=entrata\', \'_blank\')">
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Stampa').'<br>'.tr('Fatturato').'
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="window.open(\''.base_path_osm().'/pdfgen.php?id_print='.Prints::getPrints()['Fatturato'].'&id_record='.$id_record.'&dir=uscita\', \'_blank\')">
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Stampa').'<br>'.tr('Acquisti').'
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-blue">
                    <i class="fa fa-book mr-2"></i>'.tr('Libro giornale').'
                </h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 offset-md-2 mb-3">
                        <button type="button" class="btn btn-primary btn-block" data-card-widget="modal" data-title="'.tr('Libro giornale').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampe_contabili.php?nome_stampa=Libro giornale&id_record='.$id_record.'">
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Libro').'<br>'.tr('giornale').'
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title text-blue">
                    <i class="fa fa-calendar-check-o mr-2"></i>'.tr('Scadenzario').'
                </h3>
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
                <div class="row">
                    <div class="col-md-8 offset-md-2 mb-3">
                        <button type="button" '.$disabled.' class="btn btn-'.$class.' btn-block" data-card-widget="modal" data-title="'.tr('Stampa scadenzario').'" data-href="'.base_path_osm().'/modules/stampe_contabili/stampa_scadenzario.php" >
                            <i class="fa fa-print fa-2x mb-2"></i><br>'.tr('Stampa').'<br>'.tr('scadenzario').'
                        </button>
                    </div>
                </div>';

echo '
            </div>
        </div>
    </div>
</div>';
