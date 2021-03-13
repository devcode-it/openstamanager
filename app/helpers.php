<?php

/**
 * Gets the current locale.
 */
function locale(): string
{
    return app()->getLocale();
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
