<?php

namespace HTMLBuilder\Manager;

/**
 * @since 2.3
 */
class CSRFManager implements ManagerInterface
{
    public function manage($options)
    {
        $token = \CSRF::getInstance()->getToken();

        $keys = array_keys($token);
        $values = array_values($token);

        $result = '
{[ "type": "hidden", "name": "'.$keys[0].'", "value": "'.$values[0].'" ]}
{[ "type": "hidden", "name": "'.$keys[1].'", "value": "'.$values[1].'" ]}';

        return $result;
    }
}
