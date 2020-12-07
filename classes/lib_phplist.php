<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_eduvidual
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
**/

defined('MOODLE_INTERNAL') || die;


class local_eduvidual_lib_phplist {
    static $externaldb;
    static $externalconnected = false; // boolean if connection is ok.
    static $debug = false;

    /**
     * Called when a user changes his role (e.g. not manager anymore).
     * Loads lists that should be removed and removes them.
     * @param userid Userid of moodle user
    **/
    public static function check_user_role($userid){
        global $DB;
        $roles = array('Manager', 'Parent', 'Student', 'Teacher');
        $user = $DB->get_record('user', array('id' => $userid), '*', IGNORE_MISSING);
        $ignores = explode(',', get_config('local_eduvidual', 'phplist_ignore_patterns'));
        foreach ($ignores AS $ignorepattern) {
            if (!empty($ignorepattern) && strpos($user->email, $ignorepattern) !== 0) return;
        }
        if (self::$debug) echo "Checking roles of " . $userid . " / " . fullname($user) . "<br />\n";
        if (!empty($user->id)) {
            foreach($roles AS $role) {
                $listids = explode(',', get_config('local_eduvidual', 'phplist_' . strtolower($role) . '_lists'));
                if (count($listids) > 0 && !empty($listids[0])) {
                    self::get_phplistuserid($user);
                    if (!empty($user->phplistuserid)) {
                        $hasroles = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $user->id, 'role' => $role));
                        foreach($hasroles AS $hasrole) { }
                        foreach($listids AS $listid) {
                            if (!$ignore && !empty($hasrole->role) && $hasrole->role == $role) {
                                self::add_user_to_list($user, $listid);
                            } else {
                                self::remove_user_from_list($user, $listid);
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * Calls users authtype for all users with mnethostid > 1 or auth oauth2
     * Should be called on login for every user.
     * @param users (optional) array containing users - if empty will load all.
     */
    public static function check_users_authtype($users = array()){
        global $DB;
        $listids_mnet = explode(',', get_config('local_eduvidual', 'phplist_mnet_lists'));
        $listids_google = explode(',', get_config('local_eduvidual', 'phplist_oauth_google_lists'));
        $listids_microsoft = explode(',', get_config('local_eduvidual', 'phplist_oauth_microsoft_lists'));
        $listids = array_merge($listids_mnet, $listids_google, $listids_microsoft);
        $added = array();

        $issuers_google = explode(',', get_config('local_eduvidual', 'phplist_oauth_issuers_google'));
        $issuers_microsoft = explode(',', get_config('local_eduvidual', 'phplist_oauth_issuers_microsoft'));
        $issuers = array_merge($issuers_google, $issuers_microsoft);

        if (count($users) == 0) {
            $users = $DB->get_records_sql("SELECT * FROM {user} WHERE mnethostid>1 OR auth='oauth2'");
        }
        // Load oauth_issuer.
        foreach ($users AS &$user) {
            if ($user->auth == 'oauth2') {
                $oauth = $DB->get_record('auth_oauth2_linked_login', array('userid' => $user->id));
                $user->issuerid = $oauth->issuerid;
            }
        }
        $ignores = explode(',', get_config('local_eduvidual', 'phplist_ignore_patterns'));
        foreach ($users AS $user) {
            $ignore = false;
            foreach ($ignores AS $ignorepattern) {
                if (!empty($ignorepattern) && strpos($user->email, $ignorepattern) !== false) $ignore = true;
            }
            if ($ignore) continue;
            self::get_phplistuserid($user);
            if (!empty($user->phplistuserid)) {
                foreach ($listids AS $listid) {
                    if (in_array($listid, $listids_mnet) && $user->mnethostid > 1
                        || !empty($user->issuerid) && in_array($listid, $listids_google) && in_array($user->issuerid, $issuers_google)
                        || !empty($user->issuerid) && in_array($listid, $listids_microsoft) && @in_array($user->issuerid, $issuers_microsoft)
                        ) {
                        if (!isset($added[$listid])) $added[$listid] = 0;
                        $added[$listid] += self::add_user_to_list($user, $listid);
                    } else {
                        // We do not remove from Google-List as we will force users to move to manual login.
                        if (!in_array($listid, $listids_google)) {
                            self::remove_user_from_list($user, $listid);
                        }
                    }
                }
            }
        }

        foreach ($listids AS $listid) {
            if (empty($listid)) continue;
            $listtype = 'Unknown';
            if (in_array($listid, $listids_mnet)) $listtype = 'MNet';
            if (in_array($listid, $listids_google)) $listtype = 'Google';
            if (in_array($listid, $listids_microsoft)) $listtype = 'Microsoft';
            echo "Added " . (!empty($added[$listid]) ? $added[$listid] : "n/a") . " users to list #$listid for $listtype";
        }
    }
    /**
     * Does a full one-way sync.
     * @return array that holds messages for output by template alert.
    **/
    public static function sync() {
        self::connect_externaldb();

        // If db connection was unsuccessful - return.
        if (!self::$externalconnected) return;

        $lists = array('manager', 'parent', 'teacher', 'student');
        $ignores = explode(',', get_config('local_eduvidual', 'phplist_ignore_patterns'));
        foreach($lists AS $list) {
            $added = 0;
            $listids = explode(',', get_config('local_eduvidual', 'phplist_' . $list . '_lists'));
            if (count($listids) > 0 && !empty($listids[0])) {
                $users = self::get_users($list);
                echo "Syncing list " . ucfirst($list) . " with " . count($users) . " users\n";
                // Ensure the user is in the database, set the field "phplistuserid" in the user-object.
                foreach($users AS &$user) {
                    self::get_phplistuserid($user);
                    $ignore = false;
                    foreach ($ignores AS $ignorepattern) {
                        if (strpos($user->email, $ignorepattern) !== false) $ignore = true;
                    }
                    if (!empty($user->phplistuserid)) {
                        foreach($listids AS $listid) {
                            if ($ignore) {
                                // Ensure that the user isremoved from every list as we ignore him.
                                $added += self::remove_user_from_list($user, $listid);
                            } else {
                                // Ensure that the user is enrolled in every list.
                                $added += self::add_user_to_list($user, $listid);
                            }
                        }
                    }
                }
                echo "Added $added users to lists for $list";
            }
        }
        self::check_users_authtype();
        return;
    }


    /**
     * Connects the external database.
     * @return array that holds messages for output by template alert.
    **/
    private static function connect_externaldb() {
        if (!self::$externalconnected) {
            // Ensure the db connection to the external db works
            $dbhost = get_config('local_eduvidual', 'phplist_dbhost');
            $dbname = get_config('local_eduvidual', 'phplist_dbname');
            $dbpass = get_config('local_eduvidual', 'phplist_dbpass');
            $dbuser = get_config('local_eduvidual', 'phplist_dbuser');
            try {
                self::$externaldb = new mysqli($dbhost, $dbuser, $dbpass);

                // Check connection
                if (isset(self::$externaldb->connect_error)) {
                    echo "Connection failed: " . self::$externaldb->connect_error;
                } else {
                    self::$externaldb->select_db($dbname);
                    self::$externalconnected = true;
                }
            } catch(Exception $e) {
                echo "Connection failed: " . self::$externaldb->connect_error;
            }

        }
    }
    /**
     * Fetches a list of users based on a target group.
     * @param targetgroup students, teachers, managers, all
     * @return array containing users
    **/
    private static function get_users($targetgroup) {
        global $DB;
        $users = array();
        switch($targetgroup) {
            case 'manager':
                $users = $DB->get_records_sql('SELECT u.* FROM {user} u, {local_eduvidual_orgid_userid} ou WHERE ou.userid=u.id AND ou.role LIKE ? GROUP BY u.id', array('Manager'));
            break;
            case 'parent':
                $users = $DB->get_records_sql('SELECT u.* FROM {user} u, {local_eduvidual_orgid_userid} ou WHERE ou.userid=u.id AND ou.role LIKE ? GROUP BY u.id', array('Parent'));
            break;
            case 'student':
                $users = $DB->get_records_sql('SELECT u.* FROM {user} u, {local_eduvidual_orgid_userid} ou WHERE ou.userid=u.id AND ou.role LIKE ? GROUP BY u.id', array('Student'));
            break;
            case 'teacher':
                $users = $DB->get_records_sql('SELECT u.* FROM {user} u, {local_eduvidual_orgid_userid} ou WHERE ou.userid=u.id AND ou.role LIKE ? GROUP BY u.id', array('Teacher'));
            break;
        }
        return $users;
    }
    /**
     * Retrieve userid from externaldb or insert user and get id.
     * @param user Moodle user-object
     * @return user containing phplistuserid
    **/
    private static function get_phplistuserid(&$user){
        if (self::$debug) echo "get_phplistuserid from " . $user->id . "<br />\n";
        self::connect_externaldb();
        $stmt = self::$externaldb->prepare('SELECT id FROM phplist_user_user WHERE email LIKE ?');
        $stmt->bind_param('s', $user->email);
        $stmt->execute();
        $stmt->bind_result($user->phplistuserid);
        $stmt->fetch();
        $stmt->close();
        if (empty($user->phplistuserid)) {
            $uuid = md5($user->email);
            $uniqid = md5($uuid);
            $entered = date('Y-m-d H:i:s');
            $stmt = self::$externaldb->prepare('INSERT INTO phplist_user_user (`email`, `confirmed`, `blacklisted`, `entered`, `uniqid`, `uuid`, `htmlemail`, `disabled`) VALUES (?, 1, 0, ?, ?, ?, 1, 0)');
            $stmt->bind_param('ssss', $user->email, $entered, $uniqid, $uuid);
            $stmt->execute();
            $user->phplistuserid = $stmt->insert_id;
        }
        if (!empty($user->phplistuserid)) {
            $stmt = self::$externaldb->prepare('INSERT INTO phplist_user_user_attribute (userid, attributeid, value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=?');
            $stmt->bind_param('iiss', $user->phplistuserid, $attribute, $value, $value);
            $attribute = 1; $value = $user->firstname;
            $stmt->execute();
            $attribute = 2; $value = $user->lastname;
            $stmt->execute();
            $stmt->close();
        }
        if (self::$debug) echo "=> is " . $user->phplistuserid . "<br />\n";
    }
    /**
     * Adds a user to a specific list.
     * @param user Moodle user-object
     * @param listid listid from phplist
    **/
    private static function add_user_to_list($user, $listid){
        if (self::$debug) echo "Add user " . $user->id . " to list " . $listid . "<br />\n";
        if (empty($user->phplistuserid)) return;
        self::connect_externaldb();
        $entered = date('Y-m-d H:i:s');
        $stmt = self::$externaldb->prepare('SELECT userid FROM phplist_listuser WHERE userid=? AND listid=?');
        $stmt->bind_param('ii', $user->phplistuserid, $listid);
        $stmt->bind_result($existsuserid);
        $stmt->execute(); $stmt->close();
        if (!empty($existsuserid) && $existsuserid == $user->phplistuserid) {
            return 0;
        } else {
            $stmt = self::$externaldb->prepare('INSERT IGNORE INTO phplist_listuser (userid, listid, entered) VALUES (?,?,?)');
            $stmt->bind_param('iis', $user->phplistuserid, $listid, $entered);
            $stmt->execute();
            $stmt->close();
            return 1;
        }
    }
    /**
     * Removes a user to a specific list.
     * @param user Moodle user-object
     * @param listid listid from phplist
    **/
    private static function remove_user_from_list($user, $listid){
        if (self::$debug) echo "Remove user " . $user->id . " from list " . $listid . "<br />\n";
        if (empty($user->phplistuserid)) return;
        self::connect_externaldb();
        $entered = date('Y-m-d H:i:s');
        $stmt = self::$externaldb->prepare('DELETE FROM phplist_listuser WHERE userid=? AND listid=?');
        $stmt->bind_param('ii', $user->phplistuserid, $listid);
        $stmt->execute();
        $stmt->close();
    }
}
