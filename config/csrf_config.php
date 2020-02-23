<?php

/**
 * Configuration file for CSRF Protector.
 */
return [
    'logDirectory' => DOCROOT.'/logs',
    'failedAuthAction' => [
        'GET' => 0,
        'POST' => 0,
    ],
    'jsUrl' => ROOTDIR.'/assets/dist/js/csrf/csrfprotector.js',
    'tokenLength' => 10,
    'cookieConfig' => [
        'path' => ROOTDIR,
        'secure' => isHTTPS(true),
    ],
    'verifyGetFor' => [],
    'CSRFP_TOKEN' => '',
    'disabledJavascriptMessage' => '',
];
