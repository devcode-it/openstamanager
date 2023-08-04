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

use API\Services;
use Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException;
use Mpociot\VatCalculator\VatCalculator;
use Respect\Validation\Validator as v;

/**
 * Classe per la gestione delle funzioni VALIDATE richiamabili del progetto.
 *
 * @since 2.4
 */
class Validate
{
    public static function vatCheckIT($partita_iva)
    {
        if ($partita_iva === '') {
            return true;
        }
        if (strlen($partita_iva) == 13) {
            $partita_iva = substr($partita_iva, 2);
        }

        if (strlen($partita_iva) != 11 || preg_match('/^[0-9]+$/D', $partita_iva) != 1) {
            return false;
        }

        $s = 0;
        for ($i = 0; $i <= 9; $i += 2) {
            $s += ord($partita_iva[$i]) - ord('0');
        }

        for ($i = 1; $i <= 9; $i += 2) {
            $c = 2 * (ord($partita_iva[$i]) - ord('0'));
            if ($c > 9) {
                $c = $c - 9;
            }
            $s += $c;
        }

        if ((10 - $s % 10) % 10 != ord($partita_iva[10]) - ord('0')) {
            return false;
        }

        return true;
    }

    /**
     * Controlla se la partita iva inserita è valida.
     *
     * @param string $vat_number
     *
     * @return bool
     */
    public static function isValidVatNumber($vat_number)
    {
        $result['valid-format'] = true;

        if (empty($vat_number)) {
            return $result;
        }

        // Controllo sulla sintassi
        if (string_starts_with($vat_number, 'IT') && !static::vatCheckIT($vat_number)) {
            $result['valid-format'] = false;

            return $result;
        }

        /*
        // Controllo con API europea ufficiale
        if (extension_loaded('soap')) {
            try {
                $validator = new VatCalculator();

                if (!$validator->isValidVATNumber($vat_number)) {
                    return false;
                }
            } catch (VATCheckUnavailableException $e) {
            }
        } */

        // Controllo attraverso apilayer
        $access_key = setting('apilayer API key for VAT number');
        if (!empty($access_key)) {
            if (!extension_loaded('curl')) {
                App::flash()->warning(tr('Estensione cURL non installata'));

                return true;
            }

            $ch = curl_init();

            $qs = '&vat_number='.urlencode(strtoupper($vat_number));

            $url = "http://apilayer.net/api/validate?access_key=$access_key".$qs;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $data = json_decode(curl_exec($ch));
            curl_close($ch);

            /*se la riposta è null imposto la relativa proprietà dell'oggetto a 0*/
            if ($data->valid == null) {
                $data->valid = 0;
            }

            $fields = [];
            // Ragione sociale
            $fields['ragione_sociale'] = $data->company_name;

            // Indirizzo
            $address = $data->company_address;
            $info = explode(PHP_EOL, $address);
            $fields['indirizzo'] = $info[0];

            $info = explode(' ', $info[1]);

            $fields['cap'] = $info[0];
            $fields['provincia'] = end($info);

            $citta = array_slice($info, 1, -1);
            $fields['citta'] = implode(' ', $citta);

            $result['fields'] = $fields;
        } elseif (Services::verificaRisorsaAttiva('Verifica Partita IVA')) {
            $response = Services::request('post', 'check_iva', [
                'partita_iva' => $vat_number,
            ]);
            $data = Services::responseBody($response);

            if (!empty($data['result'])) {
                $result['valid-format'] = $data['result']['format_valid'];
                $result['valid'] = $data['result']['valid'];

                $fields = [];
                // Ragione sociale
                $fields['ragione_sociale'] = $data['result']['company_name'];

                // Indirizzo
                $address = $data['result']['company_address'];
                $info = explode(PHP_EOL, $address);
                $fields['indirizzo'] = $info[0];

                $info = explode(' ', $info[1]);

                $fields['cap'] = $info[0];
                $fields['provincia'] = end($info);

                $citta = array_slice($info, 1, -1);
                $fields['citta'] = implode(' ', $citta);

                $result['fields'] = $fields;
            }
        }

        return $result;
    }

    /**
     * Controlla se l'email inserita è valida.
     *
     * @param string $email
     *
     * @return array
     */
    public static function isValidEmail($email)
    {
        $result = [];
        $result['valid-format'] = true;

        if (!v::email()->validate($email)) {
            $result['valid-format'] = false;

            return $result;
        }

        // Controllo attraverso apilayer
        $access_key = setting('apilayer API key for Email');
        $smtp = 0;
        if (!empty($access_key)) {
            if (!extension_loaded('curl')) {
                App::flash()->warning(tr('Estensione cURL non installata'));

                return true;
            }

            $qs = '&email='.urlencode($email);
            $qs .= "&smtp=$smtp";
            $qs .= '&format=1';
            $qs .= '&resource=check_email';

            $url = "https://services.osmcloud.it/api/?token=$access_key".$qs;

            $curl_options = [
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => 'gzip,deflate',
            ];

            $ch = curl_init();
            curl_setopt_array($ch, $curl_options);
            $output = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($output, false);

            /*se la riposta è null verficando il formato, il record mx o il server smtp imposto la relativa proprietà dell'oggetto a 0*/
            if ($data->format_valid == null) {
                $data->format_valid = 0;
            }

            if ($data->mx_found == null && $smtp) {
                $data->mx_found = 0;
            }

            if ($data->smtp_check == null && $smtp) {
                $data->smtp_check = 0;
            }

            $data->smtp = $smtp;

            $data->json_last_error = json_last_error();
            $data->json_last_error_msg = json_last_error_msg();

            return empty($data->format_valid) ? false : $data;
        } elseif (Services::verificaRisorsaAttiva('Verifica Email')) {
            $response = Services::request('post', 'check_email', [
                'email' => $email,
            ]);
            $data = Services::responseBody($response);

            if (!empty($data['result'])) {
                $result['valid-format'] = $data['result']['format_valid'];
                $result['smtp-check'] = $data['result']['smtp_check'];
            }
        }

        return $result;
    }

    public static function isValidTaxCode($codice_fiscale)
    {
        if (empty($codice_fiscale)) {
            return true;
        }

        $validator = new CodiceFiscale\Validator($codice_fiscale);

        return $validator->isFormallyValid();
    }
}
