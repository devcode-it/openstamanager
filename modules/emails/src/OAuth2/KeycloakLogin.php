<?php

namespace Modules\Emails\OAuth2;

use League\OAuth2\Client\Provider\GenericProvider;

class KeycloakLogin extends GenericProvider implements ProviderInterface
{
    /**
     * Impostazioni native per la connessione.
     *
     * @var string[][]
     */
    protected static $options = [];

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Configurazioni specifiche per il provider Keycloak
        $config = array_merge($options, [
            'urlAuthorize' => $options['auth_server_url'].'/realms/'.$options['realm'].'/protocol/openid-connect/auth',
            'urlAccessToken' => $options['auth_server_url'].'/realms/'.$options['realm'].'/protocol/openid-connect/token',
            'urlResourceOwnerDetails' => $options['auth_server_url'].'/realms/'.$options['realm'].'/protocol/openid-connect/userinfo',
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
            'auth_server_url' => [
                'label' => 'Auth Server URL',
                'type' => 'text',
            ],
            'realm' => [
                'label' => 'Realm',
                'type' => 'text',
            ]
        ];
    }

    public function getUser($access_token)
    {
        $response = $this->getAuthenticatedRequest(
            'GET',
            $this->getResourceOwnerDetailsUrl($access_token),
            $access_token
        );

        $user = $this->getParsedResponse($response);

        return $user['email'] ?? null;
    }
}
