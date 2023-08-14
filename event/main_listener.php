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
            'core.user_setup_after' => 'user_setup_after'
        ];
    }

    /**
     * Load the Acme Demo language file
     *     fjf2002/keycloak/language/en/keycloak.php
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
     * phpBB has a re-authentification then accessing the admin panel.
     * Since we are using OAuth, the password check will fail.
     * Mitigate that: Bypass Re-Authentification:
     *
     * Called from ./adm/index.php line 31:
     * $user->setup('acp/common');
     */
    public function user_setup_after($event) {
        global $auth;
        global $user;

        if ($auth->acl_get('a_')) {
            $user->data['session_admin'] = "1";
        }
    }
}
