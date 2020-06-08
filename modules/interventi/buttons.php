<?php

include_once __DIR__.'/../../core.php';

if (empty($record['firma_file'])) {
    $frase = tr('Anteprima e firma');
    $info_firma = '';
} else {
    $frase = tr('Nuova anteprima e firma');
    $info_firma = '<span class="label label-success"><i class="fa fa-edit"></i> '.tr('Firmato il _DATE_ alle _TIME_ da _PERSON_', [
        '_DATE_' => Translator::dateToLocale($record['firma_data']),
        '_TIME_' => Translator::timeToLocale($record['firma_data']),
        '_PERSON_' => '<b>'.$record['firma_nome'].'</b>',
    ]).'</span>';
}

// Duplica intervento
echo'
<button type="button" class="btn btn-primary " onclick="launch_modal( \''.tr('Duplica attività').'\', globals.rootdir + \'/modules/interventi/copia_attivita.php?id_module='.$id_module.'&id_record='.$id_record.'\');" >
    <i class="fa fa-copy"></i> '.tr('Duplica attività').'...
</button>';

// Disabilito il tasto di firma per gli interventi completati
echo '

<!-- EVENTUALE FIRMA GIA\' EFFETTUATA -->
'.$info_firma.'

<button type="button" class="btn btn-primary " onclick="launch_modal( \''.tr('Anteprima e firma').'\', globals.rootdir + \'/modules/interventi/add_firma.php?id_module='.$id_module.'&id_record='.$id_record.'&anteprima=1\');" '.($record['flag_completato'] ? 'disabled' : '').'>
    <i class="fa fa-desktop"></i> '.$frase.'...
</button>';