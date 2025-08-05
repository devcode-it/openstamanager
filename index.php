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
$token = filter('token');

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
    if (Permissions::isTokenAccess()) {
        if (!empty($_SESSION['token_access']['id_module_target']) && !empty($_SESSION['token_access']['id_record_target'])) {
            redirect(base_path().'/shared_editor.php?id_module='.$_SESSION['token_access']['id_module_target'].'&id_record='.$_SESSION['token_access']['id_record_target']);
            exit;
        }
    }

    $module = Auth::firstModule();

    if (!empty($module)) {
        redirect(base_path().'/controller.php?id_module='.$module);
    } else {
        redirect(base_path().'/index.php?op=logout');
    }
    exit;
}

// Gestione accesso tramite token OTP
if (!empty($token) && $dbo->isConnected() && $dbo->isInstalled()) {
    redirect(base_path().'/token_login.php?token='.urlencode($token));
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

$pageTitle = (!$dbo->isInstalled() || !$dbo->isConnected()) ? tr('Installazione') : tr('Login');

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
    <div class="card card-danger shadow-lg col-md-6 offset-md-3 mt-5" id="brute">
        <div class="card-header text-center">
            <h3 class="card-title"><i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione').'</h3>
        </div>
        <div class="card-body text-center">
            <p class="lead">'.tr('Sono stati effettuati troppi tentativi di accesso consecutivi!').'</p>
            <div class="alert alert-warning">
                <p>'.tr('Tempo rimanente').':</p>
                <h3><span id="brute-timeout" class="badge badge-danger">'.(Auth::getBruteTimeout() + 1).'</span> '.tr('secondi').'</h3>
            </div>
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
            $("#brute").fadeOut(500, function() {
                $(".login-box").fadeIn(500);
            });
        }
    }
    </script>';
}
if (!empty(flash()->getMessage('error'))) {
    echo '
            <script>
            $(document).ready(function(){
                // Add shake animation to login box
                $(".login-box").addClass("animated shake");

                // Add error styling to password field
                $(".password-field").addClass("is-invalid");

                // Add error message under password field
                $(".password-field-container").append(\'<div class="invalid-feedback d-block"><i class="fa fa-exclamation-circle mr-1"></i>'.tr('Credenziali non valide. Riprova.').'</div>\');

                // Focus on password field
                $("input[name=password]").focus();

                // Remove error styling when user starts typing in any field
                $("input[name=password], input[name=username]").on("keydown", function() {
                    $(".password-field").removeClass("is-invalid");
                    $(".invalid-feedback").fadeOut(300);
                });
            });
            </script>';
}

if ($dbo->isInstalled() && $dbo->isConnected() && !Update::isUpdateAvailable()) {
    echo '
			<form action="?op=login" method="post" autocomplete="off">
				<div class="login-box card-center-medium">
                    <div class="card card-primary shadow-lg">
                        <div class="card-header text-center bg-light py-4">
                            <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid" style="max-width: 85%;">
                        </div>

                        <div class="card-body pt-4">
                            <p class="login-box-msg text-secondary mb-4"><i class="fa fa-lock mr-2"></i>'.tr('Accedi con le tue credenziali').'</p>
                            <div class="input-group mb-4">
                                <input type="text" name="username" autocomplete="username" class="form-control form-control-lg" placeholder="'.tr('Nome utente').'"';
    if (isset($username)) {
        echo ' value="'.$username.'"';
    }

    echo ' required>
                                <div class="input-group-append">
                                    <div class="input-group-text bg-light">
                                        <i class="fa fa-user text-primary"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4 password-field-container">
                                {[ "type": "password", "name": "password", "autocomplete": "current-password", "placeholder": "'.tr('Password').'", "class": "form-control-lg password-field" ]}
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm" id="login-button">
                                <i class="fa fa-sign-in mr-2"></i>'.tr('Accedi').'
                            </button>

                            <div class="text-center mt-4">
                                <a href="'.base_path().'/reset.php" class="text-secondary">
                                    <i class="fa fa-question-circle mr-1"></i>'.tr('Password dimenticata?').'
                                </a>
                            </div>';
    if ($microsoft) {
        echo '
                        <div class="social-auth-links text-center mt-4 pt-3 border-top">
                            <p class="text-muted">'.tr('- oppure -').'</p>

                            <a href="'.base_path().'/oauth2_login.php?id='.$microsoft['id'].'" class="btn btn-block btn-social btn-primary btn-flat shadow-sm">
                                <i class="fa fa-windows mr-2"></i>'.tr('Accedi con Microsoft').'
                            </a>
                        </div>';
    }
    echo '
                        </div>
                    </div>
                </div>
			</form>
			<!-- /.box -->

            <script>
            $(document).ready(function(){
                // Focus on first empty field
                if($("input[name=username]").val() == ""){
                    $("input[name=username]").focus();
                } else {
                    $("input[name=password]").focus();
                }

                // Add hover effect to login button
                $("#login-button").hover(
                    function() {
                        $(this).removeClass("shadow-sm").addClass("shadow");
                    },
                    function() {
                        $(this).removeClass("shadow").addClass("shadow-sm");
                    }
                );

                // Show loading text on button click
                $("#login-button").click(function(){
                    $(this).html(\'<i class="fa fa-circle-o-notch fa-spin mr-2"></i> '.tr('Autenticazione').'...\');
                });

                // Add subtle animation to input fields on focus
                $("input").focus(function(){
                    $(this).parent().animate({marginLeft: "5px"}, 200).animate({marginLeft: "0px"}, 200);
                });
            });
            </script>';
}

$custom_css = $dbo->isInstalled() ? html_entity_decode(setting('CSS Personalizzato')) : '';
if (!empty($custom_css)) {
    echo '
    <style>'.$custom_css.'</style>';
}

include_once App::filepath('include|custom|', 'bottom.php');
