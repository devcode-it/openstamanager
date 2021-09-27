<?php

namespace Modules\Emails\OAuth2;

interface ProviderInterface
{
    /**
     * Restituisce l'array di configurazione per la connessione remota al servizio del provider.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Restituisce un insieme di campi aggiuntivi richiesti per la configurazione del provider.
     *
     * @return array
     */
    public static function getConfigInputs();
}
