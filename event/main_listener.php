<?php
namespace fjf2002\keycloak\event;

use phpbb\config\config;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\auth\provider\oauth\token_storage;
use phpbb\db\driver\driver_interface;
use phpbb\request\request_interface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


// https://area51.phpbb.com/docs/dev/3.2.x/extensions/tutorial_events.html
class main_listener implements EventSubscriberInterface {

    protected config $config;
    protected driver_interface $db;
	protected request_interface $request;
	protected user $user;
    protected auth $auth;
    protected string $oauth_token_table;
	protected string $oauth_state_table;


    public function __construct(
        config $config,
        driver_interface $db,
		request_interface $request,
		user $user,
        auth $auth,
        string $oauth_token_table,
        string $oauth_state_table
    ) {
        $this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->user = $user;
        $this->auth = $auth;
		$this->oauth_token_table = $oauth_token_table;
		$this->oauth_state_table = $oauth_state_table;
    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents() {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.user_setup_after' => 'user_setup_after',
            'core.login_box_modify_template_data' => 'login_box_modify_template_data',
            'core.session_kill_after' => 'session_kill_after'
        ];
    }

    /**
     * Load the language file
     *     fjf2002/keycloak/language/.../common.php
     *
     * @param \phpbb\event\data $event The event object
     */
    public function load_language_on_setup($event) {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'fjf2002/keycloak',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /*
     * Called from ./adm/index.php line 31:
     * $user->setup('acp/common');
     */
    public function user_setup_after($event) {
        /*
         * Do not show login form.
         * Instead redirect to keycloak login:
         */
        if ($this->user->data['user_id'] == ANONYMOUS) {
            $this->request->overwrite('oauth_service', 'keycloak');
            $this->auth->login("", "");
        }

        /*
         * phpBB has a re-authentification when accessing the admin panel.
         * Since we are using OAuth, the password check will fail.
         * Mitigate that: Bypass Re-Authentification:
         */
        if ($this->auth->acl_get('a_')) {
            $this->user->data['session_admin'] = "1";
        }
    }

    /**
     * When a protected forum page is requested but the session has expired (or is non-existent),
     * the login form would get rendered.
     * This method prevents that.
     * (The event core.login_box_before wouldn't work since it also gets called on the regular keycloak login procedure.)
     */
    public function login_box_modify_template_data($event) {
        // see /srv/www/vhosts/forum/htdocs/phpbb/auth/provider/oauth/oauth.php, login method:
        $this->request->overwrite('oauth_service', 'keycloak');
        $this->auth->login("", "");
    }

    /**
     * Logout event
     */
    public function session_kill_after($event) {
        /*
         * phpbb/auth/provider/oauth/oauth.php does not offer a keycloak logout.
         * Reimplemented here:
         */
        $tokenStorage = new token_storage($this->db, $this->user, 'phpbb_oauth_tokens', 'phpbb_oauth_states');

        $stdOAuth2Token = $tokenStorage->retrieveAccessToken('auth.provider.oauth.service.keycloak');

        $idTokenBase64 = $stdOAuth2Token->getExtraParams()['id_token'];

        $baseApiUri = $this->config['auth_oauth_keycloak_url'];

        redirect("$baseApiUri/logout?id_token_hint=$idTokenBase64&post_logout_redirect_uri=" . urlencode("/"), false, true);
    }
}
