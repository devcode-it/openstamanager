<?php

namespace Modules\Emails;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Modules\Emails\OAuth2\Google;
use Modules\Emails\OAuth2\Microsoft;

class OAuth2
{
    public static $providers = [
        'microsoft' => [
            'name' => 'Microsoft',
            'class' => Microsoft::class,
            'help' => 'https://docs.openstamanager.com/faq/configurazione-oauth2#microsoft',
        ],
        'google' => [
            'name' => 'Google',
            'class' => Google::class,
            'help' => 'https://docs.openstamanager.com/faq/configurazione-oauth2#google',
        ],
    ];

    protected $provider;
    protected $account;

    public function __construct(Account $account)
    {
        $this->account = $account;

        // Inizializza il provider per l'autenticazione OAuth2.
        $redirect_uri = base_url().'/oauth2.php';

        $class = $this->getProviderConfiguration()['class'];
        $this->provider = new $class($this->account, $redirect_uri);
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getProviderConfiguration()
    {
        return self::$providers[$this->account->provider];
    }

    public function needsConfiguration()
    {
        $access_token = $this->getAccessToken();

        return empty($access_token);
    }

    /**
     * Gestisce le operazioni di configurazione per l'autenticazione OAuth2.
     * Restituisce l'URL di redirect per le operazioni di aggiornamento dei dati, lancia un eccezione in caso di errori e restituisce null in caso di completamento della configurazione.
     *
     * Nota: l'autenticazione OAuth2 richiede una serie di richieste su una singola pagina
     *  - Richiesta di autenticazione al server remoto (code, state vuoti)
     *  - Conferma di autenticazione alla pagina di redirect (code, state impostati)
     *  - Richiesta del token di accesso dalla pagina di redirect al server remoto
     *
     * @param string|null $code
     * @param string|null $state
     *
     * @throws IdentityProviderException
     * @throws InvalidArgumentException
     *
     * @return string|null
     */
    public function configure($code, $state)
    {
        if (!$this->needsConfiguration()) {
            return null;
        }

        $provider = $this->getProvider();
        $options = $provider->getOptions();
        if (empty($code)) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorization_url = $provider->getAuthorizationUrl($options);

            // Get the state generated for you and store it to the session.
            $this->account->oauth2_state = $provider->getState();
            $this->account->save();

            // Redirect the user to the authorization URL.
            return $authorization_url;
        } elseif (!empty($this->account->oauth2_state) && $this->account->oauth2_state !== $state) {
            $this->account->oauth2_state = null;
            $this->account->save();

            throw new InvalidArgumentException();
        } else {
            $this->account->oauth2_state = null;
            $this->account->save();

            // Try to get an access token using the authorization code grant
            $access_token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            $refresh_token = $access_token->getRefreshToken();

            $this->updateTokens($access_token, $refresh_token);
        }

        return null;
    }

    public function getRefreshToken()
    {
        $this->checkTokens();

        return $this->account->refresh_token;
    }

    /**
     * Restituisce l'access token per l'autenticazione OAuth2.
     *
     * @return AccessToken|null
     */
    public function getAccessToken()
    {
        $this->checkTokens();

        return unserialize($this->account->access_token);
    }

    /**
     * Imposta l'access token per l'autenticazione OAuth2.
     *
     * @param AccessToken|null
     */
    public function updateTokens($access_token, $refresh_token)
    {
        $this->account->access_token = serialize($access_token);

        $previous_refresh_token = $this->account->refresh_token;
        $this->account->refresh_token = $refresh_token ?: $previous_refresh_token;

        $this->account->save();
    }

    protected function checkTokens()
    {
        $access_token = unserialize($this->account->access_token);

        if (!empty($access_token) && $access_token->hasExpired()) {
            // Tentativo di refresh del token di accesso
            $refresh_token = $this->account->refresh_token;
            if (!empty($refresh_token)) {
                $access_token = $this->getProvider()->getAccessToken('refresh_token', [
                    'refresh_token' => $this->account->refresh_token,
                ]);

                $refresh_token = $access_token->getRefreshToken();
            } else {
                $access_token = null;
                $refresh_token = null;
            }

            $this->updateTokens($access_token, $refresh_token);
        }
    }
}
