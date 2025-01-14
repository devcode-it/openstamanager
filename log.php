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

use Carbon\Carbon;

include_once __DIR__.'/core.php';

$pageTitle = tr('Log');

include_once App::filepath('include|custom|', 'top.php');

echo '
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa fa-book"></i> '.tr('Ultimi 100 accessi').'</h3>
        </div>

        <!-- /.card-header -->
        <div class="card-body table-responsive no-padding">
            <table class="datatables table table-hover">
                <thead>
                    <tr>
                        <th width="200">'.tr('Username').'</th>
                        <th width="150">'.tr('Data').'</th>
                        <th width="100">'.tr('Indirizzo IP').'</th>
                        <th>'.tr('Dispositivo').'</th>
                        <th width="180">'.tr('Stato').'</th>
                    </tr>
                </thead>
                <tbody>';

/*
    LEGGO DALLA TABELLA ZZ_LOG
*/
if (Auth::admin()) {
    $q = 'SELECT * FROM `zz_logs` ORDER BY `created_at` DESC LIMIT 0, 100';
} else {
    $q = 'SELECT * FROM `zz_logs` WHERE `id_utente`='.prepare(Auth::user()['id']).' ORDER BY `created_at` DESC LIMIT 0, 100';
}
$logs = $dbo->fetchArray($q);

foreach ($logs as $log) {
    $timestamp = Translator::timestampToLocale($log['created_at']);

    $status = Auth::getStatus();
    if ($log['stato'] == $status['success']['code']) {
        $type = 'success';
        $stato = $status['success']['message'];
    } elseif ($log['stato'] == $status['disabled']['code']) {
        $type = 'warning';
        $stato = $status['disabled']['message'];
    } elseif ($log['stato'] == $status['unauthorized']['code']) {
        $type = 'warning';
        $stato = $status['unauthorized']['message'];
    } else {
        $type = 'danger';
        $stato = $status['failed']['message'];
    }

    $created_at = new Carbon($log['created_at']);

    echo '
                    <tr class="'.$type.'">
                        <td>'.$log['username'].'</td>
                        <td class="tip" title="'.$created_at->format('d/m/Y H:i:s').'">'.$created_at->diffForHumans().'</td>
                        <td>'.$log['ip'].'</td>
                        <td class="user-agent tip" title="'.strip_tags($log['user_agent'] ?: '').'">'.$log['user_agent'].'</td>
                        <td><span class="badge badge-'.$type.'">'.$stato.'</span></td>
                    </tr>';
}

echo '

                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->';
?>

<script>
$(document).ready(function() {
    var parser = new UAParser();
    var icons_path = globals.rootdir + '/assets/dist/img/icons';

    $('tr').each(function(){
        user_agent_cell = $(this).find('.user-agent');
        user_agent = user_agent_cell.text();

        if (user_agent !== '') {
            parser.setUA(user_agent);
            device = parser.getResult();

            var device_info = [];

            // Browser
            if (device.browser.name) {
                device_info['browser'] = {};
                device_info['browser']['text'] = '<strong>' + device.browser.name + '</strong> ' + (device.browser.version || '');
                device_info['browser']['icon'] = icons_path + '/browser/' + device.browser.name.toLowerCase().replace(' ', '-');
            }

            // OS
            if (device.os.name) {
                device_info['os'] = {};
                device_info['os']['text'] = '<strong>' + device.os.name + '</strong> ' + (device.os.version || '');
                device_info['os']['icon'] = icons_path + '/os/' + device.os.name.toLowerCase();
            }

            // Device
            if (device.device.name) {
                device_info['device'] = {};
                device_info['device']['text'] = '<strong>' + device.device.vendor + '</strong> ' + (device.device.model || '');
                device_info['device']['icon'] = icons_path + '/device/' + device.device.name.toLowerCase();
            }

            // Preparazione user-agent riscritto
            if (device_info.browser || device_info.os) {
                user_agent_cell.html('');
            }

            // Sostituzione user-agent con formato più amichevole
            for (var key in device_info) {
                var icon = device_info[key]['icon'];
                var text = device_info[key]['text'];

                if (icon) {
                    var img = new Image();
                    img.src = icon + '.svg';

                    const imgElement = document.createElement('img');
                    imgElement.src = img.src;
                    imgElement.width = 14;
                    imgElement.height = 14;
                    user_agent_cell.append(imgElement).append(' ');
                }

                user_agent_cell.append(text + ' | ');
            }
        }
    })
})
</script>

<?php
include_once App::filepath('include|custom|', 'bottom.php');
