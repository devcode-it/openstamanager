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

// Elenco moduli installati
use API\Services;
use Carbon\Carbon;
use Models\Cache;

echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Moduli installati').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th>'.tr('Versione').'</th>
                <th>'.tr('Stato').'</th>
                <th>'.tr('Compatibilità').'</th>
                <th>'.tr('Opzioni').'</th>
            </tr>';

$modules = Modules::getHierarchy();

$osm_version = Update::getVersion();

echo submodules($modules);

echo '
        </table>
    </div>';

if (Services::isEnabled()) {
    // Informazioni su Services
    $servizi = Cache::pool('Informazioni su Services')->content;

    if (!empty($servizi)) {
        // Elaborazione dei servizi in scadenza
        $limite_scadenze = (new Carbon())->addDays(60);
        $servizi_in_scadenza = [];
        foreach ($servizi as $servizio) {
            // Gestione per data di scadenza
            $scadenza = new Carbon($servizio['expiration_at']);
            if (
                (isset($servizio['expiration_at']) && $scadenza->lessThan($limite_scadenze))
            ) {
                $servizi_in_scadenza[] = $servizio['name'].' ('.$scadenza->diffForHumans().')';
            }
            // Gestione per crediti
            elseif (
                (isset($servizio['credits']) && $servizio['credits'] < 100)
            ) {
                $servizi_in_scadenza[] = $servizio['name'].' ('.$servizio['credits'].' crediti)';
            }
        }

        echo '
        <div class="col-md-12 col-lg-6">
            <div class="box box-info">
                <div class="box-header">
                    <h3 class="box-title">
                        '.tr('Informazioni su Services').'
                    </h3>
                </div>
            </div>

            <div class="box-body">';

        if (empty($servizi_in_scadenza)) {
            echo '
                <p>'.tr('Nessun servizio in scadenza').'.</p>';
        } else {
            echo '
                <p>'.tr('I seguenti servizi sono in scadenza:').'</p>
                <ul>';
            foreach ($servizi_in_scadenza as $servizio) {
                echo '
                    <li>'.$servizio.'</li>';
            }
            echo '
                </ul>';
        }

        echo '

                <hr><br>

                <h4>'.tr('Statistiche su Fatture Elettroniche').'</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>'.tr('Anno').'</th>
                            <th>'.tr('Documenti archiviati').' 
                            <span class="tip" title="'.tr('Fatture attive e relative ricevute, fatture passive').'.">
                                <i class="fa fa-question-circle-o"></i>
                            </span>
                            </th>

                            <th>'.tr('Totale spazio occupato').'
                            <span class="tip" title="'.tr('Fatture attive con eventuali allegati e ricevute, fatture passive con eventuali allegati').'.">
                                <i class="fa fa-question-circle-o"></i>
                            </span>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="elenco-fe">
                        <tr class="info">
                            <td>'.tr('Totale').'</td>
                            <td id="fe_numero"></td>
                            <td id="fe_spazio"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        $(document).ready(function (){
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "GET",
                dataType: "JSON",
                data: {
                    id_module: globals.id_module,
                    op: "informazioni-fe",
                },
                success: function (response) {
                    $("#fe_numero").html(response.invoice_number);
                    $("#fe_spazio").html(response.size);

                    if (response.history.length) {
                        for (let i = 0; i < 5; i++) {
                            const data = response.history[i];

                            $("#elenco-fe").append(`<tr>
                                <td>` + data["year"] + `</td>
                                <td>` + data["number"] + `</td>
                                <td>` + data["size"] + `</td>
                            </tr>`);
                        }
                    }
                }
            });
        });
        </script>';
    } else {
        echo '
        <div class="col-md-12 col-lg-6"><div class="alert alert-warning alert-dismissible" role="alert"><button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button><span><i class="fa fa-warning"></i> '.tr('Nessun servizio abilitato o "OSMCloud Services API Token" non valido').'.</span></div></div>';
    }
}

// Widgets
echo '
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Widgets').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th>'.tr('Posizione').'</th>
                <th>'.tr('Stato').'</th>
                <th>'.tr('Posizione').'</th>
            </tr>';

$widgets = $dbo->fetchArray('SELECT zz_widgets.id, zz_widgets.name AS widget_name, zz_modules.name AS module_name, zz_widgets.enabled AS enabled, location, help FROM zz_widgets INNER JOIN zz_modules ON zz_widgets.id_module=zz_modules.id ORDER BY `id_module` ASC, `zz_widgets`.`order` ASC');

$previous = '';

foreach ($widgets as $widget) {
    // Nome modulo come titolo sezione
    if ($widget['module_name'] != $previous) {
        echo '
            <tr>
                <th colspan="4">'.$widget['module_name'].'</th>
            </tr>';
    }

    // STATO
    if ($widget['enabled']) {
        $stato = '<i class="fa fa-cog fa-spin text-success tip" title="'.tr('Abilitato').'. '.tr('Clicca per disabilitarlo').'..."></i>';
        $class = 'success';
    } else {
        $stato = '<i class="fa fa-cog text-warning tip" title="'.tr('Non abilitato').'"></i>';
        $class = 'warning';
    }

    // Possibilità di disabilitare o abilitare i moduli tranne quello degli aggiornamenti
    if ($widget['enabled']) {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Disabilitare questo widget?')."') ){ $.post( '".base_path().'/actions.php?id_module='.$id_module."', { op: 'disable_widget', id: '".$widget['id']."' }, function(response){ location.href='".base_path().'/controller.php?id_module='.$id_module."'; }); }\">".$stato."</a>\n";
    } else {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Abilitare questo widget?')."') ){ $.post( '".base_path().'/actions.php?id_module='.$id_module."', { op: 'enable_widget', id: '".$widget['id']."' }, function(response){ location.href='".base_path().'/controller.php?id_module='.$id_module."'; }); }\"\">".$stato."</a>\n";
    }

    // POSIZIONE
    if ($widget['location'] == 'controller_top') {
        $location = tr('Schermata modulo in alto');
    } elseif ($widget['location'] == 'controller_right') {
        $location = tr('Schermata modulo a destra');
    }

    if ($widget['location'] == 'controller_right') {
        $posizione = "<i class='fa fa-arrow-up text-warning tip' title=\"".tr('Clicca per cambiare la posizione...')."\"></i>&nbsp;<i class='fa fa-arrow-right text-success' ></i>";
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".base_path().'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_top', id: '".$widget['id']."' }, function(response){ location.href='".base_path().'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    } elseif ($widget['location'] == 'controller_top') {
        $posizione = "<i class='fa fa-arrow-up text-success'></i>&nbsp;<i class='fa fa-arrow-right text-warning tip' title=\"".tr('Clicca per cambiare la posizione...').'"></i>';
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".base_path().'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_right', id: '".$widget['id']."' }, function(response){ location.href='".base_path().'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    }

    echo '
            <tr class="'.$class.'">
                <td>'.$widget['widget_name'].((!empty($widget['help'])) ? ' <i class="tip fa fa-question-circle-o" title="'.$widget['help'].'"</i>' : '').'</td>
                <td align="left"><small>'.$location.'</small></td>
                <td align="center">'.$stato.'</td>
                <td align="center">'.$posizione.'</td>
            </tr>';

    $previous = $widget['module_name'];
}

echo '
        </table>
    </div>
</div>';
