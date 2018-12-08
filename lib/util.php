<?php

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
     * @return plucked array only with key data
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
        return array_values(array_filter($array, function ($value) {
            return !empty($value);
        }));
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

if (!function_exists('str_replace_once')) {
    /**
     * Sostituisce la prima occorenza di una determinata stringa.
     *
     * @param string $str_pattern
     * @param string $str_replacement
     * @param string $string
     *
     * @since 2.3
     *
     * @return string
     */
    function str_replace_once($str_pattern, $str_replacement, $string)
    {
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);

            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
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
     * @return bool
     */
    function str_to_lower($string)
    {
        return S::create($string)->toLowerCase();
    }
}

if (!function_exists('str_to_upper')) {
    /**
     * Converts a string in the upper-case version.
     *
     * @param string $string
     *
     * @return bool
     */
    function str_to_upper($string)
    {
        return S::create($string)->toUpperCase();
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
     * @throws LengthException If $length is bigger than the available
     *                         character pool and $no_duplicate_chars is
     *                         enabled
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

if (!function_exists('get_remote_data')) {
    /**
     * echo get_remote_data("http://example.com/");                                // GET request
     * echo get_remote_data("http://example.com/", "var2=something&var3=blabla" ); // POST request.
     *
     * Automatically handles FOLLOWLOCATION problem;
     * Using 'replace_src'=>true, it fixes domain-relative urls  (i.e.:   src="./file.jpg"  ----->  src="http://example.com/file.jpg" )
     * Using 'schemeless'=>true, it converts urls in schemeless  (i.e.:   src="http://exampl..  ----->  src="//exampl... )\
     *
     * @source tazotodua/useful-php-scripts
     */
    function get_remote_data($url, $post_paramtrs = false, $extra = ['schemeless' => true, 'replace_src' => true, 'return_array' => false])
    {
        // start curl
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        //if parameters were passed to this function, then transform into POST method.. (if you need GET request, then simply change the passed URL)
        if ($post_paramtrs) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, (is_array($post_paramtrs) ? http_build_query($post_paramtrs) : $post_paramtrs));
        }
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_COOKIE, 'CookieName1=Value;');
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:76.0) Gecko/20100101 Firefox/76.0';
        $headers[] = 'Pragma: ';
        $headers[] = 'Cache-Control: max-age=0';
        if (!empty($post_paramtrs) && !is_array($post_paramtrs) && is_object(json_decode($post_paramtrs))) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.strlen($post_paramtrs);
        }
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_MAXREDIRS, 10);
        //if SAFE_MODE or OPEN_BASEDIR is set,then FollowLocation cant be used.. so...
        $follow_allowed = (ini_get('open_basedir') || ini_get('safe_mode')) ? false : true;
        if ($follow_allowed) {
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($c, CURLOPT_REFERER, $url);
        curl_setopt($c, CURLOPT_TIMEOUT, 60);
        curl_setopt($c, CURLOPT_AUTOREFERER, true);
        curl_setopt($c, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($c, CURLOPT_HEADER, !empty($extra['return_array']));
        $data = curl_exec($c);
        if (!empty($extra['return_array'])) {
            preg_match("/(.*?)\r\n\r\n((?!HTTP\/\d\.\d).*)/si", $data, $x);
            preg_match_all('/(.*?): (.*?)\r\n/i', trim('head_line: '.$x[1]), $headers_, PREG_SET_ORDER);
            foreach ($headers_ as $each) {
                $header[$each[1]] = $each[2];
            }
            $data = trim($x[2]);
        }
        $status = curl_getinfo($c);
        curl_close($c);
        // if redirected, then get that redirected page
        if ($status['http_code'] == 301 || $status['http_code'] == 302) {
            //if we FOLLOWLOCATION was not allowed, then re-get REDIRECTED URL
            //p.s. WE dont need "else", because if FOLLOWLOCATION was allowed, then we wouldnt have come to this place, because 301 could already auto-followed by curl  :)
            if (!$follow_allowed) {
                //if REDIRECT URL is found in HEADER
                if (empty($redirURL)) {
                    if (!empty($status['redirect_url'])) {
                        $redirURL = $status['redirect_url'];
                    }
                }
                //if REDIRECT URL is found in RESPONSE
                if (empty($redirURL)) {
                    preg_match('/(Location:|URI:)(.*?)(\r|\n)/si', $data, $m);
                    if (!empty($m[2])) {
                        $redirURL = $m[2];
                    }
                }
                //if REDIRECT URL is found in OUTPUT
                if (empty($redirURL)) {
                    preg_match('/moved\s\<a(.*?)href\=\"(.*?)\"(.*?)here\<\/a\>/si', $data, $m);
                    if (!empty($m[1])) {
                        $redirURL = $m[1];
                    }
                }
                //if URL found, then re-use this function again, for the found url
                if (!empty($redirURL)) {
                    $t = debug_backtrace();

                    return call_user_func($t[0]['function'], trim($redirURL), $post_paramtrs);
                }
            }
        }
        // if not redirected,and nor "status 200" page, then error..
        elseif ($status['http_code'] != 200) {
            $data = "ERRORCODE22 with $url<br/><br/>Last status codes:".json_encode($status)."<br/><br/>Last data got:$data";
        }
        //URLS correction
        if (function_exists('url_corrections_for_content_HELPER')) {
            $data = url_corrections_for_content_HELPER($data, $status['url'], ['schemeless' => !empty($extra['schemeless']), 'replace_src' => !empty($extra['replace_src']), 'rawgit_replace' => !empty($extra['rawgit_replace'])]);
        }
        $answer = (!empty($extra['return_array']) ? ['data' => $data, 'header' => $header, 'info' => $status] : $data);

        return $answer;
    }
    function url_corrections_for_content_HELPER($content = false, $url = false, $extra_opts = ['schemeless' => false, 'replace_src' => false, 'rawgit_replace' => false])
    {
        $GLOBALS['rdgr']['schemeless'] = $extra_opts['schemeless'];
        $GLOBALS['rdgr']['replace_src'] = $extra_opts['replace_src'];
        $GLOBALS['rdgr']['rawgit_replace'] = $extra_opts['rawgit_replace'];
        if ($GLOBALS['rdgr']['schemeless'] || $GLOBALS['rdgr']['replace_src']) {
            if ($url) {
                $GLOBALS['rdgr']['parsed_url'] = parse_url($url);
                $GLOBALS['rdgr']['urlparts']['domain_X'] = $GLOBALS['rdgr']['parsed_url']['scheme'].'://'.$GLOBALS['rdgr']['parsed_url']['host'];
                $GLOBALS['rdgr']['urlparts']['path_X'] = stripslashes(dirname($GLOBALS['rdgr']['parsed_url']['path']).'/');
                $GLOBALS['rdgr']['all_protocols'] = ['adc', 'afp', 'amqp', 'bacnet', 'bittorrent', 'bootp', 'camel', 'dict', 'dns', 'dsnp', 'dhcp', 'ed2k', 'empp', 'finger', 'ftp', 'gnutella', 'gopher', 'http', 'https', 'imap', 'irc', 'isup', 'javascript', 'ldap', 'mime', 'msnp', 'map', 'modbus', 'mosh', 'mqtt', 'nntp', 'ntp', 'ntcip', 'openadr', 'pop3', 'radius', 'rdp', 'rlogin', 'rsync', 'rtp', 'rtsp', 'ssh', 'sisnapi', 'sip', 'smtp', 'snmp', 'soap', 'smb', 'ssdp', 'stun', 'tup', 'telnet', 'tcap', 'tftp', 'upnp', 'webdav', 'xmpp'];
            }
            $GLOBALS['rdgr']['ext_array'] = [
            'src' => ['audio', 'embed', 'iframe', 'img', 'input', 'script', 'source', 'track', 'video'],
            'srcset' => ['source'],
            'data' => ['object'],
            'href' => ['link', 'area', 'a'],
            'action' => ['form'],
            //'param', 'applet' and 'base' tags are exclusion, because of a bit complex structure
        ];
            $content = preg_replace_callback(
            '/<(((?!<).)*?)>/si', 	//avoids unclosed & closing tags
            function ($matches_A) {
                $content_A = $matches_A[0];
                $tagname = preg_match('/((.*?)(\s|$))/si', $matches_A[1], $n) ? $n[2] : '';
                foreach ($GLOBALS['rdgr']['ext_array'] as $key => $value) {
                    if (in_array($tagname, $value)) {
                        preg_match('/ '.$key.'=(\'|\")/i', $content_A, $n);
                        if (!empty($n[1])) {
                            $GLOBALS['rdgr']['aphostrope_type'] = $n[1];
                            $content_A = preg_replace_callback(
                                '/( '.$key.'='.$GLOBALS['rdgr']['aphostrope_type'].')(.*?)('.$GLOBALS['rdgr']['aphostrope_type'].')/i',
                                function ($matches_B) {
                                    $full_link = $matches_B[2];
                                    //correction to files/urls
                                    if (!empty($GLOBALS['rdgr']['replace_src'])) {
                                        //if not schemeless url
                                        if (substr($full_link, 0, 2) != '//') {
                                            $replace_src_allow = true;
                                            //check if the link is a type of any special protocol
                                            foreach ($GLOBALS['rdgr']['all_protocols'] as $each_protocol) {
                                                //if protocol found - dont continue
                                                if (substr($full_link, 0, strlen($each_protocol) + 1) == $each_protocol.':') {
                                                    $replace_src_allow = false;
                                                    break;
                                                }
                                            }
                                            if ($replace_src_allow) {
                                                $full_link = $GLOBALS['rdgr']['urlparts']['domain_X'].(str_replace('//', '/', $GLOBALS['rdgr']['urlparts']['path_X'].$full_link));
                                            }
                                        }
                                    }
                                    //replace http(s) with sheme-less urls
                                    if (!empty($GLOBALS['rdgr']['schemeless'])) {
                                        $full_link = str_replace(['https://', 'http://'], '//', $full_link);
                                    }
                                    //replace github mime
                                    if (!empty($GLOBALS['rdgr']['rawgit_replace'])) {
                                        $full_link = str_replace('//raw.github'.'usercontent.com/', '//rawgit.com/', $full_link);
                                    }
                                    $matches_B[2] = $full_link;
                                    unset($matches_B[0]);
                                    $content_B = '';
                                    foreach ($matches_B as $each) {
                                        $content_B .= $each;
                                    }

                                    return $content_B;
                                },
                                $content_A
                            );
                        }
                    }
                }

                return $content_A;
            },
            $content
        );
            $content = preg_replace_callback(
            '/style="(.*?)background(\-image|)(.*?|)\:(.*?|)url\((\'|\"|)(.*?)(\'|\"|)\)/i',
            function ($matches_A) {
                $url = $matches_A[7];
                $url = (substr($url, 0, 2) == '//' || substr($url, 0, 7) == 'http://' || substr($url, 0, 8) == 'https://' ? $url : '#');

                return 'style="'.$matches_A[1].'background'.$matches_A[2].$matches_A[3].':'.$matches_A[4].'url('.$url.')'; //$matches_A[5] is url taged ,7 is url
            },
            $content
        );
        }

        return $content;
    }
}
