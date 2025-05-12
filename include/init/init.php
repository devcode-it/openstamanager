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

$id_tipo_azienda = Tipo::where('name', 'Azienda')->first()->id;

$has_azienda = $dbo->fetchNum('SELECT `an_anagrafiche`.`idanagrafica` FROM `an_anagrafiche`
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id`=`an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
WHERE `an_tipianagrafiche`.`id` = '.$id_tipo_azienda.' AND `an_anagrafiche`.`deleted_at` IS NULL') != 0;
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
        $id_module = Module::where('name', 'Anagrafiche')->first()->id;
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
            'idanagrafica' => $id_record ?? 0,
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
<div class="card card-center-large shadow-lg" style="max-width: 1200px; margin: 3% auto; border-radius: 8px; border: none; opacity: 0; animation: fadeIn 0.5s forwards;">
    <div class="card-header text-center">
        <img src="'.$img.'/logo_completo.png" style="max-width: 280px;" alt="'.tr('OSM Logo').'">
    </div>

    <div class="card-body" style="padding: 30px;">
        <div class="text-center mb-4">
            <h4 style="color: #3c8dbc; font-weight: 600;">'.tr('Configurazione iniziale del gestionale').'</h4>
            <p class="text-muted">'.tr('Completa i campi seguenti per iniziare a utilizzare OpenSTAManager').'</p>
        </div>

        <form action="" method="post" id="init-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="init">';

if (!$has_user) {
    echo '
            <div class="card card-outline card-info mb-4" style="border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #17a2b8; border-radius: 6px 6px 0 0;">
                    <h3 class="card-title" style="color: #17a2b8; font-weight: 600;"><i class="fa fa-user mr-2"></i>'.tr('Amministrazione').'</h3>
                </div>

                <div class="card-body" style="padding: 20px;">
                    <div class="row">
                        <div class="col-md-4">
                            {[ "type": "text", "label": "'.tr('Username').'", "id": "admin_username", "name": "admin_username", "value": "", "placeholder": "'.tr("Imposta lo username dell'amministratore").'", "required": 1, "extra": "style=\"border-radius: 4px; border-color: #ddd;\"" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "password", "label": "'.tr('Password').'", "id": "password", "name": "admin_password", "value": "", "placeholder": "'.tr("Imposta la password dell'amministratore").'", "required": 1, "strength": "#config", "extra": "style=\"border-radius: 4px; border-color: #ddd;\"" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "text", "label": "'.tr('Email').'", "id": "admin_email", "name": "admin_email", "value": "", "placeholder": "'.tr("Imposta l'indirizzo email dell'amministratore").'", "required": 1, "extra": "style=\"border-radius: 4px; border-color: #ddd;\"" ]}
                        </div>
                    </div>
                </div>
            </div>';
}

if (!$has_azienda) {
    echo '
            <div class="card card-outline card-success mb-4" style="border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #28a745; border-radius: 6px 6px 0 0;">
                    <h3 class="card-title" style="color: #28a745; font-weight: 600;"><i class="fa fa-building mr-2"></i>'.tr('Azienda predefinita').'</h3>
                </div>

                <div class="card-body" id="bs-popup" style="padding: 20px;">';

    $idtipoanagrafica = Tipo::where('name', 'Azienda')->first()->id;
    $readonly_tipo = true;

    ob_start();
    include base_dir().'/modules/anagrafiche/add.php';
    $anagrafica = ob_get_clean();

    echo str_replace('</form>', '', $anagrafica);

    echo '
                    <div class="card card-outline card-secondary collapsed-card" style="border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px; transition: all 0.3s ease;">
                        <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #6c757d; border-radius: 6px 6px 0 0;">
                            <h3 class="card-title" style="color: #6c757d; font-weight: 600;"><i class="fa fa-image mr-2"></i>'.tr('Logo stampe').'</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" style="color: #6c757d;">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body collapse" style="padding: 20px;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: 600; color: #333;">'.tr('File').'</label>
                                        <input type="file" class="form-control" name="blob" accept=".jpg,.jpeg,.png" style="border-radius: 4px; border-color: #ddd; padding: 6px;">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info" style="border-left: 4px solid #17a2b8; background-color: #f8f9fa; margin-top: 15px; border-radius: 4px;">
                                <i class="fa fa-info-circle mr-2"></i> '.tr('Per impostare il logo delle stampe, caricare un file con estensione ".jpg", ".jpeg" o ".png". Risoluzione consigliata 302x111 pixel').'.
                            </div>
                        </div>
                    </div>';

    echo '
                </div>
            </div>';
}

if (!$has_settings) {
    echo '
            <div class="card card-outline card-warning mb-4" style="border-radius: 6px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease;">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 2px solid #ffc107; border-radius: 6px 6px 0 0;">
                    <h3 class="card-title" style="color: #ffc107; font-weight: 600;"><i class="fa fa-cogs mr-2"></i>'.tr('Impostazioni di base').'</h3>
                </div>

                <div class="card-body" style="padding: 20px;">
                    <div class="row">';
    foreach ($settings as $setting => $required) {
        if (empty(setting($setting))) {
            echo '
                        <div class="col-md-4">
                            '.Settings::input($setting, $required).'
                        </div>';
        }
    }

    echo '          </div>
                </div>
            </div>';
}
echo '
            <!-- PULSANTI -->
            <div class="row" style="margin-top: 30px;">
                <div class="col-md-6">
                    <div class="required-fields" style="display: flex; align-items: center; margin-top: 10px;">
                        <span style="color: #dc3545; font-size: 16px; margin-right: 5px;">*</span>
                        <span style="color: #666; font-size: 13px;">'.tr('Campi obbligatori').'</span>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <button type="submit" id="config" class="btn btn-success btn-lg" style="border-radius: 4px; padding: 10px 20px; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <i class="fa fa-cog mr-2"></i> '.tr('Configura').'
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<style>
/* Nascondi la barra superiore e la sidebar */
.main-header, .main-sidebar, .control-sidebar-button, .control-sidebar, .control-sidebar-bg {
    display: none !important;
}

/* Espandi il contenuto a tutta la larghezza */
.content-wrapper {
    margin-left: 0 !important;
    margin-top: 0 !important;
}

/* Rimuovi il padding superiore della sezione content */
.content {
    padding-top: 0 !important;
}

/* Animazioni e stili per le card */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
</style>

<script>
    $(document).ready(function(){
        // Rimuovi i pulsanti di submit non necessari
        $("button[type=submit]").not("#config").remove();

        // Nascondi la barra superiore e la sidebar
        $(".main-header, .main-sidebar, .control-sidebar-button, .control-sidebar, .control-sidebar-bg").hide();

        // Espandi il contenuto a tutta la larghezza
        $(".content-wrapper").css({
            "margin-left": "0",
            "margin-top": "0"
        });

        // Rimuovi il padding superiore della sezione content
        $(".content").css("padding-top", "0");

        // Animazione per le card al caricamento
        $(".card-outline").each(function(index) {
            $(this).css({
                "opacity": "0",
                "transform": "translateY(20px)"
            }).delay(300 + (index * 150)).animate({
                opacity: 1,
                transform: "translateY(0px)"
            }, 500);
        });
    });
</script>

<script>$(document).ready(init)</script>';

include_once App::filepath('include|custom|', 'bottom.php');

exit;
