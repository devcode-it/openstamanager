<?php

$skip_permissions = true;
include __DIR__.'/../../core.php';

$pageTitle = tr('Utente');

if (file_exists($docroot.'/include/custom/top.php')) {
    include $docroot.'/include/custom/top.php';
} else {
    include $docroot.'/include/top.php';
}

$user = Auth::user();
$token = Auth::getInstance()->getToken();

$rs = $dbo->fetchArray('SELECT * FROM an_anagrafiche WHERE idanagrafica = '.prepare($user['idanagrafica']));
$anagrafica = [];
if (!empty($rs)) {
    $anagrafica = $rs[0];
}

$api = BASEURL.'/api/?token='.$token;

echo '
<div class="box">
    <div class="box-header text-center">
        <h3 class="box-title">'.$user['username'].'</h3>
    </div>

    <div class="box-body">';

// Cambio password e nome utente
echo '
        <div>
            <p>'.tr('Gruppo').': '.$user['gruppo'].'</p>';

if (!empty($anagrafica)) {
    echo '
            <p>'.tr('Anagrafica associata').': '.$anagrafica['ragione_sociale'].'</p>';
}

echo '

            <a class="btn btn-info btn-block" data-href="'.$rootdir.'/modules/'.Modules::get('Utenti e permessi')['directory'].'/user.php" class="text-warning tip" data-toggle="modal" data-target="#bs-popup" data-title="Cambia password">
                <i class="fa fa-unlock-alt"></i> '.tr('Cambia password').'
            </a>
        </div>';

    echo '
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
                <p>'.tr("Puoi utilizzare il token per accedere all'API del gestionale e per visualizzare il calendario su applicazioni esterne").'</p>

                <p>'.tr('Token personale').': '.$token.'</p>
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

            <h4>'.tr('Configurazione').'</h4>
            <div>
                <p>'.tr("Per _ANDROID_, scarica un'applicazione dedicata dal _LINK_", [
                    '_ANDROID_' => '<b>'.tr('Android').'</b>',
                    '_LINK_' => '<a href="https://play.google.com/store/search?q=iCalSync&c=apps" target="_blank">'.tr('Play Store').'</a>',
                ]).'.</p>

                <p>'.tr("Per _APPLE_, puoi configurare un nuovo calendario dall'app standard del calendario", [
                    '_APPLE_' => '<b>'.tr('Apple').'</b>',
                ]).'.</p>

                <p>'.tr('Per _PC_ e altri client di posta, considerare le relative funzionalità o eventuali plugin delle relative applicazioni', [
                    '_PC_' => '<b>'.tr('PC').'</b>',
                ]).'.</p>
            </div>
        </div>
    </div>

</div>';

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include $docroot.'/include/custom/bottom.php';
} else {
    include $docroot.'/include/bottom.php';
}
