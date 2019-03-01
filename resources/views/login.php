<?php

$pageTitle = tr('Login');
$body_class = 'hold-transition login-page';

include_once App::filepath('resources\views|custom|\layout', 'header.php');
include_once App::filepath('resources\views|custom|\layout', 'messages.php');

// Controllo se è una beta e in caso mostro un warning
if (Update::isBeta()) {
    echo '
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-warning alert-dismissable col-md-6 col-md-push-3 text-center fade in">
				<i class="fa fa-warning"></i> <b>'.tr('Attenzione!').'</b> '.tr('Stai utilizzando una versione <b>non stabile</b> di OSM.').'

                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
			</div>';
}

// Controllo se è una beta e in caso mostro un warning
if (Auth::isBrute()) {
    echo '
            <div class="box box-danger box-center" id="brute">
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

if (!empty(flash()->getMessage('error'))) {
    echo '
            <script>
			$(document).ready(function(){
                $(".login-box").effect("shake");
            });
            </script>';
}

echo '
			<form action="" method="post" class="login-box box">
				<div class="box-header with-border text-center">
					<img src="'.App::getPaths()['img'].'/logo.png" alt="'.tr('OSM Logo').'">
					<h3 class="box-title">'.tr('OpenSTAManager').'</h3>
				</div>

				<div class="login-box-body box-body">
					<div class="form-group input-group">
						<span class="input-group-addon before"><i class="fa fa-user"></i> </span>
						<input type="text" name="username" autocomplete="off" class="form-control" placeholder="'.tr('Nome utente').'"';
if (isset($username)) {
    echo ' value="'.$username.'"';
}
echo'>
					</div>
					<div class="form-group input-group">
						<span class="input-group-addon before"><i class="fa fa-lock"></i> </span>
						<input type="password" name="password" autocomplete="off" class="form-control" placeholder="'.tr('Password').'">
					</div>
					<div class="form-group">
						<input type="checkbox" name="keep_alive"';
if (filter('keep_alive') != null) {
    echo ' checked';
}
echo '/> '.tr('Mantieni attiva la sessione').'
					</div>
				</div>
				<!-- /.box-body -->
				<div class="box-footer">
					<button type="submit" id="login" class="btn btn-danger btn-block">'.tr('Accedi').'</button>
				</div>
				<!-- box-footer -->
			</form>
			<!-- /.box -->

            <script>
            $(document).ready( function(){
                $("#login").click(function(){
                    $("#login").text("';
if ($dbo->isInstalled() && !Update::isUpdateAvailable() && setting('Backup automatico')) {
    echo tr('Backup automatico in corso');
} else {
    echo tr('Autenticazione');
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

include_once App::filepath('resources\views|custom|\layout', 'footer.php');
