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
        $content = $string;

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
        $directory = pathinfo($file, PATHINFO_DIRNAME);
        $content = file_get_contents($file);

        $base64 = base64_decode(base64_encode($content), true);
        if ($base64 !== false) {
            $content = $base64;
        }

        file_put_contents($file, self::removeBOM($content));

        $output_file = $directory.'/'.basename($file, '.p7m');

        exec('openssl smime -verify -noverify -in "'.$file.'" -inform DER -out "'.$output_file.'"', $output, $cmd);
        if (!file_exists($output_file)) {
            $signer = $directory.'/signer';

            self::decode($file, $output_file, $signer);

            self::der2smime($file);
            self::decode($file, $output_file, $signer);

            if (!file_exists($output_file)) {
                return false;
            }
        }

        return $output_file;
    }

    /**
     * Decodifica il file utilizzando le funzioni native PHP.
     *
     * @param $file
     * @param $output_file
     * @param $signer
     *
     * @return mixed
     */
    protected static function decode($file, $output_file, $signer)
    {
        openssl_pkcs7_verify($file, PKCS7_NOVERIFY | PKCS7_NOSIGS, $signer);
        $result = openssl_pkcs7_verify($file, PKCS7_NOVERIFY | PKCS7_NOSIGS, $signer, [], $signer, $output_file);

        return $result;
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
}
