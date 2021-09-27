<?php

namespace Modules\Emails\OAuth2;

use League\OAuth2\Client\Provider\Google as OriginalProvider;

class Google extends OriginalProvider implements ProviderInterface
{
    protected static $options = [
        'scope' => ['https://mail.google.com/'],
        'accessType' => 'offline',
    ];

    public function getOptions()
    {
        return self::$options;
    }

    public static function getConfigInputs()
    {
        return [];
    }
}
