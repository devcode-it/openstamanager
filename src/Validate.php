<?php

use Mpociot\VatCalculator\VatCalculator;
use Mpociot\VatCalculator\Exceptions\VATCheckUnavailableException;
use Respect\Validation\Validator as v;

/**
 * Classe per la gestione delle funzioni VALIDATE richiamabili del progetto.
 *
 * @since 2.4
 */
class Validate
{
    public static function vatCheck($partita_iva)
    {
        if ($partita_iva === '') {
            return true;
        }

        if (strlen($partita_iva) != 11 || preg_match("/^[0-9]+\$/D", $partita_iva) != 1) {
            return false;
        }

        $s = 0;
        for ($i = 0; $i <= 9; $i += 2) {
            $s += ord($partita_iva[$i]) - ord('0');
        }

        for ($i = 1; $i <= 9; $i += 2) {
            $c = 2*(ord($partita_iva[$i]) - ord('0'));
            if ($c > 9) {
                $c = $c - 9;
            }
            $s += $c;
        }

        if ((10 - $s%10)%10 != ord($partita_iva[10]) - ord('0')) {
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
        if (empty($vat_number)) {
            return true;
        }

        // Controllo sulla sintassi
        if (!static::vatCheck($vat_number)) {
            return false;
        }

        // Controllo con API europea ufficiale
        if (extension_loaded('soap')) {
            try {
                $validator = new VatCalculator();

                if (!$validator->isValidVATNumber($vat_number)) {
                    return false;
                }
            } catch (VATCheckUnavailableException $e) {
            }
        }

        // Controllo attraverso apilayer
        $access_key = setting('apilayer API key for VAT number');
        if (!empty($access_key)) {
            if (!extension_loaded('curl')) {
                flash()->warning(tr('Estensione cURL non installata'));

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

            return $data->valid;
        }

        return true;
    }

    /**
     * Controlla se l'email inserita è valida.
     *
     * @param string $email
     * @param bool   $smtp
     *
     * @return bool|object
     */
    public static function isValidEmail($email, $smtp = 0)
    {
        if (!v::email()->validate($email)) {
            return false;
        }

        // Controllo attraverso apilayer
        $access_key = setting('apilayer API key for Email');
        if (!empty($access_key)) {
            if (!extension_loaded('curl')) {
                flash()->warning(tr('Estensione cURL non installata'));

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
        }

        return true;
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
