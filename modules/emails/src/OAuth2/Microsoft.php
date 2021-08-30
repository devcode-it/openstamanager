<?php

namespace Modules\Emails\OAuth2;

use Modules\Emails\Account;
use TheNetworg\OAuth2\Client\Provider\Azure;

class Microsoft extends Azure implements ProviderInterface
{
    /**
     * Impostazioni native per la connessione.
     *
     * Ufficialmente lo scope dovrebbe comprendere 'https://graph.microsoft.com/SMTP.Send', a causa di un quirk interno bisogna utilizzare 'https://outlook.office.com/SMTP.Send'.
     *
     * @source https://github.com/decomplexity/SendOauth2/blob/main/MSFT%20OAuth2%20quirks.md
     *
     * @var \string[][]
     */
    protected static $options = [
        'scope' => [
            'offline_access',
            'https://outlook.office.com/SMTP.Send',
            //'https://outlook.office.com/IMAP.AccessAsUser.All'
        ],
    ];

    public function __construct(Account $account, $redirect_uri)
    {
        parent::__construct([
            'clientId' => $account->client_id,
            'clientSecret' => $account->client_secret,
            'redirectUri' => $redirect_uri,
            'accessType' => 'offline',
        ]);

        // Configurazioni specifiche per il provider di Microsoft Azure
        $this->defaultEndPointVersion = parent::ENDPOINT_VERSION_2_0;
        $this->tenant = $account->oauth2_config['tenant_id'];
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
                'required' => true,
            ],
        ];
    }
}
