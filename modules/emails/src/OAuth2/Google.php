<?php

namespace Modules\Emails\OAuth2;

use League\OAuth2\Client\Provider\Google as OriginalProvider;

class Google extends OriginalProvider implements ProviderInterface
{
    protected static $options = [
        'scope' => ['https://mail.google.com/'],
        'access_type' => 'offline',
    ];

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Configurazioni specifiche per il provider di Microsoft Azure
        $config = array_merge($options, [
            'redirectUri' => base_url().'/oauth2.php',
        ]);

        parent::__construct($config, $collaborators);
    }

    public function getOptions()
    {
        return self::$options;
    }

    public static function getConfigInputs()
    {
        return [];
    }

    protected function getDefaultScopes(): array
    {
        return ['https://mail.google.com/'];
    }
}
