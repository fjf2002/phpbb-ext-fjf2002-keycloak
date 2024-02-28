<?php
namespace fjf2002\keycloak\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


// https://area51.phpbb.com/docs/dev/3.2.x/extensions/tutorial_events.html
class main_listener implements EventSubscriberInterface {
    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents() {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.user_setup_after' => 'user_setup_after',
            'core.login_box_modify_template_data' => 'login_box_modify_template_data'
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
        global $auth, $user, $request;

        /*
         * Do not show login form.
         * Instead redirect to keycloak login:
         */
        if ($user->data['user_id'] == ANONYMOUS) {
            $request->overwrite('oauth_service', 'keycloak');
            $auth->login("", "");
        }

        /*
         * phpBB has a re-authentification when accessing the admin panel.
         * Since we are using OAuth, the password check will fail.
         * Mitigate that: Bypass Re-Authentification:
         */
        if ($auth->acl_get('a_')) {
            $user->data['session_admin'] = "1";
        }
    }

    /**
     * When a protected forum page is requested but the session has expired (or is non-existent),
     * the login form would get rendered.
     * This method prevents that.
     * (The event core.login_box_before wouldn't work since it also gets called on the regular keycloak login procedure.)
     */
    public function login_box_modify_template_data($event) {
        global $request, $auth;

        // see /srv/www/vhosts/forum/htdocs/phpbb/auth/provider/oauth/oauth.php, login method:
        $request->overwrite('oauth_service', 'keycloak');
        $auth->login("", "");
    }
}
