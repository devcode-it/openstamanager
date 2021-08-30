<?php

namespace Modules\Emails\OAuth2;

use League\OAuth2\Client\Provider\Google as OriginalProvider;
use Modules\Emails\Account;

class Google extends OriginalProvider implements ProviderInterface
{
    protected static $options = [
        'scope' => ['https://mail.google.com/'],
        'accessType' => 'offline',
    ];

    public function __construct(Account $account, $redirect_uri)
    {
        parent::__construct([
            'clientId' => $account->client_id,
            'clientSecret' => $account->client_secret,
            'redirectUri' => $redirect_uri,
            'accessType' => 'offline',
        ]);
    }

    public function getOptions()
    {
        return self::$options;
    }

    public static function getConfigInputs()
    {
        return [];
    }
}
