<?php
namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;


class Keycloak extends AbstractService {

    const SCOPE_OPENID = 'openid';

    public function __construct(Credentials $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, array $scopes = [], UriInterface $baseApiUri = null) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        global $config;
        $this->baseApiUri = new Uri($config['auth_oauth_keycloak_url']);
    }

    /**
     * Returns a class constant from ServiceInterface defining the authorization method used for the API
     * Header is the sane default.
     *
     * @return int
     */
    protected function getAuthorizationMethod() {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAuthorizationEndpoint() {
        return new Uri($this->baseApiUri->getAbsoluteUri() . '/auth');
    }

    /**
     * @return \OAuth\Common\Http\Uri\UriInterface
     */
    public function getAccessTokenEndpoint() {
        return new Uri($this->baseApiUri->getAbsoluteUri() . '/token');
    }

    /**
     * @param string $responseBody
     * @return \OAuth\Common\Token\TokenInterface|\OAuth\OAuth2\Token\StdOAuth2Token
     * @throws \OAuth\Common\Http\Exception\TokenResponseException
     */
    protected function parseAccessTokenResponse($responseBody) {
		$data = json_decode($responseBody, true);

        if (null === $data || !is_array($data) ) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'] ) ) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();

        $token->setAccessToken($data['access_token'] );
        $token->setLifeTime($data['expires_in'] );

        if (isset($data['refresh_token'] ) ) {
            $token->setRefreshToken($data['refresh_token'] );
            unset($data['refresh_token']);
        }

        unset($data['access_token'] );
        unset($data['expires_in'] );
        $token->setExtraParams($data );

        return $token;
    }
}
