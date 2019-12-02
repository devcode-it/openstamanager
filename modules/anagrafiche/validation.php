<?php

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

        $message = $disponibile ? tr('Il codice fiscale non è già inserito in una anagrafica') : tr("Il codice fiscale è già utilizzato in un'altra anagrafica");

        // Validazione del Codice Fiscale, solo per anagrafiche Private e Aziende, ignoro controllo se codice fiscale e settato uguale alla p.iva
        if (empty($anagrafica) || ($anagrafica->tipo != 'Ente pubblico' && $value != $anagrafica->partita_iva)) {
            $check = Validate::isValidTaxCode($value);
            if (empty($check)) {
                $message .= '. '.tr('Attenzione: il codice fiscale _COD_ potrebbe non essere valido', [
                    '_COD_' => $value,
                ]);
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

        $message = $disponibile ? tr('La partita iva non è già inserita in una anagrafica') : tr("La partita iva è già utilizzata in un'altra anagrafica");

        $partita_iva = !empty($anagrafica) && is_numeric($value) ? $anagrafica->nazione->iso2.$value : $value;

        $result = $disponibile;
        $check = Validate::isValidVatNumber($partita_iva);
        if (empty($check['valid-format'])) {
            $result = false;
            $errors[] = tr('La partita iva inserita non possiede un formato valido');
        }

        if (empty($check['valid'])) {
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

        $message = $disponibile ? tr("L'email non è già inserita in una anagrafica") : tr("L'email è già utilizzata in un'altra anagrafica");

        $errors = [];
        $check = Validate::isValidEmail($value);
        if (empty($check['valid-format'])) {
            $result = false;
            $errors[] = tr("L'email inserita non possiede un formato valido");
        }

        if (empty($check['smtp-check'])) {
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
