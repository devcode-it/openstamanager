<?php

return [
    'php' => [
        'zip' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'mbstring' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'pdo_mysql' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'dom' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'xsl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'openssl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'intl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'curl' => [
            'type' => 'ext',
            'required' => 1,
        ],
        'soap' => [
            'type' => 'ext',
            'required' => 1,
        ],

        'upload_max_filesize' => [
            'type' => 'value',
            'suggested' => '>32M',
        ],
        'post_max_size' => [
            'type' => 'value',
            'suggested' => '>32M',
        ],
    ],
    'apache' => [
        'mod_rewrite' => [
            'server' => 'HTTP_MOD_REWRITE',
        ],
    ],
    'directories' => [
        'backup',
        'files',
        'logs',
    ],
];
