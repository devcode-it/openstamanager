<?php

namespace Modules\Emails\OAuth2;

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

    public function __construct(array $options = [], array $collaborators = [])
    {
        // Configurazioni specifiche per il provider di Microsoft Azure
        $config = array_merge($options, [
            'defaultEndPointVersion' => parent::ENDPOINT_VERSION_2_0,
            'tenant' => $options['tenant_id'],
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
                'required' => true,
            ],
        ];
    }
}
