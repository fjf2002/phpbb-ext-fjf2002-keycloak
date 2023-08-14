# phpBB Keycloak OAuth extension

This extension enables phpBB OpenID Connect authentification on your Keycloak server.

## Installation
* Copy these files to ext/fjf2002/keycloak
* Go to phpBB Admin Control Panel, tab "Customize", and enable this extension

## Configuration
* Go to PHPbb Admin Control Panel,
  * tab "Extensions", and enter your Keycloak base url,
  * tab "General" Client Communication->Authentication,
    * choose Keycloak as authentication method,
    * enter Keycloak Key and Secret.

## Important notes
This extension's feature set deviates from the phpBB built-in OAuth provider features such as Google, Twitter, Facebook in the following ways:
* When logging in for the first time via Keycloak,
  no dialog is represented to choose whether to create a new phpBB account or to link an existing one.
  Instead, users are immediately linked by their username (or accounts created in phpBB, if not yet present).
* phpBB groups are updated from the information given in the access token "groups" property.
* Access to the phpBB Admin Control panel does not trigger a username/password form any more.
  Instead, access is granted automatically, given admin rights.
  (This is because automatically created accounts do not have a password to authenticate here.)
* Also because of the latter, username/password authentication in general will not work for auto-generated accounts.
* Hint: You can trigger keycloak login by directly browsing to `./ucp.php?mode=login&login=external&oauth_service=keycloak` .

Thanks to
 * The phpBB development website at https://area51.phpbb.com/docs/dev/3.2.x/extensions/
 * The project https://github.com/ect-ua/phpbb-ext-keycloak as a starting point
