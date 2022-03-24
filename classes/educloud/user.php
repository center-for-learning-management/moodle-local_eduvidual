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

namespace local_eduvidual\educloud;

defined('MOODLE_INTERNAL') || die;

class user {
    /**
     * Create an ad hoc task synchronize a user account.
     * @param user user object or userid
     */
    public static function action($user = "") {
        $cfg = \local_eduvidual\educloud\lib::api_config(true);
        if (empty($cfg->apipath)) {
            // not using educloud on this Moodle site.
            return;
        }
        global $USER;
        if (empty($user)) {
            $user = $USER->id;
        } else if (is_object($user)) {
            $user = $user->id;
        }
        $task = new \local_eduvidual\educloud\task();
        $task->set_custom_data([
            'userid' => $user,
        ]);
        \core\task\manager::queue_adhoc_task($task, true);
    }
    /**
     * Create a user in univention portal and store univentionidentifier.
     * @param userid int.
     */
    public static function create($userid) {
        $properties = self::ucs_properties($userid);
        $_properties = json_encode($properties);

        $response = \local_eduvidual\educloud\lib::curl(
            "/ucsschool/kelvin/v1/users/",
            [],
            $_properties,
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
                'Content-Type' => 'application/json',
            ],
            'POST',
            false
        );
        $response = json_decode($response);

        if (!empty($response->name)) {
            mtrace("Create successful");
            self::ucs_identifier($userid, $response->name);
        } else {
            mtrace("Create failed, output of curl was " . print_r($response, 1));
            mtrace("Properties of user were: " . $_properties);
            throw new \moodle_exception('educloud:exception:userupdatefailed', 'local_eduvidual', '', [ 'userid' => $userid ]);
        }
    }
    /**
     * Deletes a particular user.
     * @param userorid userid or user object.
     */
    public static function delete($userorid) {
        if (!is_object($userorid)) {
            $user = \core_user::get_user($userorid);
        } else {
            $user = $userorid;
        }
        $ucsidentifier = self::ucs_identifier($user->id);
        mtrace("UCS-Identifier $ucsidentifier");
        $response = json_decode(\local_eduvidual\educloud\lib::curl(
            "/ucsschool/kelvin/v1/users/$ucsidentifier",
            [],
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
            ],
            'DELETE',
            false
        ));
        self::ucs_identifier($user->id, '', true);
        if (!empty($response) && substr($response->detail, 0, 9) != 'No object') {
            mtrace("Delete failed, output of curl was " . print_r($response, 1));
            throw new \moodle_exception('educloud:exception:userdeletefailed', 'local_eduvidual', '', [ 'userid' => $user->id ]);
        } else {
            mtrace("Deleted or user did not exist!");
        }
    }
    /**
     * Asks the univention portal for a particular user.
     * @param userid the Moodle userid.
     */
    public static function get($userid) {
        $ucsidentifier = self::ucs_identifier($userid);
        $response = \local_eduvidual\educloud\lib::curl(
            "/ucsschool/kelvin/v1/users/$ucsidentifier",
            [],
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
            ]
        );
        $response = json_decode($response);
        if (!empty($response->name)) {
            // We found a user link that was unkown. Store it.
            self::ucs_identifier($userid, $response->name);
            return $response;
        } else {
            return (object) [];
        }
    }
    /**
     * Get all orgs for a user, that use educloud.
     * @param userid of that user.
     * @return indexed array.
     */
    public static function get_orgs($userid) {
        global $DB;
        $sql = "SELECT ee.orgid,ee.ucsurl,ee.ucsname,ou.role
                    FROM {local_eduvidual_educloud} ee,
                         {local_eduvidual_orgid_userid} ou
                    WHERE ee.orgid = ou.orgid
                        AND ee.enabled > 0
                        AND ou.userid = ?";
        return $DB->get_records_sql($sql, [ $userid ]);
    }
    /**
     * Get the record_uid for univention (=username).
     * @param userid.
     * @return String with identifier.
     */
    private static function record_uid($userid) {
        $user = \core_user::get_user($userid);
        return $user->username;
    }
    /**
     * Get or set the ucs identifier of a userid.
     * @param userid
     * @param setto (optional) set new identifier
     * @param delete (optional) delete identifier
     */
    public static function ucs_identifier($userid, $setto = '', $delete = false) {
        if ($delete) {
            \unset_user_preference('educloud_identifier', $userid);
        } else if (!empty($setto)) {
            \set_user_preference('educloud_identifier', $setto, $userid);
        } else {
            $mapped_identifier = \get_user_preferences('educloud_identifier', $userid);
            if (empty($mapped_identifier)) {
                $cfg = \local_eduvidual\educloud\lib::api_config();
                return "{$cfg->sourceid}_$userid";
            } else {
                return $mapped_identifier;
            }
        }
    }
    /**
     * Transform Moodle-user profile to an univention user profile.
     * @param userorid object or userid.
     * @return object for univention profile.
     */
    public static function ucs_properties($userorid) {
        global $CFG, $DB;
        $cfg = \local_eduvidual\educloud\lib::api_config();
        if (!is_object($userorid)) {
            $user = \core_user::get_user($userorid);
        } else {
            $user = $userorid;
        }

        $ucs_roles = self::ucs_roles();

        $orgs = self::get_orgs($user->id);

        $roles = [];
        $schools = [];
        $ucsschool_roles = [];

        foreach ($orgs as $org) {
            if (empty($org->ucsurl)) {
                $org->ucsurl = \local_eduvidual\educloud\school::create($org->orgid);
            }
            if (!empty($org->ucsurl)) {
                $schools[] = $org->ucsurl;

                $tmp = in_array($org->role, [ 'Manager', 'Teacher']) ? 'teacher' : 'student';
                $roleurl = $ucs_roles[$tmp];
                if (!in_array($roleurl, $roles)) {
                    $roles[] = $roleurl;
                }

                $ucsschool_roles[] = "$tmp:school:{$org->ucsname}";
            }
        }

        $properties = (object) [
            "name"              => self::ucs_identifier($user->id),
            "schools"           => $schools,
            "firstname"         => $user->firstname,
            "lastname"          => $user->lastname,
            "disabled"          => false,
            "expiration_date"   => "2099-12-31",
            "record_uid"        => self::record_uid($user->id),
            "roles"             => $roles,
            //"school_classes"  => {},
            "source_uid"        => $cfg->sourceid,
            "ucsschool_roles"   => $ucsschool_roles,
            "udm_properties"    => (object) [
                "e-mail" => [$user->email],
            ],
            //"password"        => "", // Not set.
        ];
        return $properties;
    }

    /**
     * Get available roles of the UCS System.
     * Required roles are student and teacher.
     * @return array with all roles.
     */
    public static function ucs_roles() {
        $roles = (array) json_decode(\local_eduvidual\locallib::cache('application', 'educloud_roles'));
        if (empty($roles)) {
            $response = \local_eduvidual\educloud\lib::curl(
                '/ucsschool/kelvin/v1/roles/',
                [],
                [],
                [
                    'Accept' => 'application/json',
                    'Authorization' => \local_eduvidual\educloud\lib::token(),
                ],
            );

            $response = json_decode($response);
            $roles = [];
            if (!empty($response) && count($response) > 0) {
                foreach ($response as $role) {
                    $roles[$role->name] = $role->url;
                }
                \local_eduvidual\locallib::cache('application', 'educloud_roles', json_encode($roles));
            }
        }
        return $roles;
    }
    /**
     * Update a user in univention portal and store univentionidentifier.
     * @param userorid User object or userid.
     */
    public static function update($userorid) {
        if (!is_object($userorid)) {
            $user = \core_user::get_user($userorid);
        } else {
            $user = $userorid;
        }
        $ucsidentifier = self::ucs_identifier($user->id);
        mtrace("UCS-Identifier $ucsidentifier");

        $properties = self::ucs_properties($user);
        $_properties = json_encode($properties);

        $response = \local_eduvidual\educloud\lib::curl(
            "/ucsschool/kelvin/v1/users/$ucsidentifier",
            [],
            $_properties,
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
                'Content-Type' => 'application/json',
            ],
            'PATCH',
            false
        );
        $response = json_decode($response);

        if (!empty($response->name)) {
            mtrace("Update successful");
            self::ucs_identifier($user->id, $response->name);
        } else {
            mtrace("Update failed, output of curl was " . print_r($response, 1));
            throw new \moodle_exception('educloud:exception:userupdatefailed', 'local_eduvidual', '', [ 'userid' => $user->id ]);
        }
    }
}
