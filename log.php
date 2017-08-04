<?php

include_once __DIR__.'/core.php';

$pageTitle = 'Log';

if (file_exists($docroot.'/include/custom/top.php')) {
    include $docroot.'/include/custom/top.php';
} else {
    include $docroot.'/include/top.php';
}

echo '
    <div class="box">
        <div class="box-header">
            <h3 class="box-title"><i class="fa fa-book"></i> '._('Ultimi 100 accessi').'</h3>
        </div>

        <!-- /.box-header -->
        <div class="box-body table-responsive no-padding">
            <table class="datatables table table-hover">
                <thead>
                    <tr>
                        <th>'._('Username').'</th>
                        <th>'._('Data').'</th>
                        <th>'._('Stato').'</th>
                        <th>'._('Indirizzo IP').'</th>
                    </tr>
                </thead>
                <tbody>';

/*
    LEGGO DALLA TABELLA ZZ_LOG
*/
if (Auth::isAdmin()) {
    $q = 'SELECT * FROM `zz_logs` ORDER BY `created_at` DESC LIMIT 0, 100';
} else {
    $q = 'SELECT * FROM `zz_logs` WHERE `idutente`='.prepare($_SESSION['idutente']).' ORDER BY `created_at` DESC LIMIT 0, 100';
}
$rs = $dbo->fetchArray($q);
$n = sizeof($rs);

for ($i = 0; $i < $n; ++$i) {
    $id = $rs[$i]['id'];
    $idutente = $rs[$i]['idutente'];
    $username = $rs[$i]['username'];
    $ip = $rs[$i]['ip'];

    $timestamp = Translator::timestampToLocale($rs[$i]['created_at']);

    if ($rs[$i]['stato'] == 1) {
        $type = 'success';
        $stato = _('Login riuscito!');
    } elseif ($rs[$i]['stato'] == 2) {
        $type = 'warning';
        $stato = _('Utente non abilitato!');
    } elseif ($rs[$i]['stato'] == 3) {
        $type = 'warning';
        $stato = _("L'utente non ha nessun permesso impostato!");
    } else {
        $type = 'danger';
        $stato = _('Autenticazione fallita!');
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

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include $docroot.'/include/custom/bottom.php';
} else {
    include $docroot.'/include/bottom.php';
}
