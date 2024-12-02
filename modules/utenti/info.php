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

use Models\Module;
use Models\Setting;

$skip_permissions = true;
include_once __DIR__.'/../../core.php';

$pageTitle = tr('Utente');

include_once App::filepath('include|custom|', 'top.php');

if (post('op') == 'self_update') {
    include_once __DIR__.'/actions.php';
}

$user = Auth::user();
$token = auth()->getToken();

$rs = $dbo->fetchArray('SELECT * FROM an_anagrafiche WHERE idanagrafica = '.prepare($user['idanagrafica']));
$anagrafica = [];
if (!empty($rs)) {
    $anagrafica = $rs[0];
}

$api = base_url().'/api/?token='.$token;
$module = Module::where('name', 'Utenti e permessi')->first();

echo '
<div class="card card-widget widget-user">
    <div class="widget-user-header bg-orange">
      <h3 class="widget-user-username">'.$user['username'].'</h3>
      <h5 class="widget-user-desc">'.$user['gruppo'].'</h5>
    </div>

    <div class="widget-user-image">';

$user_photo = $user->photo ?: $rootdir.'/assets/dist/img/user.png';
echo '
        <img src="'.$user_photo.'" class="img-circle" alt="'.$user['username'].'" />
    </div>
    <div class="card-footer">
        <div class="row">
            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <h5 class="description-header">'.tr('Anagrafica associata').'</h5>
                    <span class="description-text">'.(!empty($anagrafica) ? $anagrafica['ragione_sociale'] : tr('Nessuna')).'</span>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-info btn-block tip '.(($module) ? '' : 'disabled').'" data-href="'.(($module) ? ($module->fileurl('self.php').'?id_module='.$module->id) : '#').'&resource=photo" data-widget="modal" data-title="'.tr('Cambia foto utente').'">
                        <i class="fa fa-picture-o"></i> '.tr('Cambia foto utente').'
                    </a>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-warning btn-block tip '.(($module) ? '' : 'disabled').'" data-href="'.(($module) ? $module->fileurl('self.php').'?id_module='.$module->id : '#').'&resource=password" data-widget="modal" data-title="'.tr('Cambia password').'">
                        <i class="fa fa-unlock-alt"></i> '.tr('Cambia password').'
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>';

echo '
<div class="row">
    <div class="col-md-6">

        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">'.tr('API').'</h3>
            </div>

            <div class="card-body">
                <p>'.tr("Puoi utilizzare il token per accedere all'API del gestionale e per visualizzare il calendario su applicazioni esterne").'.</p>

                <p>'.tr('Token personale').': <b>'.$token.'</b></p>
                <p>'.tr("URL dell'API").': <a href="'.$api.'" target="_blank">'.$api.'</a></p>

            </div>
        </div>
    </div>';

$link = $api.'&resource=sync';
echo '

    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">'.tr('Configurazione').'</h3>
            </div>
            <div class="card-body">
                <p>'.tr("Per _ANDROID_, scarica un'applicazione dedicata dal _LINK_", [
    '_ANDROID_' => '<b>'.tr('Android').'</b>',
    '_LINK_' => '<a href="https://play.google.com/store/search?q=iCalSync&c=apps" target="_blank">'.tr('Play Store').'</a>',
]).'.</p>

                <p>'.tr("Per _APPLE_, puoi configurare un nuovo calendario dall'app standard del calendario", [
    '_APPLE_' => '<b>'.tr('Apple').'</b>',
]).'.</p>

                <p>'.tr('Per _PC_ e altri client di posta, considerare le relative funzionalità o eventuali plugin', [
    '_PC_' => '<b>'.tr('PC').'</b>',
]).'.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">'.tr('Impostazioni').'</h3>
            </div>

            <div class="card-body">';
$gruppi = Setting::selectRaw('sezione AS nome, COUNT(id) AS numero')
->where('is_user_setting', 1)
->groupBy(['sezione'])
->orderBy('sezione')
->get();

foreach ($gruppi as $key => $gruppo) {
    echo '
                <!-- Impostazioni della singola sezione -->
                <div class="card card-primary collapsed-card" title="'.$gruppo->nome.'">
                    <div class="card-header clickable" title="'.$gruppo->nome.'" id="impostazioni-'.$key.'">
                        <div class="card-title">'.tr('_SEZIONE_', [
        '_SEZIONE_' => $gruppo->nome,
    ]).'</div>
                        <div class="card-tools pull-right">
                            <div class="badge">'.$gruppo->numero.'</div>
                        </div>
                    </div>
                
                    <div class="card-body row"></div>
                </div>';
}
echo '
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">'.tr('Calendario interventi').'</h3>
            </div>

            <div class="card-body">
                <p>'.tr("Per accedere al calendario eventi attraverso l'API, accedi al seguente link").':</p>
                <a href="'.$link.'" target="_blank">'.$link.'</a>
            </div>
        </div>
    </div>
</div>

<script>
$("[id^=impostazioni]").click(function() {
    caricaSezione(this);
});

function caricaSezione(header) {
    let card = $(header).closest(".card");
    card.toggleClass("collapsed-card");

    // Controllo sul caricamento già effettuato
    let container = card.find(".card-body");
    if (container.html()){
        return ;
    }

    // Caricamento della sezione di impostazioni
    let sezione = card.attr("title");
    localLoading(container, true);
    return $.get("'.$module->fileurl('sezione.php').'?id_module='.$module->id.'&sezione=" + sezione, function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

function salvaImpostazione(id, valore){
    $.ajax({
        url: "'.$module->fileurl('actions.php').'",
        cache: false,
        type: "POST",
        dataType: "JSON",
        data: {
            op: "update_setting",
            id_module: '.$module->id.',
            id: id,
            valore: valore,
        },
        success: function(data) {
            renderMessages();
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio dei dati').'", "error");
        }
    });
}
</script>';

include_once App::filepath('include|custom|', 'bottom.php');
