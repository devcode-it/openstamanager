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
    <div class="box">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-book"></i> '.tr('Ultimi 100 accessi').'</h3>
        </div>

        <!-- /.box-header -->
        <div class="box-body table-responsive no-padding">
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
                        <td class="user-agent tip" title="'.strip_tags($log['user_agent']).'">'.$log['user_agent'].'</td>
                        <td><span class="label label-'.$type.'">'.$stato.'</span></td>
                    </tr>';
}

echo '

                </tbody>
            </table>
        </div>
        <!-- /.box-body -->
    </div>
    <!-- /.box -->';
?>

<script>
$(document).ready(function() {
    var parser = new UAParser();

    $('tr').each(function(){
        user_agent_cell = $(this).find('.user-agent');
        user_agent = user_agent_cell.text();

        if (user_agent !== '') {
            parser.setUA(user_agent);
            device = parser.getResult();

            user_agent_cell.html('<strong>' + device.browser.name + '</strong> ' + device.browser.version + ' | <strong>' + device.os.name + '</strong> ' + device.os.version);
            
            console.log(device);
        }
    })
})
</script>

<?php
include_once App::filepath('include|custom|', 'bottom.php');
