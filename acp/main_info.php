<?php
namespace fjf2002\keycloak\acp;

// https://area51.phpbb.com/docs/dev/3.2.x/extensions/tutorial_modules.html#module-info
class main_info {

    public function module() {
        return [
            'filename'  => '\fjf2002\keycloak\acp\main_module',
            'title'     => 'ACP_KEYCLOAK_TITLE',
            'modes'    => [
                'settings'  => [
                    'title' => 'ACP_KEYCLOAK',
                    'auth'  => 'ext_fjf2002/keycloak && acl_a_board',
                    'cat'   => ['ACP_KEYCLOAK_TITLE'],
                ],
            ],
        ];
    }
}
