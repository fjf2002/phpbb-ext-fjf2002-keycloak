<?php
namespace fjf2002\keycloak\core;


class keycloak extends \phpbb\auth\provider\oauth\service\base {
	/**
	 * @var phpbb_config
	 */
	protected $config;

	/**
	 * @var phpbb_request
	 */
	protected $request;

	/**
	 * @var UsersAndGroupsInterface
	 */
	protected $usersAndGroupsInterface;

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
			throw new phpbb\auth\provider\oauth\service\exception('AUTH_PROVIDER_OAUTH_ERROR_INVALID_SERVICE_TYPE');
		}

		// This was a callback request from Keycloak IDP, get the token
		$token = $this->service_provider->requestAccessToken($this->request->variable('code', ''));
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
