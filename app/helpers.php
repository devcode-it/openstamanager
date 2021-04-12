<?php

/**
 * Gets the current locale.
 */
function locale(): string
{
    $locale = app()->getLocale();
    $dot = strpos($locale, '.');

    return substr($locale, 0, $dot === false ? null : $dot);
}

/**
 * Get the language portion of the locale.
 * (ex. en_GB returns en).
 */
function localeLanguage(): string
{
    $locale = locale();

    return substr($locale, 0, strpos($locale, '_'));
}
