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

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 * Se descrizione = 1 e il tipo è 'query=' mi restituisce il valore del campo descrizione della query.
 *
 * @deprecated 2.4.2

 *
 * @param string $name
 * @param string $sezione
 * @param string $descrizione
 *
 * @return mixed
 */
function get_var($nome, $sezione = null, $descrizione = false, $again = false)
{
    return setting($nome, $again);
}
