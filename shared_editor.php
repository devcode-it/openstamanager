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

include_once __DIR__.'/core.php';

use Permissions;

// Controllo accesso tramite token
if (!Auth::check() && empty($_SESSION['token_user'])) {
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

// Verifica che sia un accesso tramite token
if (!Permissions::isTokenAccess()) {
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

// Se c'è un utente associato, redirect al normale editor.php
if (!empty($_SESSION['id_utente'])) {
    $token_info = $_SESSION['token_access'];
    if (!empty($token_info['id_module_target']) && !empty($token_info['id_record_target'])) {
        redirect_url(base_path().'/editor.php?id_module='.$token_info['id_module_target'].'&id_record='.$token_info['id_record_target']);
    } else {
        redirect_url(base_path().'/index.php?op=logout');
    }
    exit;
}

// Verifica che il token abbia le informazioni necessarie
$token_info = $_SESSION['token_access'];
if (empty($token_info['id_module_target']) || empty($token_info['id_record_target'])) {
    flash()->error(tr('Token non configurato correttamente per l\'accesso diretto'));
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

$id_module = $token_info['id_module_target'];
$id_record = $token_info['id_record_target'];

// Verifica se l'utente sta cercando di accedere a un modulo o record diverso tramite URL
$requested_module = filter('id_module');
$requested_record = filter('id_record');

if (!empty($requested_module) && $requested_module != $id_module) {
    // Redirect al modulo autorizzato
    redirect_url(base_path().'/shared_editor.php?id_module='.$id_module.'&id_record='.$id_record);
    exit;
}

if (!empty($requested_record) && $requested_record != $id_record) {
    // Redirect al record autorizzato
    redirect_url(base_path().'/shared_editor.php?id_module='.$id_module.'&id_record='.$id_record);
    exit;
}

// Verifica che il modulo esista e sia abilitato
$module = Modules::get($id_module);
if (empty($module) || !$module['enabled']) {
    flash()->error(tr('Modulo non disponibile'));
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

// Verifica permessi del token per questo modulo
if (!Permissions::checkTokenPermissions()) {
    flash()->error(tr('Accesso negato'));
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

// Verifica permessi del token per questo record
if (!Permissions::checkTokenRecordAccess($id_record)) {
    flash()->error(tr('Accesso al record negato'));
    redirect_url(base_path().'/index.php?op=logout');
    exit;
}

// Ottieni la struttura del modulo
$structure = Modules::get($id_module);

// Variabili per la compatibilità con i moduli
$id_parent = $id_record;
$id_plugin = null;

// Carica il file init.php del modulo se esiste
$init_file = App::filepath('modules/'.$module['directory'].'|custom|', 'init.php');
if (file_exists($init_file)) {
    include_once $init_file;

    // Registrazione del record per HTMLBuilder (per la sostituzione dei placeholder $nome$)
    HTMLBuilder\HTMLBuilder::setRecord($record);
}

// Determina se caricare edit e actions in base ai permessi
$current_permission = $token_info['permessi'] ?? 'r';
$load_module_content = !in_array($current_permission, ['ra', 'rwa']);

// Imposta la variabile read_only per la compatibilità con i moduli
$read_only = ($current_permission == 'r');

// Altre variabili necessarie per la compatibilità
$rootdir = base_path();
$docroot = base_dir();

if ($load_module_content) {
    // Gestione delle operazioni POST solo per permessi r e rw
    if (!empty(filter('op'))) {
        $actions_file = App::filepath('modules/'.$module['directory'].'|custom|', 'actions.php');
        if (file_exists($actions_file)) {
            include_once $actions_file;

            // Gestione del redirect in base al tipo di salvataggio
            $backto = filter('backto');
            if ($backto == 'shared-editor-close') {
                // Redirect alla pagina di logout per chiudere la sessione token
                redirect_url(base_path().'/index.php?op=logout');
            } else {
                // Redirect normale per evitare re-submit
                redirect_url(base_path().'/shared_editor.php?id_module='.$id_module.'&id_record='.$id_record);
            }
            exit;
        }
    }
}

// Imposta il titolo della pagina in base ai permessi
$permission_titles = [
    'r' => tr('Visualizzazione'),
    'rw' => tr('Modifica'),
    'ra' => tr('Caricamento Allegati'),
    'rwa' => tr('Gestione Allegati'),
];

$permission_info = [
    'r' => tr('Hai accesso in sola lettura: puoi consultare tutte le informazioni e i dettagli presenti, ma non potrai apportare modifiche o salvare cambiamenti'),
    'rw' => tr('Hai accesso completo ai dati: puoi visualizzare tutte le informazioni e modificarle, salvando i cambiamenti quando necessario'),
    'ra' => tr('Hai accesso per caricare gli allegati: puoi caricare, consultare e scaricare tutti i documenti presenti'),
    'rwa' => tr('Hai accesso completo alla gestione documenti: puoi visualizzare, caricare, modificare ed eliminare allegati'),
];

$pageTitle = tr($structure->getTranslation('title')).' - '.$permission_titles[$current_permission];

// Navbar custom prima di top.php
echo '
<!-- Navbar custom per shared_editor -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-3" style="box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1030;">
    <div class="container-fluid">
        <!-- Logo e nome modulo a sinistra -->
        <a href="'.tr('https://www.openstamanager.com').'" class="brand-link" title="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione elettronica").'" target="_blank">
            <img src="'.$rootdir.'/assets/dist/img/logo_completo.png" class="brand-image" alt="'.tr("Il gestionale open source per l'assistenza tecnica e la fatturazione elettronica").'">
            <span class="brand-text font-weight-light">&nbsp;</span>

        </a>

        <!-- Pulsante esci a destra -->
        <ul class="navbar-nav ml-auto">
        <!-- Pulsanti a destra -->
        <ul class="navbar-nav ml-auto">';

// Pulsanti di salvataggio (solo per permessi rw)
if ($current_permission == 'rw') {
    echo '
            <li class="nav-item">
                <div class="btn-group" id="save-buttons" style="margin-right: 10px;">
                    <button type="button" class="btn btn-success" id="save">
                        <i class="fa fa-check"></i> '.tr('Salva').'
                    </button>
                    <button type="button" class="btn btn-success dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="false">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-3" role="menu">
                        <a class="btn dropdown-item" href="#" id="save-close">
                            <i class="fa fa-check-square-o"></i>
                            '.tr('Salva e chiudi').'
                        </a>
                    </div>
                </div>
            </li>';
}
echo '
            <li class="nav-item">
                <a href="'.base_path().'/index.php?op=logout" onclick="sessionStorage.clear()" class="btn btn-danger logout-btn" style="padding: 8px;" title="Esci">
                    <i class="fa fa-power-off nav-icon"></i>
                </a>
            </li>
        </ul>
    </div>
</nav>
';

include_once App::filepath('include|custom|', 'top.php');

// CSS personalizzato per l'interfaccia semplificata
echo '
<style>
/* Nasconde la navbar originale di top.php */
.main-header.navbar {
    display: none !important;
}
.main-sidebar, .control-sidebar, .main-footer {
    display: none !important;
}
.content-wrapper {
    margin-left: 0 !important;
    margin-top: 70px !important; /* Spazio per la navbar fissa */
}
body.sidebar-mini .content-wrapper {
    margin-left: 0 !important;
    margin-top: 70px !important;
}
body {
    padding-top: 0 !important;
}
.token-access-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 0.5rem;
}
.token-access-info {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 0.75rem;
    margin-bottom: 1rem;
}
.token-readonly-buttons .btn.disabled,
.token-readonly-buttons button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.token-readonly-buttons .btn.disabled:hover,
.token-readonly-buttons button.disabled:hover {
    opacity: 0.5;
}
.with-control-sidebar {
    margin-right: 0 !important;
}

</style>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-12">

            <!-- Titolo del modulo -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <h1>
                        <i class="'.$structure['icon'].'"></i> '.$structure->getTranslation('title').'
                    </h1>
                </div>
            </div>

            <!-- Info in base al tipo di operazioni permesse -->
            <div class="row mb-2">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> '.$permission_info[$current_permission].'
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">';
                
//Se abbiamo accesso solo agli allegati mostro un header informativo
if ($current_permission == 'ra' || $current_permission == 'rwa') {
    // Includi l'header personalizzato se il modulo lo supporta e abbiamo i dati necessari
    if(file_exists(App::filepath('modules/'.$module['directory'].'|custom|', 'shared_header.php'))) {
        include_once App::filepath('modules/'.$module['directory'].'|custom|', 'shared_header.php');
    }
}

if ($current_permission == 'rw' || $current_permission == 'r') {
    $path = $structure->getEditFile();
    if (!empty($path)) {
        include $path;
    }
}

// Gestione allegati in base ai permessi
if (in_array($current_permission, ['ra', 'rwa'])) {
    // Per permesso 'ra': solo caricamento
    if ($current_permission == 'ra') {
        // Usa la sintassi standard con upload_only=true
        echo '{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "upload_only": "true", "category": "'.tr('Allegati caricati tramite accesso condiviso').'" )}';
    } elseif ($current_permission == 'rwa') {
        // Per permesso 'rwa': visualizzazione e modifica completa
        echo '{( "name": "filelist_and_upload", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "disable_edit": "true", "category": "'.tr('Allegati caricati tramite accesso condiviso').'" )}';
    }
}

echo '
                </div>
            </div>
        </div>
    </div>
</div>';

// JavaScript per gestire i permessi in base al livello
$current_permission = $token_info['permessi'] ?? 'r';

if ($current_permission == 'r') {
    // Permessi di sola lettura: disabilita tutto
    echo '
    <script>
    $(document).ready(function() {
        // Disabilita tutti i form di modifica per permessi di sola lettura
        $("input, textarea, select").not("[type=hidden]").prop("readonly", true).prop("disabled", true);

        // Disabilita tutti i pulsanti di azione
        $("button[type=submit], .btn-primary, .btn-success, .btn-warning").prop("disabled", true).addClass("disabled");

        // Nasconde i pulsanti di eliminazione e modifica pericolosi
        $(".btn-danger,button[onclick*=\'delete\'], button[onclick*=\'elimina\']").hide();

        //Mostro il pulsante logout
        $(".logout-btn").show();

        // Previene l\'invio di form
        $("form").submit(function(e) {
            e.preventDefault();
            alert("'.tr('Modifica non consentita in modalità sola lettura').'");
            return false;
        });

        // Aggiunge un tooltip ai pulsanti disabilitati
        $(".disabled").attr("title", "'.tr('Azione non disponibile in modalità sola lettura').'").tooltip();
    });
    </script>';
}

// JavaScript per gestire il salvataggio (solo per permessi rw)
if ($current_permission == 'rw') {
    echo '
    <script>
    $(document).ready(function(){
        var form = $("#module-edit").find("form").first();

        // Se non esiste un form con id module-edit, cerca il primo form nella pagina
        if (form.length === 0) {
            form = $("form").first();
        }

        // Aggiunta del submit nascosto se non esiste
        if (form.find("#submit").length === 0) {
            form.prepend(\'<button type="submit" id="submit" class="hide"></button>\');
        }

        $("#save").click(function(){
            $("#submit").trigger("click");
        });

        $("#save-close").on("click", function (){
            // Imposta il redirect dopo il salvataggio
            form.find("[name=backto]").val("shared-editor-close");
            $("#submit").trigger("click");
        });
    });
    </script>';
}

// Disabilito la barra dei plugin
echo '
<script>
$(document).ready(function() {
    $(".main-sidebar").hide();
    $(".control-sidebar-button").hide();
});
</script>';

// Disabilito il pulsante elimina
echo '
<script>
$(document).ready(function() {
    $(".btn.btn-danger.ask").hide();
});
</script>';

// Include il footer
include_once App::filepath('include|custom|', 'bottom.php');
