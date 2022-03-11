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
 * @copyright  2022 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_educloud {
    /**
     * Create an ad hoc task synchronize a user account.
     * @param user user object or userid
     */
    public static function action($user = "") {
        global $USER;
        if (empty($user)) {
            $user = $USER->id;
        } else if (is_object($user)) {
            $user = $user->id;
        }
        $task = new \local_eduvidual\task\educloud();
        $task->set_custom_data([
            'userid' => $user,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
    }
    /**
     * Add required settings to admin settings page.
     * @param settings the node settings are attached to.
    **/
    public static function admin_settings_page($settings) {
        global $ADMIN;
        if (empty($ADMIN) || !$ADMIN->fulltree) {
            return;
        }

        $heading = get_string('educloud:settings', 'local_eduvidual');
        $text    = get_string('educloud:settings:description', 'local_eduvidual');
        $settings->add(
            new \admin_setting_heading(
                'local_eduvidual_educloud',
                '',
                "<h3>$heading</h3><p>$text</p>"
            )
        );

        $settings->add(
            new \admin_setting_configtext(
                'local_eduvidual/educloud_apipath',
                get_string('educloud:settings:apipath', 'local_eduvidual'),
                '',
                'https://<urlofunivention>/univention',
                PARAM_URL
            )
        );

        $settings->add(
            new \admin_setting_configtext(
                'local_eduvidual/educloud_apiuser',
                get_string('educloud:settings:apiuser', 'local_eduvidual'),
                '',
                '',
                PARAM_TEXT
            )
        );
        $settings->add(
            new \admin_setting_configpasswordunmask(
                'local_eduvidual/educloud_apipass',
                get_string('educloud:settings:apipass', 'local_eduvidual'),
                '',
                '',
                PARAM_TEXT
            )
        );
        $settings->add(
            new \admin_setting_configtext(
                'local_eduvidual/educloud_apildap',
                get_string('educloud:settings:apildap', 'local_eduvidual'),
                '',
                'cn=users,dc=educloud-austria,dc=at',
                PARAM_TEXT
            )
        );
    }
    /**
     * Get the config of this plugin and check all values.
     * @return object with configuration for educloud api.
     */
    public static function api_config() {
        $cfg = (object) [
            'apildap' => \get_config('local_eduvidual', 'educloud_apildap'),
            'apipass' => \get_config('local_eduvidual', 'educloud_apipass'),
            'apipath' => \get_config('local_eduvidual', 'educloud_apipath'),
            'apiuser' => \get_config('local_eduvidual', 'educloud_apiuser'),
        ];

        if (empty($cfg->apildap) || empty($cfg->apipass) || empty($cfg->apipath) || empty($cfg->apiuser)) {
            throw new \moodle_exception('educloud:exception:incompletesitesettings', 'local_eduvidual');
        }
        return $cfg;
    }
    /**
     * Create a user in univention portal and store univentionidentifier.
     * @param userid int.
     */
    public static function api_create_user($userid) {
        $cfg = self::api_config();
        $jsono = json_encode((object) [
            'position' => $cfg->apildap,
            'objectType' => 'users/user',
            'options' => (object) [
                'pki' => false,
            ],
            'policies' => (object) [
                'policies/pwhistory' => [],
                'policies/umc' => [],
                'policies/desktop' => []
            ],
            'properties' => self::univention_user_from_moodle_user($userid),
        ], JSON_NUMERIC_CHECK);

        $response = self::api_curl("udm/users/user/add", [], [ 'data' => $jsono ], [], true);
    }
    /**
     * Asks the univention portal for a particular user.
     * @param univentionidentifier the identifier
     * @param field to search for, default is 'id'
     */
    public static function api_get_user($univentionidentifier, $field = 'id') {
        // @todo only search in our container!!!
        $response = self::api_curl("udm/users/user/", [ "query[$field]" => $univentionidentifier ]);
        $response = json_decode($response);
        if (!empty($response->_embedded->{'udm:object'})) {
            $objects = $response->_embedded->{'udm:object'};
            if (count($objects) == 1) {
                return $objects[0];
            } else {
                print_r($response->_embedded->{'udm:object'});
                throw new \moodle_exception(
                    'educloud:exception:multipleobjectsforidentifier',
                    'local_eduvidual',
                    '',
                    [ 'identifier' => $univentionidentifier ]
                );
            }
        }
        print_r($response);
        throw new \moodle_exception(
            'educloud:exception:invalidapiresponse',
            'local_eduvidual',
            '',
            [ 'identifier' => $univentionidentifier ]
        );
    }
    /**
     * Do a particular API-Call
     * @param module extension to the apipath like e.g. udm/users/user
     * @param get array with get-parameters
     * @param post array with post-parameters
     * @param headers array with headers.
     * @param debug show debugging information, default false.
     */
    public static function api_curl($module, $get = [], $post = [], $headers = [], $debug = false) {
        $cfg = self::api_config();

        $url = "$cfg->apipath/$module";
        $url = str_replace("https://", "https://$cfg->apiuser:$cfg->apipass@", $url);
        $url = str_replace("http://", "http://$cfg->apiuser:$cfg->apipass@", $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");

        if (!empty($get) && count($get) > 0) {
            $fields = array();
            foreach ($get as $key => $value) {
                $fields[] = urlencode($key) . '=' . urlencode($value);
            }
            $fields = implode('&', $fields);

            curl_setopt($ch, CURLOPT_URL, $url . '?' . $fields);
        }
        if (!empty($post) && count($post) > 0) {
            $fields = array();
            foreach ($post as $key => $value) {
                $fields[] = urlencode($key) . '=' . urlencode($value);
            }
            $fields = implode('&', $fields);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }
        if (!empty($headers) && count($headers) > 0) {
            $strheaders = array();
            foreach ($headers as $key => $value) {
                $strheaders[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $strheaders);
        }
        if (!empty($basicauth)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $basicauth);
        }

        if ($debug) {
            // enable debugging of curl request.
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        $output = curl_exec($ch);
        if ($debug) {
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            print_r($info);
            print_r($error);
            print_r($output);
        }
        curl_close($ch);
        return $output;
    }
    /**
     * Disables the feature for a particular org.
     * @param orgid
     * @return object record from database of local_eduvidual_educloud or false.
     */
    public static function org_disable($orgid) {
        global $DB;
        try {
            $transaction = $DB->start_delegated_transaction();
            self::org_sync($orgid);
            $record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $orgid]);
            if (!empty($record->id)) {
                $record = (object) [];
                $DB->delete_records('local_eduvidual_educloud', [ 'orgid' => $orgid ]);
            }
            $transaction->allow_commit();
            return $record;
        } catch(\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    /**
     * Enables the feature for a particular org.
     * @param orgid
     * @return object record from database of local_eduvidual_educloud or false.
     */
    public static function org_enable($orgid) {
        global $DB, $USER;
        try {
            $transaction = $DB->start_delegated_transaction();
            self::org_sync($orgid);
            $record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $orgid]);
            if (empty($record->id)) {
                $record = (object) [
                    'orgid' => $orgid,
                    'enabled' => time(),
                    'byuserid' => $USER->id,
                ];
                $record->id = $DB->insert_record('local_eduvidual_educloud', $record);
            }
            $transaction->allow_commit();
            return $record;
        } catch(\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    public static function org_sync($orgid) {
        global $DB, $OUTPUT;
        $members = $DB->get_records('local_eduvidual_orgid_userid', [ 'orgid' => $orgid ]);
        foreach ($members as $member) {
            self::action($member->userid);
        }
        return true;
    }
    /**
     * Transform Moodle-user profile to an univention user profile.
     * @param userorid object or userid.
     * @return object for univention profile.
     */
    public static function univention_user_from_moodle_user($userorid) {
        if (!is_object($userorid)) {
            $user = \core_user::get_user($userorid);
        } else {
            $user = $userorid;
        }
        return (object) [
            'phone' => $user->phone1,
            'groups' => [],
            'primaryGroup' => "cn=Domain Users,cn=groups,dc=mydomain,dc=intranet",
            'uidNumber' => $user->id,
            'disabled' => false,
            'unlock' => true,
            'locked' => false,
            'street' => $user->address,
            'postcode' => '',
            'city' => $user->city,
            'country' => $user->country,
            'e-mail' => $user->email,
            'userexpiry' => null,
            'preferredLanguage' => $user->lang,
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'displayname' => \fullname($user),
            'password' => null,
            'jpegPhoto' => null, // @todo can we transmit the user profile picture?
        ];
    }
}
