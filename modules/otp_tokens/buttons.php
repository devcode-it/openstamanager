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

$record = $records[0];

// Verifica se il token è abilitato
$is_enabled = !empty($record['enabled']);

// Verifica se il token è scaduto
$is_not_active = false;
if (!empty($record['valido_dal']) && !empty($record['valido_al'])) {
    $is_not_active = strtotime($record['valido_dal']) > time() || strtotime($record['valido_al']) < time();
} elseif (!empty($record['valido_dal']) && empty($record['valido_al'])) {
    $is_not_active = strtotime($record['valido_dal']) > time();
} elseif (empty($record['valido_dal']) && !empty($record['valido_al'])) {
    $is_not_active = strtotime($record['valido_al']) < time();
}

// Pulsante per abilitare/disabilitare
if ($is_enabled) {
    echo '
    <a class="btn btn-warning ask" data-msg="'.tr('Disabilitare questo token?').'" data-backto="record-edit" data-op="disable" data-button="'.tr('Disabilita').'" data-class="btn btn-lg btn-warning">
        <i class="fa fa-times"></i> '.tr('Disabilita').'
    </a>';
} else {
    echo '
    <a class="btn btn-success ask" data-msg="'.tr('Abilitare questo token?').'" data-backto="record-edit" data-op="enable" data-button="'.tr('Abilita').'" data-class="btn btn-lg btn-success">
        <i class="fa fa-check"></i> '.tr('Abilita').'
    </a>';
}

// Pulsante per copiare URL
$base_url = base_url();
$url = $base_url.'/?token='.$record['token'];

if (!empty($url)) {
    echo '
    <button class="btn btn-primary" onclick="copyTokenUrl(\''.$url.'\')">
        <i class="fa fa-copy"></i> '.tr('Copia URL').'
    </button>';
}

?>

<script>
function copyTokenUrl(url) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('<?php echo tr('URL copiato negli appunti!'); ?>');
        }).catch(function(err) {
            console.error('Errore nella copia: ', err);
            fallbackCopyTextToClipboard(url);
        });
    } else {
        fallbackCopyTextToClipboard(url);
    }
}

function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            toastr.success('<?php echo tr('URL copiato negli appunti!'); ?>');
        } else {
            toastr.error('<?php echo tr('Errore nella copia'); ?>');
        }
    } catch (err) {
        toastr.error('<?php echo tr('Errore nella copia'); ?>');
    }

    document.body.removeChild(textArea);
}

function generateTokenQR(url) {
    window.open('https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(url), '_blank');
}
</script>
