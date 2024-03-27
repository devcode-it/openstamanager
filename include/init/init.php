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

include_once __DIR__.'/../../core.php';

use Models\Group;
use Models\Module;
use Models\Setting;
use Modules\Anagrafiche\Tipo;

if (Update::isUpdateAvailable() || !$dbo->isInstalled()) {
    return;
}

$has_azienda = $dbo->fetchNum('SELECT `an_anagrafiche`.`idanagrafica` FROM `an_anagrafiche`
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id`=`an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).")
WHERE `an_tipianagrafiche_lang`.`name` = 'Azienda' AND `an_anagrafiche`.`deleted_at` IS NULL") != 0;
$has_user = $dbo->fetchNum('SELECT `id` FROM `zz_users`') != 0;

$settings = [
    'Regime Fiscale' => true,
    'Tipo Cassa Previdenziale' => false,
    'Conto predefinito fatture di vendita' => true,
    'Conto predefinito fatture di acquisto' => true,
    "Ritenuta d'acconto predefinita" => false,
    "Causale ritenuta d'acconto" => false,
    'Valuta' => true,
    'Utilizza prezzi di vendita comprensivi di IVA' => false,
    'Soft quota' => false,
];

if (!empty(setting("Ritenuta d'acconto predefinita"))) {
    $settings["Causale ritenuta d'acconto"] = true;
}

$has_settings = true;
foreach ($settings as $setting => $required) {
    if (empty(setting($setting)) && $required) {
        $has_settings = false;
        break;
    }
}

if ($has_azienda && $has_user && $has_settings) {
    return;
}

$pageTitle = tr('Inizializzazione');

include_once App::filepath('include|custom|', 'top.php');

// Controllo sull'esistenza di nuovi parametri di configurazione
if (post('action') == 'init') {
    // Azienda predefinita
    if (!$has_azienda) {
        Filter::set('post', 'op', 'add');
        $id_module = (new Module())->getByField('name', 'Anagrafiche', \Models\Locale::where('predefined', true)->first()->id);
        include base_dir().'/modules/anagrafiche/actions.php';

        // Logo stampe
        if (!empty($_FILES) && !empty($_FILES['blob']['name'])) {
            $upload = Uploads::upload($_FILES['blob'], [
                'name' => 'Logo stampe',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            Settings::setValue('Logo stampe', $upload->filename);
        }
    }

    // Utente amministratore
    if (!$has_user) {
        $admin = Group::where('nome', '=', 'Amministratori')->first();

        // Creazione utente Amministratore
        $dbo->insert('zz_users', [
            'username' => post('admin_username'),
            'password' => Auth::hashPassword(post('admin_password')),
            'email' => post('admin_email'),
            'idgruppo' => $admin['id'],
            'idanagrafica' => isset($id_record) ? $id_record : 0,
            'enabled' => 1,
        ]);

        // Creazione token API per l'amministratore
        $dbo->insert('zz_tokens', [
            'id_utente' => $dbo->lastInsertedID(),
            'token' => secure_random_string(),
        ]);
    }

    if (!$has_settings) {
        foreach ($settings as $setting => $required) {
            $setting = Setting::where('nome', '=', $setting)->first();

            $value = post('setting')[$setting->id];
            if (!empty($value)) {
                Settings::setValue($setting->id, $value);
            }
        }
    }

    redirect(base_path(), 'js');
    exit;
}

$img = App::getPaths()['img'];

// Visualizzazione dell'interfaccia di impostazione iniziale, nel caso il file di configurazione sia mancante oppure i paramentri non siano sufficienti
echo '
<div class="box box-center-large box-warning">
    <div class="box-header with-border text-center">
        <img src="'.$img.'/logo_completo.png" class="logo-image" alt="'.tr('OSM Logo').'">
    </div>

    <div class="box-body">
        <form action="" method="post" id="init-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="init">';

if (!$has_user) {
    echo '

            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Amministrazione').'</h3>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "'.tr('Username').'", "name": "admin_username", "value": "", "placeholder": "'.tr("Imposta l'username dell'amministratore").'", "required": 1 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "password", "label": "'.tr('Password').'", "id": "password", "name": "admin_password", "value": "", "placeholder": "'.tr("Imposta la password dell'amministratore").'", "required": 1, "strength": "#config" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "email", "label": "'.tr('Email').'", "name": "admin_email", "value": "", "placeholder": "'.tr("Imposta l'indirizzo email dell'amministratore").'", "required": 1 ]}
                        </div>
                    </div>
                </div>
            </div>';
}

if (!$has_azienda) {
    echo '

            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Azienda predefinita').'</h3>
                </div>

                <div class="panel-body" id="bs-popup">';

    $idtipoanagrafica = (new Tipo())->getByField('name', 'Azienda', \Models\Locale::where('predefined', true)->first()->id);
    $readonly_tipo = true;

    ob_start();
    include base_dir().'/modules/anagrafiche/add.php';
    $anagrafica = ob_get_clean();

    echo str_replace('</form>', '', $anagrafica);

    echo '
                    <div class="box box-success collapsed-box">
                        <div class="box-header with-border">
                            <h3 class="box-title">'.tr('Logo stampe').'</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="box-body collapse">

                            <div class="col-md-12">
                                {[ "type": "file", "placeholder": "'.tr('File').'", "name": "blob" ]}
                            </div>


							<p>&nbsp;</p><div class="col-md-12 alert alert-info text-center">'.tr('Per impostare il logo delle stampe, caricare un file ".jpg". Risoluzione consigliata 302x111 pixel').'.</div>

                        </div>
                    </div>';

    echo '
                </div>
            </div>';
}

if (!$has_settings) {
    echo '

            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Impostazioni di base').'</h3>
                </div>

                <div class="panel-body">';
    $i = 0;
    foreach ($settings as $setting => $required) {
        if (empty(setting($setting))) {
            if ($i % 2 == 0 or $i == 0) {
                echo '  <div class="row">';
            }

            echo '
                        <div class="col-md-6">
                            '.Settings::input($setting, $required).'
                        </div>';
            ++$i;
            if ($i % 2 == 0 or $i == sizeof($settings)) {
                echo '  </div>';
            }
        }
    }

    echo '      </div>
            </div>';
}

echo '
            <!-- PULSANTI -->
            <div class="row">
                <div class="col-md-4">
                    <span>*<small><small>'.tr('Campi obbligatori').'</small></small></span>
                </div>
                <div class="col-md-4 text-right">
                    <button type="submit" id="config" class="btn btn-success btn-block">
                        <i class="fa fa-cog"></i> '.tr('Configura').'
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>';

echo '
<script>
    $(document).ready(function(){
        $("button[type=submit]").not("#config").remove();
    });
</script>

<script>$(document).ready(init)</script>';

include_once App::filepath('include|custom|', 'bottom.php');

exit;
