<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Stringy\Stringy as S;

/*
 * Funzioni esterne di utilità per il progetto.
 *
 * @since 2.3
 */

if (!function_exists('array_column')) {
    /**
     * Pluck an array of values from an array.
     *
     * @param  $array - data
     * @param  $key - value you want to pluck from array
     *
     * @since 2.3
     *
     * @return array plucked array only with key data
     */
    function array_column($array, $key)
    {
        return array_map(function ($v) use ($key) {
            return is_object($v) ? $v->$key : $v[$key];
        }, $array);
    }
}

if (!function_exists('array_clean')) {
    /**
     * Pulisce i contenuti vuoti di un array.
     *
     * @param  $array
     *
     * @since 2.3.2
     *
     * @return array
     */
    function array_clean($array)
    {
        return array_unique(array_values(array_filter($array, function ($value) {
            return !empty($value);
        })));
    }
}

if (!function_exists('array_deep_clean')) {
    /**
     * Pulisce i contenuti vuoti di un array.
     *
     * @param  $array
     *
     * @since 2.4.11
     *
     * @return array
     */
    function array_deep_clean($input)
    {
        // If it is an element, then just return it
        if (!is_array($input)) {
            return $input;
        }

        $non_empty_items = [];

        foreach ($input as $key => $value) {
            // Ignore empty cells
            if ($value) {
                $cleaned = array_deep_clean($value);

                // Use recursion to evaluate cells
                if (!empty($cleaned)) {
                    $non_empty_items[$key] = $cleaned;
                }
            }
        }

        // Finally return the array without empty items
        return $non_empty_items;
    }
}

if (!function_exists('starts_with')) {
    /**
     * Check if a string starts with the given string.
     *
     * @param string $string
     * @param string $starts_with
     *
     * @return bool
     */
    function starts_with($string, $starts_with)
    {
        //return strpos($string, $starts_with) === 0;
        return S::create($string)->startsWith($starts_with);
    }
}

if (!function_exists('ends_with')) {
    /**
     * Check if a string ends with the given string.
     *
     * @param string $string
     * @param string $ends_with
     *
     * @return bool
     */
    function ends_with($string, $ends_with)
    {
        //return substr($string, -strlen($ends_with)) === $ends_with;
        return S::create($string)->endsWith($ends_with);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check if a string contains the given string.
     *
     * @param string $string
     * @param string $contains
     *
     * @return bool
     */
    function str_contains($string, $contains)
    {
        //return strpos($string, $contains) !== false;
        return S::create($string)->contains($contains);
    }
}

if (!function_exists('str_to_lower')) {
    /**
     * Converts a string in the lower-case version.
     *
     * @param string $string
     *
     * @return string
     */
    function str_to_lower($string)
    {
        return S::create($string)->toLowerCase()->__toString();
    }
}

if (!function_exists('str_to_upper')) {
    /**
     * Converts a string in the upper-case version.
     *
     * @param string $string
     *
     * @return string
     */
    function str_to_upper($string)
    {
        return S::create($string)->toUpperCase()->__toString();
    }
}

if (!function_exists('replace')) {
    /**
     * Sostituisce gli elementi dell'array all'interno della stringa.
     *
     * @param string $string
     * @param array  $array
     *
     * @return string
     */
    function replace($string, $array)
    {
        return str_replace(array_keys($array), array_values($array), $string);
    }
}

if (!function_exists('random_string')) {
    /**
     * Generates a string of random characters.
     *
     * @param int  $length             The length of the string to
     *                                 generate
     * @param bool $human_friendly     Whether or not to make the
     *                                 string human friendly by
     *                                 removing characters that can be
     *                                 confused with other characters (
     *                                 O and 0, l and 1, etc)
     * @param bool $include_symbols    Whether or not to include
     *                                 symbols in the string. Can not
     *                                 be enabled if $human_friendly is
     *                                 true
     * @param bool $no_duplicate_chars whether or not to only use
     *                                 characters once in the string
     *
     * @throws LengthException If $length is bigger than the available
     *                         character pool and $no_duplicate_chars is
     *                         enabled
     *
     * @return string
     */
    function random_string($length = 16, $human_friendly = true, $include_symbols = false, $no_duplicate_chars = false)
    {
        $nice_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefhjkmnprstuvwxyz23456789';
        $all_an = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $symbols = '!@#$%^&*()~_-=+{}[]|:;<>,.?/"\'\\`';
        $string = '';

        // Determine the pool of available characters based on the given parameters
        if ($human_friendly) {
            $pool = $nice_chars;
        } else {
            $pool = $all_an;

            if ($include_symbols) {
                $pool .= $symbols;
            }
        }

        if (!$no_duplicate_chars) {
            return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
        }

        // Don't allow duplicate letters to be disabled if the length is
        // longer than the available characters
        if ($no_duplicate_chars && strlen($pool) < $length) {
            throw new \LengthException('$length exceeds the size of the pool and $no_duplicate_chars is enabled');
        }

        // Convert the pool of characters into an array of characters and
        // shuffle the array
        $pool = str_split($pool);
        $poolLength = count($pool);
        $rand = mt_rand(0, $poolLength - 1);

        // Generate our string
        for ($i = 0; $i < $length; ++$i) {
            $string .= $pool[$rand];

            // Remove the character from the array to avoid duplicates
            array_splice($pool, $rand, 1);

            // Generate a new number
            if (($poolLength - 2 - $i) > 0) {
                $rand = mt_rand(0, $poolLength - 2 - $i);
            } else {
                $rand = 0;
            }
        }

        return $string;
    }
}

if (!function_exists('secure_random_string')) {
    /**
     * Generate secure random string of given length
     * If 'openssl_random_pseudo_bytes' is not available
     * then generate random string using default function.
     *
     * Part of the Laravel Project <https://github.com/laravel/laravel>
     *
     * @param int $length length of string
     *
     * @return bool
     */
    function secure_random_string($length = 32)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false) {
                throw new \LengthException('$length is not accurate, unable to generate random string');
            }

            return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        }

        return random_string($length);
    }
}

if (!function_exists('download')) {
    /**
     * Transmit headers that force a browser to display the download file
     * dialog. Cross browser compatible. Only fires if headers have not
     * already been sent.
     *
     * @param string $filename The name of the filename to display to
     *                         browsers
     * @param string $content  The content to output for the download.
     *                         If you don't specify this, just the
     *                         headers will be sent
     *
     * @since 2.3
     *
     * @return bool
     */
    function download($file, $filename = null)
    {
        ob_get_clean();
        ob_end_clean();

        if (!headers_sent()) {
            $filename = !empty($filename) ? $filename : basename($file);

            // Required for some browsers
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }

            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

            // Required for certain browsers
            header('Cache-Control: private', false);

            header('Content-Disposition: attachment; filename="'.basename(str_replace('"', '', $filename)).'";');
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');

            header('Content-Length: '.filesize($file));

            $open = fopen($file, 'rb');
            while (!feof($open)) {
                echo fread($open, 1024 * 8);
                ob_flush();
                flush();
            }

            return true;
        }

        return false;
    }
}

if (!function_exists('safe_truncate')) {
    /**
     * Truncate a string to a specified length without cutting a word off.
     *
     * @param string $string The string to truncate
     * @param int    $length The length to truncate the string to
     * @param string $append Text to append to the string IF it gets
     *                       truncated, defaults to '...'
     *
     * @since 2.3
     *
     * @return string
     */
    function safe_truncate($string, $length, $append = '...')
    {
        $ret = substr($string, 0, $length);
        $last_space = strrpos($ret, ' ');

        if ($last_space !== false && $string != $ret) {
            $ret = substr($ret, 0, $last_space);
        }

        if ($ret != $string) {
            $ret .= $append;
        }

        return $ret;
    }
}

if (!function_exists('isHTTPS')) {
    /**
     * Checks to see if the page is being served over SSL or not.
     *
     * @since 2.3
     *
     * @return bool
     */
    function isHTTPS($trust_proxy_headers = false)
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            // Check the standard HTTPS headers
            return true;
        } elseif ($trust_proxy_headers && isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            // Check proxy headers if allowed
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    }
}

if (!function_exists('color_darken')) {
    /**
     * Scurisce un determinato colore.
     *
     * @param unknown $color
     * @param number  $dif
     *
     * @return string
     */
    function color_darken($color, $dif = 20)
    {
        $color = str_replace('#', '', $color);
        if (strlen($color) != 6) {
            return '000000';
        }
        $rgb = '';
        for ($x = 0; $x < 3; ++$x) {
            $c = hexdec(substr($color, (2 * $x), 2)) - $dif;
            $c = ($c < 0) ? 0 : dechex($c);
            $rgb .= (strlen($c) < 2) ? '0'.$c : $c;
        }

        return '#'.$rgb;
    }
}

if (!function_exists('color_inverse')) {
    /**
     * Inverte il colore inserito.
     *
     * @see http://www.splitbrain.org/blog/2008-09/18-calculating_color_contrast_with_php
     *
     * @param string $start_colour
     *
     * @return string
     */
    function color_inverse($start_colour)
    {
        $R1 = hexdec(substr($start_colour, 1, 2));
        $G1 = hexdec(substr($start_colour, 3, 2));
        $B1 = hexdec(substr($start_colour, 5, 2));
        $R2 = 255;
        $G2 = 255;
        $B2 = 255;
        $L1 = 0.2126 * pow($R1 / 255, 2.2) + 0.7152 * pow($G1 / 255, 2.2) + 0.0722 * pow($B1 / 255, 2.2);
        $L2 = 0.2126 * pow($R2 / 255, 2.2) + 0.7152 * pow($G2 / 255, 2.2) + 0.0722 * pow($B2 / 255, 2.2);
        if ($L1 > $L2) {
            $lum = ($L1 + 0.05) / ($L2 + 0.05);
        } else {
            $lum = ($L2 + 0.05) / ($L1 + 0.05);
        }
        if ($lum >= 2.5) {
            return '#fff';
        } else {
            return '#000';
        }
    }
}

if (!function_exists('readSQLFile')) {
    /**
     * Restituisce l'insieme delle query presente nel file specificato.
     *
     * @param string $filename  Percorso per il file
     * @param string $delimiter Delimitatore delle query
     *
     * @since 2.3
     *
     * @return array
     */
    function readSQLFile($filename, $delimiter = ';')
    {
        $inString = false;
        $escChar = false;
        $query = '';
        $stringChar = '';
        $queryLine = [];
        $queryBlock = file_get_contents($filename);
        $sqlRows = explode("\n", $queryBlock);
        $delimiterLen = strlen($delimiter);
        do {
            $sqlRow = current($sqlRows)."\n";
            $sqlRowLen = strlen($sqlRow);
            for ($i = 0; $i < $sqlRowLen; ++$i) {
                if ((substr(ltrim($sqlRow), $i, 2) === '--') && !$inString) {
                    break;
                }
                $znak = substr($sqlRow, $i, 1);
                if ($znak === '\'' || $znak === '"') {
                    if ($inString) {
                        if (!$escChar && $znak === $stringChar) {
                            $inString = false;
                        }
                    } else {
                        $stringChar = $znak;
                        $inString = true;
                    }
                }
                if ($znak === '\\' && substr($sqlRow, $i - 1, 2) !== '\\\\') {
                    $escChar = !$escChar;
                } else {
                    $escChar = false;
                }
                if (substr($sqlRow, $i, $delimiterLen) === $delimiter) {
                    if (!$inString) {
                        $query = trim($query);
                        $delimiterMatch = [];
                        if (preg_match('/^DELIMITER[[:space:]]*([^[:space:]]+)$/i', $query, $delimiterMatch)) {
                            $delimiter = $delimiterMatch[1];
                            $delimiterLen = strlen($delimiter);
                        } else {
                            $queryLine[] = $query;
                        }
                        $query = '';
                        continue;
                    }
                }
                $query .= $znak;
            }
        } while (next($sqlRows) !== false);

        return $queryLine;
    }
}
