<?php
namespace fjf2002\keycloak\migrations;

// https://area51.phpbb.com/docs/dev/3.2.x/extensions/tutorial_modules.html#installing-control-panel-modules
class add_module extends \phpbb\db\migration\migration {

    /**
     * If our config variable already exists in the db
     * skip this migration.
     */
    public function effectively_installed() {
        return isset($this->config['auth_oauth_keycloak_url']);
    }

    /**
     * This migration depends on phpBB's v314 migration
     * already being installed.
     */
    static public function depends_on() {
        return [];
    }

    public function update_data() {
        global $language;
        $language->add_lang('common', 'fjf2002/keycloak');

        return [
            // Add the config variable we want to be able to set
            ['config.add', ['auth_oauth_keycloak_url', 'https://your-keycloak/realms/master/protocol/openid-connect']],

            // Add a parent module (ACP_KEYCLOAK_TITLE) to the Extensions tab (ACP_CAT_DOT_MODS)
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_KEYCLOAK_TITLE'
            ]],

            // Add our main_module to the parent module (ACP_KEYCLOAK_TITLE)
            ['module.add', [
                'acp',
                'ACP_KEYCLOAK_TITLE',
                [
                    'module_basename' => '\fjf2002\keycloak\acp\main_module',
                    'modes' => ['settings'],
                ],
            ]],
        ];
    }
}
