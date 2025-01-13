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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

class OAuth2 extends Model
{
    use SimpleModelTrait;

    protected $provider;

    protected $table = 'zz_oauth2';

    protected $casts = [
        'config' => 'array',
    ];

    /**
     * @return AbstractProvider
     */
    public function getProvider()
    {
        // Inizializza il provider per l'autenticazione OAuth2.
        if (!isset($this->provider)) {
            $config = $this->config ?? [];
            $config = array_merge($config, [
                'clientId' => $this->client_id,
                'clientSecret' => $this->client_secret,
            ]);

            $class = $this->class;
            if (!class_exists($class)) {
                throw new \InvalidArgumentException('Classe non esistente');
            }

            $this->provider = new $class($config);
        }

        return $this->provider;
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
     * @throws \InvalidArgumentException
     *
     * @return string|null
     */
    public function configure($code, $state)
    {
        if (!$this->needsConfiguration()) {
            return null;
        }

        $provider = $this->getProvider();
        $options = method_exists($provider, 'getOptions') ? $provider->getOptions() : [];
        if (empty($code)) {
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorization_url = $provider->getAuthorizationUrl($options);

            // Get the state generated for you and store it to the session.
            $this->state = $provider->getState();
            $this->save();

            // Redirect the user to the authorization URL.
            return ['authorization_url' => $authorization_url];
        } elseif (!empty($this->state) && $this->state !== $state) {
            $this->state = null;
            $this->save();

            throw new \InvalidArgumentException();
        } else {
            $this->state = null;
            $this->save();

            // Try to get an access token using the authorization code grant
            $access_token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            $refresh_token = $access_token->getRefreshToken();

            $this->updateTokens($access_token, $refresh_token);

            return ['access_token' => $access_token];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken()
    {
        $this->checkTokens();

        return $this->attributes['refresh_token'];
    }

    /**
     * Restituisce l'access token per l'autenticazione OAuth2.
     *
     * @return AccessToken|null
     */
    public function getAccessToken()
    {
        $this->checkTokens();

        return $this->attributes['access_token'] ? unserialize($this->attributes['access_token']) : '';
    }

    /**
     * Effettua una richiesta utilizzando il token di accesso prestabilito.
     *
     * @param string $method
     * @param string $url
     * @param array  $options
     *
     * @return array
     */
    public function request($method, $url, $options = [])
    {
        $provider = $this->getProvider();
        $accessToken = $this->getAccessToken();

        $request = $provider->getAuthenticatedRequest($method, $url, $accessToken, $options);

        return $provider->getParsedResponse($request);
    }

    /**
     * Imposta Access Token e Refresh Token per l'autenticazione OAuth2.
     *
     * @param AccessToken|null
     */
    protected function updateTokens($access_token, $refresh_token)
    {
        $this->access_token = serialize($access_token);

        $previous_refresh_token = $this->refresh_token;
        $this->refresh_token = $refresh_token ?: $previous_refresh_token;

        $this->save();
    }

    /**
     * Controlla la validità dei token correnti e ne effettua il refresh se necessario.
     */
    protected function checkTokens()
    {
        $access_token = $this->access_token ? unserialize($this->access_token) : '';

        if (!empty($access_token) && $access_token->hasExpired()) {
            // Tentativo di refresh del token di accesso
            $refresh_token = $this->refresh_token;
            if (!empty($refresh_token)) {
                $access_token = $this->getProvider()->getAccessToken('refresh_token', [
                    'refresh_token' => $this->refresh_token,
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
