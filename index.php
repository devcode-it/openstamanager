<?php

$skip_permissions = true;
include_once __DIR__.'/core.php';

$op = filter('op');

// LOGIN
switch ($op) {
    case 'login':
        $username = filter('username');
        $password = filter('password');
        if ($dbo->isConnected() && $dbo->isInstalled() && Auth::getInstance()->attempt($username, $password)) {
            $_SESSION['keep_alive'] = (filter('keep_alive') != null);

            // Auto backup del database giornaliero
            if (get_var('Backup automatico')) {
                $folders = glob($backup_dir.'*');
                $regexp = '/'.date('Y\-m\-d').'/';

                // Controllo se esiste già un backup zip o folder creato per oggi
                if (!empty($folders)) {
                    $found = false;
                    foreach ($folders as $folder) {
                        if (preg_match($regexp, $folder, $matches)) {
                            $found = true;
                        }
                    }
                }

                if ($found) {
                    $_SESSION['infos'][] = _('Backup saltato perché già esistente!');
                } elseif (do_backup()) {
                    $_SESSION['infos'][] = _('Backup automatico eseguito correttamente!');
                } elseif (empty($backup_dir)) {
                    $_SESSION['errors'][] = _('Non è possibile eseguire i backup poichè la cartella di backup non esiste!!!');
                } elseif (!file_exists($backup_dir)) {
                    if (mkdir($backup_dir)) {
                        $_SESSION['infos'][] = _('La cartella di backup è stata creata correttamente.');
                        if (do_backup()) {
                            $_SESSION['infos'][] = _('Backup automatico eseguito correttamente!');
                        }
                    } else {
                        $_SESSION['errors'][] = _('Non è stato possibile creare la cartella di backup!');
                    }
                }
            }
        }
        break;

    case 'logout':
        Auth::logout();

        redirect(ROOTDIR.'/index.php');

        exit();

        break;
}

if (Auth::check() && isset($dbo) && $dbo->isConnected() && $dbo->isInstalled()) {
    $module = Auth::firstModule();

    if (!empty($module)) {
        redirect(ROOTDIR.'/controller.php?id_module='.$module, 'js');
    } else {
        redirect(ROOTDIR.'/index.php?op=logout');
    }
    exit();
}

include_once $docroot.'/include/configuration.php';

include_once $docroot.'/include/update.php';

$pageTitle = _('Login');

if (file_exists($docroot.'/include/custom/top.php')) {
    include_once $docroot.'/include/custom/top.php';
} else {
    include_once $docroot.'/include/top.php';
}

// Controllo se è una beta e in caso mostro un warning
if (str_contains($version, 'beta')) {
    echo '
			<div class="alert alert-warning alert-dismissable pull-right fade in">
				<i class="fa fa-warning"></i> <b>'._('Attenzione!').'</b> '._('Stai utilizzando una versione <b>non stabile</b> di OSM.').'

                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
			</div>';
}

// Controllo se è una beta e in caso mostro un warning
if (Auth::isBrute()) {
    echo '
            <div class="box box-danger box-center" id="brute">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">'._('Attenzione').'</h3>
                </div>

                <div class="box-body text-center">
                <p>'._('Sono stati effettuati troppi tentativi di accesso consecutivi!').'</p>
                <p>'._('Tempo rimanente (in secondi)').': <span id="brute-timeout">'.(Auth::getBruteTimeout() + 1).'</span></p>
                </div>
            </div>
            <script>
            $(document).ready(function(){
                $(".login-box").fadeOut();
                brute();
            });

            function brute() {
                var value = parseFloat($("#brute-timeout").html()) - 1;
                $("#brute-timeout").html(value);

                if(value > 0){
                    setTimeout("brute()", 1000);
                } else{
                    $("#brute").fadeOut();
                    $(".login-box").fadeIn();
                }
            }
            </script>';
}

if (!empty($_SESSION['errors'])) {
    echo '
            <script>
			$(document).ready(function(){
                $(".login-box").effect("shake");
            });
            </script>';
}

echo '
			<form action="?op=login" method="post" class="login-box box">
				<div class="box-header with-border text-center">
					<img src="'.$img.'/logo.png" alt="'._('OSM Logo').'">
					<h3 class="box-title">'._('OpenSTAManager').'</h3>
				</div>
				<!-- /.box-header -->
				<div class="login-box-body box-body">
					<div class="form-group input-group">
						<span class="input-group-addon"><i class="fa fa-user"></i> </span>
						<input type="text" name="username" autocomplete="off" class="form-control" placeholder="'._('Nome utente').'"';
if (isset($username)) {
    echo ' value="'.$username.'"';
}
echo'>
					</div>
					<div class="form-group input-group">
						<span class="input-group-addon"><i class="fa fa-lock"></i> </span>
						<input type="password" name="password" autocomplete="off" class="form-control" placeholder="'._('Password').'">
					</div>
					<div class="form-group">
						<input type="checkbox" name="keep_alive"';
if (filter('keep_alive') != null) {
    echo ' checked';
}
echo '/> '._('Mantieni attiva la sessione').'
					</div>
				</div>
				<!-- /.box-body -->
				<div class="box-footer">
					<button type="submit" id="login" class="btn btn-danger btn-block">'._('Accedi').'</button>
				</div>
				<!-- box-footer -->
			</form>
			<!-- /.box -->

            <script>
            $(document).ready( function(){
                $("#login").click(function(){
                    $("#login").text("';
    if ($dbo->isInstalled() && get_var('Backup automatico')) {
        echo _('Backup automatico in corso');
    } else {
        echo _('Autenticazione');
    }
    echo '...");
                });

                if( $("input[name=username]").val() == ""){
                    $("input[name=username]").focus();
                }
                else{
                    $("input[name=password]").focus();
                }
            });
            </script>';

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include_once $docroot.'/include/custom/bottom.php';
} else {
    include_once $docroot.'/include/bottom.php';
}
