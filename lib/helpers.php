<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
 * Funzioni di aiuto per la semplificazione del codice.
 *
 * @since 2.4.2
 */
use HTMLBuilder\HTMLBuilder;

/**
 * Restituisce l'oggetto dedicato alla gestione della connessione con il database.
 *
 * @return Database
 */
function database()
{
    return Database::getConnection();
}

/**
 * Prepara il parametro inserito per l'inserimento in una query SQL.
 * Attenzione: protezione di base contro SQL Injection.
 *
 * @param string $parameter
 *
 * @since 2.3
 */
function prepare($parameter)
{
    return database()->prepare((string) $parameter);
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
    return Filter::getValue($param, $method, $raw);
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
    return Filter::getValue($param, 'post', $raw);
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
    return Filter::getValue($param, 'get', $raw);
}

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 *
 * @param string $name Nome dell'impostazione
 *
 * @since 2.4.2
 *
 * @return string
 */
function setting($name)
{
    // Utilizza la classe Settings che implementa il caching per ridurre le query
    /** @var Models\Setting $setting */
    $setting = Settings::get($name);

    $user = Auth::user();
    $user_options = [];

    if ($user) {
        $user_options = json_decode((string) $user->options ?: '', true) ?: [];
    }

    // Controlla se l'utente ha una personalizzazione per questa impostazione
    if (isset($user_options['settings'][$setting->id]) && $user_options['settings'][$setting->id] !== null) {
        $value = $user_options['settings'][$setting->id];
        $value = is_array($value) ? implode(',', $value) : $value;
    } else {
        $value = $setting->valore;
    }

    return $value;
}

/**
 * Restituisce l'oggetto dedicato alla gestione dei messaggi per l'utente.
 *
 * @since 2.4.2
 *
 * @return Util\Messages
 */
function flash()
{
    return App::flash();
}

/**
 * Restituisce l'oggetto dedicato alla gestione dell'autenticazione degli utente.
 *
 * @since 2.4.2
 *
 * @return Auth
 */
function auth()
{
    return Auth::getInstance();
}

/**
 * Restituisce l'oggetto dedicato alla gestione della traduzione del progetto.
 *
 * @since 2.4.2
 *
 * @return Translator
 */
function trans()
{
    return Translator::getInstance();
}

/**
 * Restituisce l'oggetto dedicato alla gestione della conversione di numeri e date.
 *
 * @since 2.4.2
 *
 * @return Intl\Formatter
 */
function formatter()
{
    return Translator::getFormatter();
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
    return Translator::translate($string, $parameters, $operations);
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
 * @return Monolog\Logger
 */
function logger_osm()
{
    return Monolog\Registry::getInstance('logs');
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
function numberFormat($number, $decimals = null)
{
    return Translator::numberToLocale($number, $decimals);
}

/**
 * Restituisce il timestamp indicato formattato secondo la configurazione del sistema.
 *
 * @param string $timestamp
 *                          + *
 *
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
 * Restituisce il simbolo della valuta del gestione.
 *
 * @since 2.4.9
 *
 * @return string
 */
function currency()
{
    return Translator::getCurrency();
}

/**
 * Restituisce il numero indicato formattato come una valuta secondo la configurazione del sistema.
 *
 * @return string
 *
 * @since 2.4.9
 */
function moneyFormat($number, $decimals = null)
{
    if (setting('Posizione del simbolo valuta') == 'Prima') {
        return tr('_CURRENCY_ _TOTAL_', [
            '_CURRENCY_' => currency(),
            '_TOTAL_' => numberFormat($number, $decimals),
        ]);
    } else {
        return tr('_TOTAL_ _CURRENCY_', [
            '_TOTAL_' => numberFormat($number, $decimals),
            '_CURRENCY_' => currency(),
        ]);
    }
}

/**
 * Restituisce il numero indicato formattato come una valuta secondo la configurazione del sistema.
 *
 * @return string
 *
 * @since 2.4.11
 */
function input(array $json)
{
    return HTMLBuilder::parse($json);
}
