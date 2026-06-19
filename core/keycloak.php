<?php
namespace fjf2002\keycloak\core;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\OAuth2\Service\Exception\InvalidAuthorizationStateException;
use \phpbb\auth\provider\oauth\service\exception;


class keycloak extends \phpbb\auth\provider\oauth\service\base {
	protected \phpbb\config\config $config;

	protected \phpbb\request\request_interface $request;

	protected UsersAndGroupsInterface $usersAndGroupsInterface;

	public function __construct(\phpbb\config\config $config, \phpbb\request\request_interface $request, UsersAndGroupsInterface $usersAndGroupsInterface) {
		$this->config = $config;
		$this->request = $request;
		$this->usersAndGroupsInterface = $usersAndGroupsInterface;

		// TODO: Find a better way to load this class
		require_once(__DIR__ . '/../service/Keycloak.php');
	}

	public function get_service_credentials() {
		return [
			'key'		=> $this->config['auth_oauth_keycloak_key'],
			'secret'	=> $this->config['auth_oauth_keycloak_secret'],
		];
	}

	public function perform_auth_login() {
		if (!($this->service_provider instanceof \OAuth\OAuth2\Service\Keycloak)) {
			throw new exception('AUTH_PROVIDER_OAUTH_ERROR_INVALID_SERVICE_TYPE');
		}

		// This was a callback request from Keycloak IDP, get the token
		try {
			$token = $this->service_provider->requestAccessToken(
				$this->request->variable('code', '')
			);
		} catch (InvalidAuthorizationStateException|TokenResponseException $e) {
			throw new exception('AUTH_PROVIDER_OAUTH_ERROR_REQUEST');
		}

		$accessTokenPayload = json_decode(base64_decode(explode(".", $token->getAccessToken())[1]));

		$username = $accessTokenPayload->preferred_username;

		$userRow = $this->usersAndGroupsInterface->getOrCreateUser(
			$this->request->variable('oauth_service', '', false), // always 'keycloak'
			$username,
			$accessTokenPayload->email,
			$accessTokenPayload->groups ?? []
		);

		return $username;
	}

	public function perform_token_auth() {
		throw new \Exception('perform_token_auth is not implemented.');
	}

	public function get_auth_scope() {
		return ['openid'];
	}
}
