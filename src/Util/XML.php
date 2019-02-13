<?php

namespace Util;

use Exception;

/**
 * Classe dedicata all'interpretazione dei file XML.
 *
 * @since 2.4.3
 */
class XML
{
    /**
     * Interpreta i contenuti di una stringa in formato XML.
     *
     * @param string $string
     *
     * @return array
     */
    public static function read($string)
    {
        $content = static::stripP7MData($string);

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $message = libxml_get_last_error()->message;

            throw new Exception($message);
        }

        $result = json_decode(json_encode($xml), true);

        return $result;
    }

    /**
     * Interpreta i contenuti di un file XML.
     *
     * @param string $file
     *
     * @return array
     */
    public static function readFile($file)
    {
        return static::read(file_get_contents($file));
    }

    /**
     * Interpreta i contenuti di un file XML.
     *
     * @param string $file
     *
     * @return string|bool
     */
    public static function decodeP7M($file)
    {
        $content = file_get_contents($file);

        $base64 = base64_decode($content, true);
        if ($base64 !== false) {
            $content = $base64;
        }

        file_put_contents($file, self::removeBOM($content));

        $directory = pathinfo($file, PATHINFO_DIRNAME);
        $final_file = $directory.'/'.basename($file, '.p7m');

        exec('openssl smime -verify -noverify -in "'.$file.'" -inform DER -out "'.$final_file.'"', $output, $cmd);
        if (!file_exists($final_file)) {
            self::der2smime($file);

            $result = openssl_pkcs7_verify($file, PKCS7_NOVERIFY, '', [], '', $final_file);

            if ($result == -1 || $result === false) {
                return false;
            }
        }

        return $final_file;
    }

    /**
     * Remove UTF8 BOM.
     *
     * @param $text
     *
     * @return string
     *
     * @source https://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences
     */
    protected static function removeBOM($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }

    /**
     * @param $file
     *
     * @return bool|int
     *
     * @source http://php.net/manual/en/function.openssl-pkcs7-verify.php#123118
     */
    protected static function der2smime($file)
    {
        $to = <<<TXT
MIME-Version: 1.0
Content-Disposition: attachment; filename="smime.p7m"
Content-Type: application/x-pkcs7-mime; smime-type=signed-data; name="smime.p7m"
Content-Transfer-Encoding: base64
\n
TXT;
        $from = file_get_contents($file);
        $to .= chunk_split(base64_encode($from));

        return file_put_contents($file, $to);
    }

    /**
     * Removes the PKCS#7 header and the signature info footer from a digitally-signed .xml.p7m file using CAdES format.
     *
     * TODO: controllare il funzionamento con gli allegati (https://forum.italia.it/t/in-produzione-xml-ricevuto-non-leggibile/5695/2).
     *
     * @param string $string File content
     *
     * @return string An arguably-valid XML string with the .p7m header and footer stripped away.
     *
     * @source https://www.ryadel.com/php-estrarre-contenuto-file-xml-p7m-cades-fattura-elettronica-pa/
     */
    protected static function stripP7MData($string)
    {
        // skip everything before the XML content
        $string = substr($string, strpos($string, '<?xml '));

        // skip everything after the XML content
        preg_match_all('/<\/.+?>/', $string, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = end($matches[0]);

        $result = substr($string, 0, $lastMatch[1] + strlen($lastMatch[0]) + 1);

        return static::sanitizeXML($result);
    }

    /**
     * Removes invalid characters from a UTF-8 XML string.
     *
     * @param string a XML string potentially containing invalid characters
     *
     * @return string
     *
     * @source https://www.ryadel.com/php-eliminare-caratteri-non-validi-file-stringa-xml-utf8-utf-8/
     */
    protected static function sanitizeXML($string)
    {
        if (!empty($string)) {
            $regex = '/(
            [\xC0-\xC1] # Invalid UTF-8 Bytes
            | [\xF5-\xFF] # Invalid UTF-8 Bytes
            | \xE0[\x80-\x9F] # Overlong encoding of prior code point
            | \xF0[\x80-\x8F] # Overlong encoding of prior code point
            | [\xC2-\xDF](?![\x80-\xBF]) # Invalid UTF-8 Sequence Start
            | [\xE0-\xEF](?![\x80-\xBF]{2}) # Invalid UTF-8 Sequence Start
            | [\xF0-\xF4](?![\x80-\xBF]{3}) # Invalid UTF-8 Sequence Start
            | (?<=[\x0-\x7F\xF5-\xFF])[\x80-\xBF] # Invalid UTF-8 Sequence Middle
            | (?<![\xC2-\xDF]|[\xE0-\xEF]|[\xE0-\xEF][\x80-\xBF]|[\xF0-\xF4]|[\xF0-\xF4][\x80-\xBF]|[\xF0-\xF4][\x80-\xBF]{2})[\x80-\xBF] # Overlong Sequence
            | (?<=[\xE0-\xEF])[\x80-\xBF](?![\x80-\xBF]) # Short 3 byte sequence
            | (?<=[\xF0-\xF4])[\x80-\xBF](?![\x80-\xBF]{2}) # Short 4 byte sequence
            | (?<=[\xF0-\xF4][\x80-\xBF])[\x80-\xBF](?![\x80-\xBF]) # Short 4 byte sequence (2)
        )/x';
            $string = preg_replace($regex, '', $string);

            $result = '';
            $length = strlen($string);
            for ($i = 0; $i < $length; ++$i) {
                $current = ord($string[$i]);
                if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))) {
                    $result .= chr($current);
                }
            }
            $string = $result;
        }

        return $string;
    }
}
