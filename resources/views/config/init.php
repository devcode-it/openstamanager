<?php

if (Update::isUpdateAvailable() || !$dbo->isInstalled()) {
    return;
}

$has_azienda = $dbo->fetchNum("SELECT `an_anagrafiche`.`idanagrafica` FROM `an_anagrafiche`
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`id_tipo_anagrafica`
WHERE `an_tipianagrafiche`.`descrizione` = 'Azienda' AND `an_anagrafiche`.`deleted_at` IS NULL") != 0;
$has_user = $dbo->fetchNum('SELECT `id` FROM `zz_users`') != 0;

$settings = [
    'Regime Fiscale' => true,
    'Tipo Cassa' => true,
    'Conto predefinito fatture di vendita' => true,
    'Conto predefinito fatture di acquisto' => true,
    "Percentuale ritenuta d'acconto" => false,
    "Causale ritenuta d'acconto" => false,
];

if (!empty(setting("Percentuale ritenuta d'acconto"))) {
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

include_once App::filepath('resources\views|custom|\layout', 'header.php');

// Controllo sull'esistenza di nuovi parametri di configurazione
if (post('action') == 'init') {
    // Azienda predefinita
    if (!$has_azienda) {
        Filter::set('post', 'op', 'add');
        $id_module = Modules::get('Anagrafiche')['id'];
        include DOCROOT.'/modules/anagrafiche/actions.php';

        // Logo stampe
        if (!empty($_FILES) && !empty($_FILES['blob']['name'])) {
            $file = Uploads::upload($_FILES['blob'], [
                'name' => 'Logo stampe',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            Settings::setValue('Logo stampe', $file);
        }
    }

    // Utente amministratore
    if (!$has_user) {
        $admin = $dbo->selectOne('zz_groups', ['id'], [
            'nome' => 'Amministratori',
        ]);

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
            $setting = Settings::get($setting);

            $value = post('setting')[$setting['id']];
            if (!empty($value)) {
                Settings::setValue($setting['nome'], $value);
            }
        }
    }

    redirect(ROOTDIR, 'js');
    exit();
}

$img = App::getPaths()['img'];

// Visualizzazione dell'interfaccia di impostazione iniziale, nel caso il file di configurazione sia mancante oppure i paramentri non siano sufficienti
echo '
<div class="box box-center-large box-warning">
    <div class="box-header with-border text-center">
        <img src="'.$img.'/logo.png" alt="'.tr('OSM Logo').'">
        <h3 class="box-title">'.tr('OpenSTAManager').'</h3>
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
                            {[ "type": "text", "label": "'.tr('Username').'", "name": "admin_username", "value": "'.$osm_password.'", "placeholder": "'.tr("Digita l'username dell'amministratore").'", "required": 1 ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "password", "label": "'.tr('Password').'", "id": "password", "name": "admin_password", "value": "'.$osm_password.'", "placeholder": "'.tr("Digita la password dell'amministratore").'", "required": 1, "icon-after": "<i onclick=\"togglePassword(this)\" title=\"'.tr('Visualizza password').'\" class=\"fa fa-eye clickable\" ></i>" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "email", "label": "'.tr('Email').'", "name": "admin_email", "value": "'.$osm_email.'", "placeholder": "'.tr("Digita l'indirizzo email dell'amministratore").'" ]}
                        </div>
                    </div>
                </div>
            </div>';

    echo '
    <script>
    function togglePassword() {
        var button = $(this);
        
        if (button.hasClass("fa-eye")) {
            $("#password").attr("type", "text");
            button.removeClass("fa-eye").addClass("fa-eye-slash");
            button.attr("title", "Nascondi password");
        }
        else {
            $("#password").attr("type", "password");
            button.removeClass("fa-eye-slash").addClass("fa-eye");
            button.attr("title", "Visualizza password");
        }
    }
     
    </script>';
}

if (!$has_azienda) {
    echo '

            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">'.tr('Azienda predefinita').'</h3>
                </div>

                <div class="panel-body">';

    $id_tipo_anagrafica = $dbo->fetchOne("SELECT id FROM an_tipianagrafiche WHERE descrizione='Azienda'")['id'];
    $readonly_tipo = true;

    ob_start();
    include DOCROOT.'/modules/anagrafiche/add.php';
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

    foreach ($settings as $setting => $required) {
        if (empty(setting($setting))) {
            echo '
                    <div class="col-md-6">
                        '.Settings::input($setting, $required).'
                    </div>';
        }
    }

    echo '
                </div>
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
<script src="'.$rootdir.'/lib/functions.js"></script>
<script src="'.$rootdir.'/assets/js/init.js"></script>';

include_once App::filepath('resources\views|custom|\layout', 'footer.php');

exit();
