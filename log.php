<?php

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
                        <th>'.tr('Username').'</th>
                        <th>'.tr('Data').'</th>
                        <th>'.tr('Stato').'</th>
                        <th>'.tr('Indirizzo IP').'</th>
                    </tr>
                </thead>
                <tbody>';

/*
    LEGGO DALLA TABELLA ZZ_LOG
*/
if (Auth::admin()) {
    $q = 'SELECT * FROM `zz_logs` ORDER BY `created_at` DESC LIMIT 0, 100';
} else {
    $q = 'SELECT * FROM `zz_logs` WHERE `id_utente`='.prepare($_SESSION['id_utente']).' ORDER BY `created_at` DESC LIMIT 0, 100';
}
$rs = $dbo->fetchArray($q);
$n = sizeof($rs);

for ($i = 0; $i < $n; ++$i) {
    $id = $rs[$i]['id'];
    $id_utente = $rs[$i]['id_utente'];
    $username = $rs[$i]['username'];
    $ip = $rs[$i]['ip'];

    $timestamp = Translator::timestampToLocale($rs[$i]['created_at']);

    $status = Auth::getStatus();
    if ($rs[$i]['stato'] == $status['success']['code']) {
        $type = 'success';
        $stato = $status['success']['message'];
    } elseif ($rs[$i]['stato'] == $status['disabled']['code']) {
        $type = 'warning';
        $stato = $status['disabled']['message'];
    } elseif ($rs[$i]['stato'] == $status['unauthorized']['code']) {
        $type = 'warning';
        $stato = $status['unauthorized']['message'];
    } else {
        $type = 'danger';
        $stato = $status['failed']['message'];
    }

    echo '
                    <tr class="'.$type.'">
                        <td>'.$username.'</td>
                        <td>'.$timestamp.'</td>
                        <td><span class="label label-'.$type.'">'.$stato.'</span></td>
                        <td>'.$ip.'</td>
                    </tr>';
}

echo '

                </tbody>
            </table>
        </div>
        <!-- /.box-body -->
    </div>
    <!-- /.box -->';

include_once App::filepath('include|custom|', 'bottom.php');
