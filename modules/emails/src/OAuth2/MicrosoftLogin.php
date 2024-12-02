<?php

namespace Modules\Emails\OAuth2;

use TheNetworg\OAuth2\Client\Provider\Azure;

class MicrosoftLogin extends Azure implements ProviderInterface
{
    /**
     * Impostazioni native per la connessione.
     *
     * @var string[][]
     */
    protected static $options = [
        'scope' => [
            'https://graph.microsoft.com/User.Read',
        ],
    ];

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Configurazioni specifiche per il provider di Microsoft Azure
        $config = array_merge($options, [
            'defaultEndPointVersion' => parent::ENDPOINT_VERSION_2_0,
            'tenant' => $options['tenant_id'],
            'redirectUri' => base_url().'/oauth2_login.php',
        ]);

        parent::__construct($config, $collaborators);
    }

    public function getOptions()
    {
        return self::$options;
    }

    public static function getConfigInputs()
    {
        return [
            'tenant_id' => [
                'label' => 'Tenant ID',
                'type' => 'text',
            ],
        ];
    }

    public function getUser($access_token)
    {
        $me = $this->get('https://graph.microsoft.com/v1.0/me', $access_token);

        return $me['userPrincipalName'];
    }
}
