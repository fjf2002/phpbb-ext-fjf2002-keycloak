<?php
namespace fjf2002\keycloak\acp;

// https://area51.phpbb.com/docs/dev/3.2.x/extensions/tutorial_modules.html#module-class
class main_module {

    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode) {
        global $language, $template, $request, $config;

        // FJF: notwendig hier für das Formular selbst
        $language->add_lang('common', 'fjf2002/keycloak');

        $this->tpl_name = 'acp_keycloak_body';
        $this->page_title = $language->lang('ACP_KEYCLOAK_TITLE');

        add_form_key('fjf2002_keycloak_settings');

        if ($request->is_set_post('submit')) {
            if (!check_form_key('fjf2002_keycloak_settings')) {
                 trigger_error('FORM_INVALID');
            }

            $config->set('auth_oauth_keycloak_url', $request->variable('auth_oauth_keycloak_url', 'https://your-keycloak/realms/master/protocol/openid-connect'));
            trigger_error($language->lang('ACP_KEYCLOAK_SETTING_SAVED') . adm_back_link($this->u_action));
        }

        $template->assign_vars([
            'AUTH_OAUTH_KEYCLOAK_URL' => $config['auth_oauth_keycloak_url'],
            'U_ACTION'          => $this->u_action,
        ]);
    }
}
