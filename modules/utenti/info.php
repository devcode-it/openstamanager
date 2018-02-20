<?php

$skip_permissions = true;
include __DIR__.'/../../core.php';

$pageTitle = 'Utente';

if (file_exists($docroot.'/include/custom/top.php')) {
    include $docroot.'/include/custom/top.php';
} else {
    include $docroot.'/include/top.php';
}

$user = Auth::user();
$token = Auth::getInstance()->getToken();

echo '
<div class="box">
    <div class="box-header">
        <h3 class="box-title">'.tr("Informazioni sull'utente").'</h3>
    </div>

    <div class="box-body">';

// Cambio password e nome utente
echo '
        <div>
            <p>'.tr('Utente').':'.$user['username'].'</p>

            <a class="btn btn-info" data-href="'.$rootdir.'/modules/'.Modules::get('Utenti e permessi')['directory'].'/user.php" class="text-warning tip" data-toggle="modal" data-target="#bs-popup" data-title="Cambia password">
                <i class="fa fa-unlock-alt"></i> '.tr('Cambia password').'
            </a>
        </div>';

echo '
        <div>
            <h4>'.tr('Token personale').': '.$token.'</h4>
            <p>'.tr("Puoi utilizzare il token per accedere all'API del gestionale e per visualizzare il calendario su applicazioni esterne").'</p>
        </div>';

$link = BASEURL.'/api/?token='.$token.'&resource=sync';
echo '
        <h3>'.tr('Calendario interventi').'</h3>

        <div>
            <p>'.tr("Per accedere al calendario eventi attraverso l'API, accedi al seguente link").':</p>
            <a href="'.$link.'" target="_blank">'.$link.'</a>
        </div>
        <br>

        <h4>'.tr('Configurazione').'</h4>
        <div>
            <p>'.tr('Per _ANDROID_, scarica _LINK_', [
                '_ANDROID_' => '<b>'.tr('Android').'</b>',
                '_LINK_' => '<a href="https://play.google.com/store/apps/details?id=org.kc.and.ical&hl=it" target="_blank"><b>'.tr('iCalSync2').'</b></a>',
            ]).'.</p>

            <p>'.tr("Per _APPLE_, puoi configurare un nuovo calendario dall'app standard del calendario", [
                '_APPLE_' => '<b>'.tr('Apple').'</b>',
            ]).'.</p>

            <p>'.tr('Per _PC_ e altri client di posta, considerare le relative funzionalità o eventuali plugin delle relative applicazioni', [
                '_PC_' => '<b>'.tr('PC').'</b>',
            ]).'.</p>
        </div>';

    echo '
    </div>
</div>';

if (file_exists($docroot.'/include/custom/bottom.php')) {
    include $docroot.'/include/custom/bottom.php';
} else {
    include $docroot.'/include/bottom.php';
}
