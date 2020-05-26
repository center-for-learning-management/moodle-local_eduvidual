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
 * @package    block_eduvidual
 * @copyright  2020 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eduvidual;

defined('MOODLE_INTERNAL') || die;

class locallib {
    /**
     * Filter a list of users given. Only connected users shall be kept.
     * @param users Array of users.
     * @param idfield field that contains the user-id attribute, defaults to 'id'
     * @param namefield field that contains the name attribute, defaults to 'name'
     */
    public static function filter_userlist($users, $idfield = 'id', $namefield = 'name') {
        $users2 = array();
        foreach ($users AS $user) {
            if (is_array($user)) {
                if (!\block_eduvidual\locallib::is_connected($user[$idfield])) {
                    if (is_siteadmin()) {
                        $user[$namefield] = '! ' . $user[$namefield];
                        $users2[] = $user;
                    }
                } else {
                    $users2[] = $user;
                }
            } else {
                if (!\block_eduvidual\locallib::is_connected($user->$idfield)) {
                    if (is_siteadmin()) {
                        $user->$namefield = '! ' . $user->$namefield;
                        $users2[] = $user;
                    }
                } else {
                    $users2[] = $user;
                }
            }
        }
        return $users2;
    }
    /**
     * Gets the custom profile field 'secret'
     * If not yet set, sets a new secret
     * @param userid UserID
     * @return returns the current secret
    **/
    public static function get_user_secret($userid) {
        global $DB;
        $fieldid = get_config('block_eduvidual', 'fieldid_secret');
        $dbsecret = $DB->get_record('user_info_data', array('fieldid' => $fieldid, 'userid' => $userid));
        if (empty($dbsecret->data)) {
            $insert = false;
            if (empty($dbsecret->userid)) {
                $insert = true;
                $dbsecret = new \stdClass();
                $dbsecret->userid = $userid;
                $dbsecret->fieldid = $fieldid;
            }
            $dbsecret->data = substr(md5(microtime() . rand(9, 999)), 0, 5);
            $dbsecret->dataformat = 0;
            if ($insert) {
                $DB->insert_record('user_info_data', $dbsecret);
            } else {
                $DB->update_record('user_info_data', $dbsecret);
            }
        }

        // Check if the user has a support-flag. If not use the users secret instead!
        $fieldid = get_config('block_eduvidual', 'fieldid_supportflag');
        $dbsupportflag = $DB->get_record('user_info_data', array('fieldid' => $fieldid, 'userid' => $userid));
        if (empty($dbsupportflag->data)) {
            $insert = false;
            if (empty($dbsupportflag->userid)) {
                $insert = true;
                $dbsupportflag = new \stdClass();
                $dbsupportflag->userid = $userid;
                $dbsupportflag->fieldid = $fieldid;
            }
            $user = $DB->get_record('user', array('id' => $userid));
            $dbsupportflag->data = $user->firstname . ' ' . $user->lastname . ' (' . $userid . ')';
            $dbsupportflag->dataformat = 0;
            if ($insert) {
                $DB->insert_record('user_info_data', $dbsupportflag);
            } else {
                $DB->update_record('user_info_data', $dbsupportflag);
            }
        }
        return $dbsecret->data;
    }
    /**
     * Determines if a user is in the same org like another user
     * @param touserid UserID we want to check if we are connected to
     * @param orgids (optional) List of possible orgs, if empty list we use all orgs the srcuser is member of
     * @param srcuserid (optional) User we want to check, if not given use the current logged in user
     * @return Returns true if connected, false if not connected
    **/
    public static function is_connected($touserid, $orgids = array(), $srcuserid = 0) {
        global $DB, $USER;
        if ($srcuserid == 0) {
            $srcuserid = $USER->id;
        }
        if (count($orgids) == 0) {
            $orgids = self::is_connected_orglist($srcuserid);
        }
        $sql = "SELECT userid
                    FROM {block_eduvidual_orgid_userid}
                    WHERE userid=?
                        AND orgid IN (?)";
        $params = array($touserid, implode(',', $orgids));
        $chks = $DB->get_records_sql($sql, $params);
        foreach ($chks AS $chk) {
            if (!empty($chk->userid) && $chk->userid == $touserid) {
                return true;
            }
        }
        return false;
    }
    /**
     * Makes a list of all orgs of a user without the "protectedorgs"
     * @param userid (optional) if not given use the current logged in user.
     * @return array containing all orgids the user is member of.
    **/
    public static function is_connected_orglist($userid = 0) {
        global $DB, $USER;
        if ($userid == 0) {
            $userid = $USER->id;
        }
        $protectedorgs = explode(',', get_config('block_eduvidual', 'protectedorgs'));
        $orgids = array();
        $orgs = $DB->get_records('block_eduvidual_orgid_userid', array('userid' => $userid));
        foreach($orgs AS $org) {
            if (!in_array($org->orgid, $protectedorgs)) {
                $orgids[] = $org->orgid;
            }
        }
        return $orgids;
    }
}
