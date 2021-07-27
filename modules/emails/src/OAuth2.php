<?php

namespace Modules\Emails;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Token\AccessToken;
use TheNetworg\OAuth2\Client\Provider\Azure;

class OAuth2
{
    public static $providers = [
        'microsoft' => [
            'name' => 'Microsoft',
            'class' => Azure::class,
            'options' => [
                'scope' => [
                    'offline_access',
                    'https://graph.microsoft.com/SMTP.Send',
                    //'https://outlook.office.com/IMAP.AccessAsUser.All'
                ],
            ],
            'help' => 'https://docs.openstamanager.com/faq/configurazione-oauth2#microsoft',
        ],
        'google' => [
            'name' => 'Google',
            'class' => Google::class,
            'options' => [
                'scope' => ['https://mail.google.com/'],
                'accessType' => 'offline',
            ],
            'help' => 'https://docs.openstamanager.com/faq/configurazione-oauth2#google',
        ],
    ];
    protected $provider;
    protected $account;

    public function __construct(Account $account)
    {
        $this->account = $account;

        $this->init();
    }

    /**
     * Inizializza il ->inprovider per l'autenticazione OAuth2.
     */
    public function init()
    {
        $redirect_uri = base_url().'/oauth2.php';

        $class = $this->getProviderConfiguration()['class'];

        // Authorization
        $this->provider = new $class([
            'clientId' => $this->account->client_id,
            'clientSecret' => $this->account->client_secret,
            'redirectUri' => $redirect_uri,
        ]);

        // Configurazioni specifiche per il provider di Microsoft Azure
        if ($this->provider instanceof Azure) {
            $this->provider->defaultEndPointVersion = Azure::ENDPOINT_VERSION_2_0;
            $this->provider->tenant = 'consumers';
        }
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
        $options = $this->getProviderConfiguration()['options'];
        if (empty($code)) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl($options);

            // Get the state generated for you and store it to the session.
            $this->account->oauth2_state = $provider->getState();
            $this->account->save();

            // Redirect the user to the authorization URL.
            return $authorizationUrl;
        } elseif (!empty($this->account->oauth2_state) && $this->account->oauth2_state !== $state) {
            $this->account->oauth2_state = null;
            $this->account->save();

            throw new InvalidArgumentException();
        } else {
            $this->account->oauth2_state = null;
            $this->account->save();

            // Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            //dd($accessToken);

            $this->setAccessToken($accessToken);
        }

        return null;
    }

    /**
     * Imposta l'access token per l'autenticazione OAuth2.
     *
     * @param AccessToken|null
     */
    public function setAccessToken($value)
    {
        $this->account->access_token = serialize($value);
        $this->account->save();
    }

    /**
     * Restituisce l'access token per l'autenticazione OAuth2.
     *
     * @return AccessToken|null
     */
    public function getAccessToken()
    {
        $access_token = unserialize($this->account->access_token);

        if (!empty($access_token) && $access_token->hasExpired()) {
            // Tentativo di refresh del token di accessp
            if (!empty($access_token->getRefreshToken())) {
                $access_token = $this->getProvider()->getAccessToken('refresh_token', [
                    'refresh_token' => $access_token->getRefreshToken(),
                ]);
            } else {
                $access_token = null;
            }

            $this->setAccessToken($access_token);
        }

        return $access_token;
    }

    public function getRefreshToken()
    {
        $access_token = unserialize($this->account->access_token);

        if (!empty($access_token)) {
            return $access_token->getRefreshToken();
        }

        return null;
    }
}
