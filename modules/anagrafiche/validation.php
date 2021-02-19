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
use Modules\Anagrafiche\Anagrafica;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'codice':
        $disponibile = Anagrafica::where([
            ['codice', $value],
            ['idanagrafica', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Il codice è disponbile') : tr("Il codice è già utilizzato in un'altra anagrafica");

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;

    case 'codice_fiscale':
        $disponibile = Anagrafica::where([
            ['codice_fiscale', $value],
            ['codice_fiscale', '<>', ''],
            ['idanagrafica', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Questo codice fiscale non è ancora stato utilizzato') : tr("Il codice fiscale è già utilizzato in un'altra anagrafica");

        // Validazione del Codice Fiscale, solo per anagrafiche Private e Aziende, ignoro controllo se codice fiscale e settato uguale alla p.iva
        if (empty($anagrafica) || ($anagrafica->tipo != 'Ente pubblico' && $value != $anagrafica->partita_iva)) {
            $check = Validate::isValidTaxCode($value);
            if (empty($check)) {
                $message .= '. '.tr('Attenzione: il codice fiscale _COD_ potrebbe non essere valido', [
                    '_COD_' => $value,
                ]);
                $disponibile = false;
            }
        }

        if ($value == $anagrafica->partita_iva) {
            $partita_iva = !empty($anagrafica) && is_numeric($value) ? $anagrafica->nazione->iso2.$value : $value;
            $result = $disponibile;
            $check = Validate::isValidVatNumber($partita_iva);
            if (empty($check['valid-format'])) {
                $disponibile = false;
                $errors[] = tr('La partita iva inserita non possiede un formato valido');
            }

            if (isset($check['valid']) && empty($check['valid'])) {
                $disponibile = false;
                $errors[] = tr("Impossibile verificare l'origine della partita iva");
            }
        }

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;

    case 'partita_iva':
        $disponibile = Anagrafica::where([
            ['piva', $value],
            ['piva', '<>', ''],
            ['idanagrafica', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Questa partita iva non è ancora stata utilizzata') : tr("La partita iva è già utilizzata in un'altra anagrafica");

        $partita_iva = !empty($anagrafica) && is_numeric($value) ? $anagrafica->nazione->iso2.$value : $value;

        $result = $disponibile;
        $check = Validate::isValidVatNumber($partita_iva);
        if (empty($check['valid-format'])) {
            $result = false;
            $errors[] = tr('La partita iva inserita non possiede un formato valido');
        }

        if (isset($check['valid']) && empty($check['valid'])) {
            $result = false;
            $errors[] = tr("Impossibile verificare l'origine della partita iva");
        }

        $message .= '. ';
        if (!empty($errors)) {
            $message .= tr('Attenzione').':<ul>';
            foreach ($errors as $error) {
                $message .= '<li>'.$error.'</li>';
            }
            $message .= '</ul>';
        }

        $response = [
            'result' => $result,
            'message' => $message,
            'fields' => $check['fields'],
        ];

        break;

    case 'email':
        $disponibile = Anagrafica::where([
            ['email', $value],
            ['email', '<>', ''],
            ['idanagrafica', '<>', $id_record],
        ])->count() == 0;
        $result = $disponibile;

        $message = $disponibile ? tr('Questa email non è ancora stata utilizzata') : tr("L'email è già utilizzata in un'altra anagrafica");

        $errors = [];
        $check = Validate::isValidEmail($value);
        if (empty($check['valid-format'])) {
            $result = false;
            $errors[] = tr("L'email inserita non possiede un formato valido");
        }

        if (isset($check['smtp-check']) && empty($check['smtp-check'])) {
            $result = false;
            $errors[] = tr("Impossibile verificare l'origine dell'email");
        }

        $message .= '. ';
        if (!empty($errors)) {
            $message .= tr('Attenzione').':<ul>';
            foreach ($errors as $error) {
                $message .= '<li>'.$error.'</li>';
            }
            $message .= '</ul>';
        }

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;
}
