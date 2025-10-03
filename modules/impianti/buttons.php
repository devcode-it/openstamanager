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

// Duplica impianto
echo '
<form action="" method="post" id="copia-impianto">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>';

// Importa checklist categoria
echo '
<form action="" method="post" id="check-impianto">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="sync_checklist">
    <input type="hidden" name="id_categoria" value="'.$record['id_categoria'].'">
</form>

<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo impianto?\') ){ $(\'#copia-impianto\').submit(); }"> <i class="fa fa-copy"></i> '.tr('Duplica impianto').'</button>

<button type="button" class="btn btn-primary" onclick="if( confirm(\'Confermando, tutte le checklist della categoria verranno importate in questo impianto. Continuare?\') ){ $(\'#check-impianto\').submit(); }"> <i class="fa fa-refresh"></i> '.tr('Importa checklist categoria').'</button>';

// Verifica se esiste già un token OTP per questo impianto
$existing_token = $dbo->fetchOne('SELECT id FROM zz_otp_tokens WHERE id_record_target = ? AND id_module_target = ?', [$id_record, $id_module]);

if ($existing_token) {
    // Pulsante per visualizzare l'etichetta già creata
    echo '
    <button type="button" class="btn btn-success" onclick="openModal(\''.tr('Visualizza etichetta').'\', \''.$structure->fileurl('modals/configurazione_otp.php').'?id_module='.$id_module.'&id_record='.$id_record.'\')">
        <i class="fa fa-eye"></i> '.tr('Visualizza etichetta').'
    </button>';
} else {
    // Pulsante per configurazione guidata token OTP
    echo '
    <button type="button" class="btn btn-info" onclick="openModal(\''.tr('Genera etichetta').'\', \''.$structure->fileurl('modals/configurazione_otp.php').'?id_module='.$id_module.'&id_record='.$id_record.'\')">
        <i class="fa fa-qrcode"></i> '.tr('Genera etichetta').'
    </button>';
}