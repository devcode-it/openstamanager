<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

$api = BASEURL.'/api/?token='.$token;
$module = Modules::get('Utenti e permessi');

echo '
<div class="box box-widget widget-user">
    <div class="widget-user-header bg-'.(($theme != 'default') ? $theme : 'primary').'">
      <h3 class="widget-user-username">'.$user['username'].'</h3>
      <h5 class="widget-user-desc">'.$user['gruppo'].'</h5>
    </div>

    <div class="widget-user-image">';

$user_photo = $user->photo;
if ($user_photo) {
    echo '
        <img src="'.$user_photo.'" class="img-circle" alt="'.$user['username'].'" />';
} else {
    echo '
        <i class="fa fa-user-circle-o fa-4x pull-left" alt="'.tr('OpenSTAManager').'"></i>';
}

echo '
    </div>
    <div class="box-footer">
        <div class="row">
            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <h5 class="description-header">'.tr('Anagrafica associata').'</h5>
                    <span class="description-text">'.(!empty($anagrafica) ? $anagrafica['ragione_sociale'] : tr('Nessuna')).'</span>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-info btn-block tip '.(($module) ? '' : 'disabled').'" data-href="'.(($module) ? ($module->fileurl('self.php').'?id_module='.$module->id) : '#').'&resource=photo" data-toggle="modal" data-title="'.tr('Cambia foto utente').'">
                        <i class="fa fa-picture-o"></i> '.tr('Cambia foto utente').'
                    </a>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-warning btn-block tip '.(($module) ? '' : 'disabled').'" data-href="'.(($module) ? $module->fileurl('self.php').'?id_module='.$module->id : '#').'&resource=password" data-toggle="modal" data-title="'.tr('Cambia password').'">
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

        <div class="box box-success">
            <div class="box-header">
                <h3 class="box-title">'.tr('API').'</h3>
            </div>

            <div class="box-body">
                <p>'.tr("Puoi utilizzare il token per accedere all'API del gestionale e per visualizzare il calendario su applicazioni esterne").'.</p>

                <p>'.tr('Token personale').': <b>'.$token.'</b></p>
                <p>'.tr("URL dell'API").': <a href="'.$api.'" target="_blank">'.$api.'</a></p>

            </div>
        </div>
    </div>';

$link = $api.'&resource=sync';
echo '

    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header">
                <h3 class="box-title">'.tr('Calendario interventi').'</h3>
            </div>

            <div class="box-body">
            <p>'.tr("Per accedere al calendario eventi attraverso l'API, accedi al seguente link").':</p>
            <a href="'.$link.'" target="_blank">'.$link.'</a>
            </div>

            <div class="box-header">
                <h3 class="box-title">'.tr('Configurazione').'</h3>
            </div>
            <div class="box-body">
            <div>
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

</div>';

include_once App::filepath('include|custom|', 'bottom.php');
