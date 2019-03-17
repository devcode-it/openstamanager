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
    return \App::getContainer()['database'];
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
    return database()->prepare($parameter);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param  Nome del parametro
 * @param string $method Posizione del parametro (post o get)
 * @param bool   $parse  Restituire il valore formattato
 *
 * @since 2.3
 *
 * @return string
 */
function filter($param, $method = null, $parse = false)
{
    return container()->filter->getValue($param, $method, $parse);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del parametro
 * @param bool   $parse Restituire il valore formattato
 *
 * @since 2.3
 *
 * @return string
 */
function post($param, $parse = false)
{
    return container()->filter->getValue($param, 'post', $parse);
}

/**
 * Restituisce il contenuto sanitarizzato dell'input dell'utente.
 *
 * @param string $param Nome del parametro
 * @param bool   $parse Restituire il valore formattato
 *
 * @since 2.3
 *
 * @return string
 */
function get($param, $parse = false)
{
    return container()->filter->getValue($param, 'get', $parse);
}

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 *
 * @param string $name
 * @param bool   $again
 *
 * @since 2.4.2
 *
 * @return string
 */
function setting($name, $again = false)
{
    return \Settings::getValue($name);
}

/**
 * Restituisce l'oggetto dedicato alla gestione dei messaggi per l'utente.
 *
 * @since 2.4.2
 *
 * @return \Util\Messages
 */
function flash()
{
    return \App::getContainer()['flash'];
}

/**
 * Restituisce l'oggetto dedicato alla gestione dell'autenticazione degli utente.
 *
 * @since 2.4.2
 *
 * @return \Auth
 */
function auth()
{
    return \App::getContainer()['auth'];
}

/**
 * Restituisce l'oggetto dedicato alla gestione della traduzione del progetto.
 *
 * @since 2.4.2
 *
 * @return \Translator
 */
function trans()
{
    return \App::getContainer()['translator'];
}

/**
 * Restituisce l'oggetto dedicato alla gestione della conversione di numeri e date.
 *
 * @since 2.4.2
 *
 * @return \Intl\Formatter
 */
function formatter()
{
    return \App::getContainer()['formatter'];
}

/**
 * Restituisce la traduzione del messaggio inserito.
 *
 * @param string $string
 * @param array  $parameters
 * @param array  $operations
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

/**
 * Restituisce l'oggetto dedicato alla gestione dei log.
 *
 * @since 2.4.2
 *
 * @return \Monolog\Logger
 */
function logger()
{
    return \App::getContainer()['logger'];
}

/**
 * Restituisce il numero indicato formattato secondo la configurazione del sistema.
 *
 * @param float $number
 * @param int   $decimals
 *
 * @return string
 *
 * @since 2.4.8
 */
function numberFormat($number, $decimals)
{
    return Translator::numberToLocale($number, $decimals);
}

/**
 * Restituisce il timestamp indicato formattato secondo la configurazione del sistema.
 *
 * @param string $timestamp
+ *
 * @return string
 *
 * @since 2.4.8
 */
function timestampFormat($timestamp)
{
    return Translator::timestampToLocale($timestamp);
}

/**
 * Restituisce la data indicata formattato secondo la configurazione del sistema.
 *
 * @param string $date
 *
 * @return string
 *
 * @since 2.4.8
 */
function dateFormat($date)
{
    return Translator::dateToLocale($date);
}

/**
 * Restituisce l'orario indicato formattato secondo la configurazione del sistema.
 *
 * @param string $time
 *
 * @return string
 *
 * @since 2.4.8
 */
function timeFormat($time)
{
    return Translator::timeToLocale($time);
}

/**
 * Restituisce la distanza in tempo formattata per l'utente.
 *
 * @param string $timestamp
 *
 * @return string
 *
 * @since 2.5
 */
function diffForHumans($timestamp)
{
    return \Carbon\Carbon::parse($timestamp)->diffForHumans();
}

/**
 * Restituisce il percorso per la risorsa $name.
 *
 * @param string $name
 * @param array  $parameters
 *
 * @return string
 *
 * @since 2.5
 */
function pathFor($name, $parameters = [])
{
    $router = container()->router;

    return $router->pathFor($name, $parameters);
}

/**
 * Restituisce il contenitore Slim per DI.
 *
 * @return \Slim\Container
 *
 * @since 2.5
 */
function container()
{
    return App::getContainer();
}
