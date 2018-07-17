<?php

/**
 * Funzioni di aiuto per la semplificazione del codice.
 *
 * @since 2.4.2
 */

/**
 * Restituisce l'oggetto dedicato alla gestione della connessione con il database.
 *
 * @return \Database
 */
function database()
{
    return \Database::getConnection();
}

/**
 * Prepara il parametro inserito per l'inserimento in una query SQL.
 * Attenzione: protezione di base contro SQL Injection.
 *
 * @param string $parameter
 *
 * @since 2.3
 *
 * @return mixed
 */
function prepare($parameter)
{
    return p($parameter);
}

/**
 * Prepara il parametro inserito per l'inserimento in una query SQL.
 * Attenzione: protezione di base contro SQL Injection.
 *
 * @param string $parameter
 *
 * @since 2.3
 *
 * @return mixed
 */
function p($parameter)
{
    return \Database::getConnection()->prepare($parameter);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param  Nome del parametro
 * @param string $method Posizione del parametro (post o get)
 * @param bool   $raw    Restituire il valore non formattato
 *
 * @since 2.3
 *
 * @return string
 */
function filter($param, $method = null, $raw = false)
{
    return \Filter::getValue($param, $method, $raw);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del parametro
 * @param bool   $raw   Restituire il valore non formattato
 *
 * @since 2.3
 *
 * @return string
 */
function post($param, $raw = false)
{
    return \Filter::getValue($param, 'post', $raw);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del parametro
 * @param bool   $raw   Restituire il valore non formattato
 *
 * @since 2.3
 *
 * @return string
 */
function get($param, $raw = false)
{
    return \Filter::getValue($param, 'get', $raw);
}

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 * Se descrizione è 1 e il tipo dell'impostazione è 'query=' mi restituisce il valore del campo descrizione della query.
 *
 * @param string $name
 * @param bool   $descrizione
 * @param bool   $again
 *
 * @return string
 */
function setting($nome, $again = false)
{
    return \Settings::getValue($nome, $again);
}

/**
 * Restituisce l'oggetto dedicato alla gestione dei messaggi per l'utente.
 *
 * @return \Util\Messages
 */
function flash()
{
    return \App::flash();
}

/**
 * Restituisce l'oggetto dedicato alla gestione dell'autenticazione degli utente.
 *
 * @return \Auth
 */
function auth()
{
    return \Auth::getInstance();
}

/**
 * Restituisce l'oggetto dedicato alla gestione della traduzione del progetto.
 *
 * @return \Translator
 */
function trans()
{
    return \Translator::getInstance();
}

/**
 * Restituisce l'oggetto dedicato alla gestione della conversione di numeri e date.
 *
 * @return \Intl\Formatter
 */
function formatter()
{
    return \Translator::getFormatter();
}

/**
 * Restituisce la traduzione del messaggio inserito.
 *
 * @param string $string
 * @param array  $parameters
 * @param string $operations
 *
 * @since 2.3
 *
 * @return string
 */
function tr($string, $parameters = [], $operations = [])
{
    return \Translator::translate($string, $parameters, $operations);
}

// Retrocompatibilità (con la funzione gettext)
if (!function_exists('_')) {
    function _($string, $parameters = [], $operations = [])
    {
        return tr($string, $parameters, $operations);
    }
}
