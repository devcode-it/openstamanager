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

switch (post('op')) {
    case 'update':
        $id_utente = post('id_utente') ?: null;
        $descrizione = post('descrizione');
        $tipo_accesso = post('tipo_accesso');
        $valido_dal = post('valido_dal') ?: null;
        $valido_al = post('valido_al') ?: null;
        $id_module_target = post('id_module_target') ?: null;
        $id_record_target = post('id_record_target') ?: null;
        $permessi = post('permessi') ?: null;
        $email = post('email');

        // Validazione email per token OTP
        if ($tipo_accesso == 'otp' && empty($email)) {
            flash()->error(tr('L\'email è obbligatoria per i token con OTP'));
            break;
        }

        if ($tipo_accesso == 'otp' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash()->error(tr('Inserire un indirizzo email valido'));
            break;
        }

        // Aggiornamento record
        Models\OtpToken::find($id_record)->update([
            'id_utente' => $id_utente,
            'descrizione' => $descrizione,
            'tipo_accesso' => $tipo_accesso,
            'valido_dal' => $valido_dal,
            'valido_al' => $valido_al,
            'id_module_target' => $id_module_target,
            'id_record_target' => $id_record_target,
            'permessi' => $permessi,
            'email' => $email,
        ]);

        flash()->info(tr('Token aggiornato correttamente'));
        break;

    case 'add':
        $descrizione = post('descrizione');
        // Generazione token sicuro
        $token = secure_random_string(32);

        // Inserimento nuovo record
        $otp = Models\OtpToken::create([
            'token' => $token,
            'descrizione' => $descrizione,
            'enabled' => 0,
        ]);
        $id_record = $otp->id;

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $descrizione]);
        }

        flash()->info(tr('Token creato correttamente'));
        break;

    case 'delete':
        Models\OtpToken::find($id_record)->delete();
        flash()->info(tr('Token eliminato!'));
        break;

    case 'enable':
        Models\OtpToken::find($id_record)->update(['enabled' => 1]);

        flash()->info(tr('Token abilitato!'));
        break;

    case 'disable':
        Models\OtpToken::find($id_record)->update(['enabled' => 0]);

        flash()->info(tr('Token disabilitato!'));
        break;
}
