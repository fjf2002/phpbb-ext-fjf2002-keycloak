<?php


/**
 * DO NOT CHANGE
 */
if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, [
    'ACP_KEYCLOAK_TITLE'                   => 'Keycloak',
    'ACP_KEYCLOAK'                         => 'Einstellungen',
    'ACP_KEYCLOAK_URL'                     => 'Keycloak OpenID Base URL',
    'ACP_KEYCLOAK_URL_EXPLAIN'             => 'Endet normalerweise mit /realms/master/protocol/openid-connect .',
    'ACP_KEYCLOAK_SETTING_SAVED'           => 'Einstellungen wurden gespeichert!',
    'ACP_KEYCLOAK_GENERAL_TAB_ADVICE'      => 'Bitte gehen sie zu Allgemeines->Client-Kommunikation->Authentifizierung, wählen Sie authentication method "Oauth" aus und setzen Keycloak Client Key und Secret.',
    'AUTH_PROVIDER_OAUTH_SERVICE_KEYCLOAK' => 'Keycloak'
]);
