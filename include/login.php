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

$escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$username = $username ?? '';
$error_message = $error_message ?? null;
$available_login_locales = $available_login_locales ?? [];
$selected_login_locale = $selected_login_locale ?? trans_osm()->getCurrentLocale();
$oauth_providers = $oauth_providers ?? [];

$provider_icon = static function ($provider_name) {
    $provider_name = strtolower((string) $provider_name);

    if (str_contains($provider_name, 'microsoft')) {
        return 'fa-windows';
    }
    if (str_contains($provider_name, 'keycloak')) {
        return 'fa-key';
    }
    if (str_contains($provider_name, 'google')) {
        return 'fa-google';
    }

    return 'fa-sign-in';
};

echo '
<main class="osm-login" aria-labelledby="osm-login-title">
    <section class="osm-login__panel">
        <div class="osm-login__brand">
            <img src="'.$escape($login_logo).'" alt="'.$escape(tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica')).'" class="osm-login__logo">
        </div>';

if (count($available_login_locales) > 1) {
    echo '
        <form method="get" action="" class="osm-login__locale" aria-label="'.$escape(tr('Selezione lingua')).'">
            <label for="login_locale" class="osm-login__locale-label">'.$escape(tr('Lingua')).'</label>
            <select id="login_locale" name="login_locale" class="form-control form-control-sm osm-login__locale-select" onchange="this.form.submit()">';

    foreach ($available_login_locales as $available_login_locale) {
        $locale_code = $available_login_locale['language_code'];
        echo '
                <option value="'.$escape($locale_code).'"'.($locale_code === $selected_login_locale ? ' selected' : '').'>'.$escape($available_login_locale['name']).'</option>';
    }

    echo '
            </select>
            <noscript>
                <button type="submit" class="btn btn-sm btn-outline-secondary">'.$escape(tr('Applica')).'</button>
            </noscript>
        </form>';
}

if (Update::isBeta()) {
    echo '
        <div class="alert alert-warning alert-dismissible fade show osm-login__notice" role="alert">
            <i class="fa fa-exclamation-triangle mr-1" aria-hidden="true"></i>
            <strong>'.$escape(tr('Attenzione!')).'</strong> '.tr('Stai utilizzando una versione <b>non stabile</b> di OSM.').'
            <button aria-label="'.$escape(tr('Chiudi')).'" data-dismiss="alert" class="close" type="button">&times;</button>
        </div>';
}

if (AuthOSM::isBrute()) {
    echo '
        <div class="alert alert-danger osm-login__notice" id="brute" role="alert">
            <strong>'.$escape(tr('Attenzione')).'</strong>
            <span>'.$escape(tr('Sono stati effettuati troppi tentativi di accesso consecutivi!')).'</span>
            <span class="osm-login__brute">
                '.$escape(tr('Tempo rimanente')).':
                <span id="brute-timeout" class="badge badge-danger">'.(AuthOSM::getBruteTimeout() + 1).'</span>
                '.$escape(tr('secondi')).'
            </span>
        </div>';
}

echo '
        <form action="?op=login" method="post" class="osm-login__form login-box">
            <input type="hidden" name="login_locale" value="'.$escape($selected_login_locale).'">
            <h1 id="osm-login-title" class="osm-login__title">'.$escape(tr('Accedi')).'</h1>
            <p class="osm-login__subtitle">'.$escape(tr('Accedi con le tue credenziali')).'</p>';

if (!empty($error_message)) {
    echo '
            <div class="alert alert-danger osm-login__error" role="alert">
                <i class="fa fa-exclamation-circle mr-1" aria-hidden="true"></i>'.$escape($error_message).'
            </div>';
}

echo '
            <div class="form-group osm-login__field">
                <label for="login_username">'.$escape(tr('Nome utente')).'</label>
                <div class="input-group">
                    <input type="text" id="login_username" name="username" autocomplete="username" class="form-control form-control-lg" value="'.$escape($username).'" required autofocus>
                    <div class="input-group-append">
                        <span class="input-group-text" aria-hidden="true"><i class="fa fa-user"></i></span>
                    </div>
                </div>
            </div>

            <div class="form-group osm-login__field">
                <label for="login_password">'.$escape(tr('Password')).'</label>
                <div class="input-group">
                    <input type="password" id="login_password" name="password" autocomplete="current-password" class="form-control form-control-lg password-field'.(!empty($error_message) ? ' is-invalid' : '').'" required>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary osm-login__password-toggle" aria-controls="login_password" aria-label="'.$escape(tr('Visualizza password')).'" title="'.$escape(tr('Visualizza password')).'">
                            <i class="fa fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block osm-login__submit" id="login-button">
                <i class="fa fa-sign-in mr-2" aria-hidden="true"></i>'.$escape(tr('Accedi')).'
            </button>

            <div class="osm-login__links">
                <a href="'.$escape(base_path_osm()).'/reset.php">'.$escape(tr('Password dimenticata?')).'</a>
            </div>';

if (!empty($oauth_providers)) {
    echo '
            <div class="osm-login__oauth" aria-label="'.$escape(tr('Accessi alternativi')).'">
                <div class="osm-login__divider"><span>'.$escape(tr('- oppure -')).'</span></div>';

    foreach ($oauth_providers as $provider) {
        $provider_name = $provider['name'];
        echo '
                <a href="'.$escape(base_path_osm()).'/oauth2_login.php?id='.$escape($provider['id']).'" class="btn btn-outline-primary btn-block osm-login__oauth-button">
                    <i class="fa '.$provider_icon($provider_name).' mr-2" aria-hidden="true"></i>'.$escape(tr('Accedi con')).' '.$escape($provider_name).'
                </a>';
    }

    echo '
            </div>';
}

echo '
        </form>
    </section>
</main>

<script>
$(function() {
    var username = $("#login_username");
    var password = $("#login_password");

    if (username.val() === "") {
        username.trigger("focus");
    } else {
        password.trigger("focus");
    }

    $(".osm-login__password-toggle").on("click", function() {
        var button = $(this);
        var icon = button.find("i");
        var isVisible = password.attr("type") === "text";
        var label = isVisible ? "'.$escape(tr('Visualizza password')).'" : "'.$escape(tr('Nascondi password')).'";

        password.attr("type", isVisible ? "password" : "text");
        icon.toggleClass("fa-eye", isVisible).toggleClass("fa-eye-slash", !isVisible);
        button.attr("aria-label", label).attr("title", label);
    });

    $(".osm-login__form").on("submit", function() {
        $("#login-button").addClass("disabled").html(\'<i class="fa fa-circle-o-notch fa-spin mr-2" aria-hidden="true"></i>'.$escape(tr('Autenticazione')).'...\');
    });
';

if (AuthOSM::isBrute()) {
    echo '
    $(".login-box").hide();

    function brute() {
        var timeout = $("#brute-timeout");
        var value = parseFloat(timeout.text()) - 1;
        timeout.text(value);

        if (value > 0) {
            setTimeout(brute, 1000);
        } else {
            $("#brute").fadeOut(200, function() {
                $(".login-box").fadeIn(200);
            });
        }
    }

    brute();
';
}

echo '
});
</script>';
