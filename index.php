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

$skip_permissions = true;
include_once __DIR__.'/core.php';

use Carbon\Carbon;
use Illuminate\Database\QueryException;

$op = filter('op');

$microsoft = null;

if ($dbo->isConnected()) {
    try {
        $microsoft = $dbo->selectOne('zz_oauth2', '*', ['nome' => 'Microsoft', 'enabled' => 1, 'is_login' => 1]);
    } catch (QueryException $e) {
    }
}

// LOGIN
switch ($op) {
    case 'login':
        $username = post('username');
        $password = $_POST['password'];

        if ($dbo->isConnected() && $dbo->isInstalled() && auth()->attempt($username, $password)) {
            $_SESSION['keep_alive'] = true;

            if (intval(setting('Inizio periodo calendario'))) {
                $_SESSION['period_start'] = Carbon::createFromFormat('d/m/Y', setting('Inizio periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_start'] = date('Y').'-01-01';
            }

            if (intval(setting('Fine periodo calendario'))) {
                $_SESSION['period_end'] = Carbon::createFromFormat('d/m/Y', setting('Fine periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_end'] = date('Y').'-12-31';
            }

        // Rimozione log vecchi
        // $dbo->query('DELETE FROM `zz_operations` WHERE DATE_ADD(`created_at`, INTERVAL 30*24*60*60 SECOND) <= NOW()');
        } else {
            $status = auth()->getCurrentStatus();

            flash()->error(Auth::getStatus()[$status]['message']);

            redirect(base_path().'/index.php');
            exit;
        }

        break;

    case 'logout':
        Auth::logout();

        redirect(base_path().'/index.php');
        exit;
}

if (Auth::check() && isset($dbo) && $dbo->isConnected() && $dbo->isInstalled()) {
    $module = Auth::firstModule();

    if (!empty($module)) {
        redirect(base_path().'/controller.php?id_module='.$module);
    } else {
        redirect(base_path().'/index.php?op=logout');
    }
    exit;
}

// Modalità manutenzione
if (!empty($config['maintenance_ip'])) {
    include_once base_dir().'/include/init/maintenance.php';
}

// Procedura di installazione
include_once base_dir().'/include/init/configuration.php';

// Procedura di aggiornamento
include_once base_dir().'/include/init/update.php';

// Procedura di inizializzazione
include_once base_dir().'/include/init/init.php';

$pageTitle = tr('Login');

include_once App::filepath('include|custom|', 'top.php');

// Controllo se è una beta e in caso mostro un warning
if (Update::isBeta()) {
    echo '
			<div class="clearfix"></div>
            <div class="alert alert-warning alert-dismissible col-md-6 offset-md-3 text-center show">
                <i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Attenzione!').'</strong> '.tr('Stai utilizzando una versione <b>non stabile</b> di OSM.').'
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
            </div>';
}

// Controllo se è una beta e in caso mostro un warning
if (Auth::isBrute()) {
    echo '
    <div class="box box-danger" id="brute">
        <div class="box-header with-border text-center">
            <h3 class="box-title">'.tr('Attenzione').'</h3>
        </div>
        <div class="box-body text-center">
            <p>'.tr('Sono stati effettuati troppi tentativi di accesso consecutivi!').'</p>
            <p>'.tr('Tempo rimanente (in secondi)').': <span id="brute-timeout">'.(Auth::getBruteTimeout() + 1).'</span></p>
        </div>
    </div>

    <script>
    $(document).ready(function(){
        $(".login-box").hide();
        brute();
    });

    function brute() {
        var value = parseFloat($("#brute-timeout").text()) - 1;
        $("#brute-timeout").text(value);

        if(value > 0){
            setTimeout(brute, 1000);
        } else {
            $("#brute").fadeOut();
            $(".login-box").fadeIn();
        }
    }
    </script>';
}
if (!empty(flash()->getMessage('error'))) {
    echo '
            <script>
            $(document).ready(function(){
                $(".login-box").addClass("animated shake");
            });
            </script>';
}

echo '
			<form action="?op=login" method="post" autocomplete="off">
				<div class="login-box">
                    <div class="card card-outline card-orange">
                        <div class="card-header text-center">
                            <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid">
                        </div>

                        <div class="card-body">
                            <p class="login-box-msg">'.tr('Accedi con le tue credenziali').'</p>
                            <div class="input-group mb-3">
                                <input type="text" name="username" autocomplete="username" class="form-control" placeholder="'.tr('Nome utente').'"';
if (isset($username)) {
    echo ' value="'.$username.'"';
}

echo ' required>
                                <div class="input-group-append">
                                    <div class="input-group-text">
                                        <i class="fa fa-user"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="input-group mb-3">
                                {[ "type": "password", "name": "password", "autocomplete": "current-password", "placeholder": "'.tr('Password').'" ]}
                            </div>

                            <button type="submit" class="btn btn-danger btn-block btn-flat">'.tr('Accedi').'</button>
                            <br>
                            <p><a href="'.base_path().'/reset.php">'.tr('Password dimenticata?').'</a></p>';
if ($microsoft) {
    echo '
                        <div class="social-auth-links text-center">
                            <p>- oppure -</p>
                        
                            <a href="'.base_path().'/oauth2_login.php?id='.$microsoft['id'].'" class="btn btn-block btn-social btn-primary btn-flat"><i class="fa fa-windows"></i>'.tr('Accedi con Microsoft').'</a>
                        </div>';
}
echo '
                    </div>
				</div>
			</form>
			<!-- /.box -->

            <script>
            $(document).ready( function(){
                $("#login").click(function(){
                    $("#login").text("'.tr('Autenticazione').'...");
                });

                if( $("input[name=username]").val() == ""){
                    $("input[name=username]").focus();
                }
                else{
                    $("input[name=password]").focus();
                }
            });
            </script>';

$custom_css = $dbo->isInstalled() ? html_entity_decode(setting('CSS Personalizzato')) : '';
if (!empty($custom_css)) {
    echo '
    <style>'.$custom_css.'</style>';
}

include_once App::filepath('include|custom|', 'bottom.php');
