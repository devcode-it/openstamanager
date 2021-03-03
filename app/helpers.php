<?php

/**
 * Gets the current locale.
 *
 * @return string
 */
function locale(): string
{
    return app()->getLocale();
}

/**
 * Get the language portion of the locale.
 * (ex. en_GB returns en)
 *
 * @return string
 */
function localeLanguage(): string
{
    $locale = locale();

    return substr($locale, 0, strpos($locale, "_"));
}
