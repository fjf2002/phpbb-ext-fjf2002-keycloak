<?php
namespace fjf2002\keycloak\core;

use phpbb\db\driver\driver_interface;



class UsersAndGroupsInterface {

    /** @var \phpbb\config\config */
    protected $config;

	/** @var driver_interface DBAL driver instance */
	protected $db;

	/** @var string OAuth table: token storage */
	protected $oauth_account_table;

    protected string $phpbbRootPath;
    protected string $phpExt;

    /** @var array of phpbb group names, indexed by group id */
    protected $phpbbGroupsById;

    /** @var array of phpbb group ids, indexed by group name */
    protected $phpbbGroupsByName;


    function __construct(\phpbb\config\config $config,
        driver_interface $db,
        string $oauth_account_table,
        string $phpbbRootPath,
        string $phpExt
    ) {
        $this->config = $config;
        $this->db = $db;
        $this->oauth_account_table	= $oauth_account_table;
        $this->phpbbRootPath = $phpbbRootPath;
        $this->phpExt = $phpExt;
    }

    /**
     * This function fetches all existing groups from phpBB.
     *
     * @return array   An array with fetched groups, key: group_id, value: group_name
     */
    private function lazyGetPhpbbGroupsById() {
        $this->phpbbGroupsById = $this->phpbbGroupsById ?? (function() {
            $sql = 'SELECT group_id, group_name FROM ' . GROUPS_TABLE;

            $result = $this->db->sql_query($sql);
            $groups = $this->db->sql_fetchrowset($result) ?: [];
            $this->db->sql_freeresult($result);

            $kvArray = [];
            foreach($groups as $group) {
                $kvArray[$group['group_id']] = $group['group_name'];
            }

            return $kvArray;
        })();

        return $this->phpbbGroupsById;
    }

    private function lazyGetPhpbbGroupsByName() {
        $this->phpbbGroupsByName = $this->phpbbGroupsByName ?? array_flip($this->lazyGetPhpbbGroupsById());
        return $this->phpbbGroupsByName;
    }

    /**
     * @return array|false
     */
    private function getUser(string $username) {
        // from ./phpbb/auth/provider/db.php::login

        $username_clean = utf8_clean_string($username);

        $sql = 'SELECT user_id, username, user_password, user_passchg, user_email, user_type
            FROM ' . USERS_TABLE . "
            WHERE username_clean = '" . $this->db->sql_escape($username_clean) . "'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        return $row;
    }

    /**
     * This function generates an array which can be passed to the user_add
     * function in order to create a user
     *
     * @param    string $username The username of the new user.
     * @param    string $password The password of the new user.
     * @return    array                Contains data that can be passed directly to
     *                                the user_add function.
     */
    private function createUser(string $username, string $email) {
        /*
         * Generate user account data.
         * No password is set here.
         * phpBB prevents username/password login with emtpy password.
         */
        $userRow = [
            'username' => $username,
            'user_email' => $email,
            'group_id' => $this->lazyGetPhpbbGroupsByName()['REGISTERED'],
            'user_type' => USER_NORMAL,
            'user_ip' => $GLOBALS['user']->ip,
            'user_new' => $this->config['new_member_post_limit'] ? 1 : 0,
        ];

        // from ./phpbb/auth/auth.php::login
        if (!function_exists('user_add')) {
            include($this->phpbbRootPath . 'includes/functions_user.' . $this->phpExt);
        }

        if (user_add($userRow) === false) {
            throw new \Exception("Error creating user $username.");
        }
    }

    private function linkOAuthAccount(int $phpbbUserId, string $provider, string $keycloakUsername) {
        // from ./phpbb/auth/provider/oauth/oauth.php::link_account_perform_link
        $data = [
            'user_id' => $phpbbUserId,
            'provider' => $provider,
            'oauth_provider_id' => $keycloakUsername
        ];

        // Link account / update account link
        $sql = 'INSERT INTO ' . $this->oauth_account_table . ' ' . $this->db->sql_build_array('INSERT', $data)
            . ' ON DUPLICATE KEY UPDATE oauth_provider_id = \'' . $this->db->sql_escape($keycloakUsername) . '\'';
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    private function updateGroupMemberships(int $phpbbUserId, array $keycloakGroupNames) {
        /* All the following group arrays have the group name as their value;
         * exception: $allPhpbbGroupsByName
         */

        /*
         * Wenn alle Gruppen geholt werden, dann wird der User aus den special groups REGISTERED und NEWLY_REGISTERED
         * entfernt; dies scheint auch das vergangene Standardverhalten gewesen zu sein.
         */
        $allPhpbbGroupsById = $this->lazyGetPhpbbGroupsById();
        $allPhpbbGroupsByName = $this->lazyGetPhpbbGroupsByName();

        $currentPhpbbGroupMemberships = array_map(
            function($item) use ($allPhpbbGroupsById) { return $allPhpbbGroupsById[$item['group_id']]; },
            group_memberships(false, $phpbbUserId, false) ?: []
        );


        // Step 1: Create additional group memberships in phpbb
        $newPhpbbGroups = array_diff(
            array_intersect($keycloakGroupNames, $allPhpbbGroupsById), // only keycloak groups that exist in phpbb
            $currentPhpbbGroupMemberships
        );
        foreach($newPhpbbGroups as $groupName) {
            // if groupname is NOT passed here, at first login, page gets loaded in english :-(
            group_user_add($allPhpbbGroupsByName[$groupName], $phpbbUserId, false, $groupName);
        }


        // Step 2: Remove leftover group memberships in phpbb
        $removePhpbbGroups = array_diff(
            $currentPhpbbGroupMemberships,
            $keycloakGroupNames
        );
        foreach($removePhpbbGroups as $groupName) {
            // if groupname is NOT passed here, at first login, page gets loaded in english :-(
            group_user_del($allPhpbbGroupsByName[$groupName], $phpbbUserId, false, $groupName);
        }
    }

    public function getOrCreateUser(string $provider, string $username, string $email, array $groups) {
        // step 1: get or create phpbb_users row
        $userRow = $this->getUser($username);

        if (!$userRow) {
			$this->createUser($username, $email);
            $userRow = $this->getUser($username);
		}

        // step 2: oauth account link
        $this->linkOAuthAccount($userRow['user_id'], $provider, $username);


        // step 3: update group memberships
        $this->updateGroupMemberships($userRow['user_id'], $groups);

        return $userRow;
    }
}
