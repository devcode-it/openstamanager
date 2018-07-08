<?php

// trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

/**
 * Individua il codice successivo.
 *
 * @deprecated 2.4
 *
 * @param string $str
 * @param int    $qty
 * @param string $mask
 */
function get_next_code($str, $qty = 1, $mask = '')
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    return Util\Generator::generate($mask, $str, $qty);
}
