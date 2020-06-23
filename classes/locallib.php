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
 * @copyright  2020 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

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
                if (!self::is_connected($user[$idfield])) {
                    if (is_siteadmin()) {
                        $user[$namefield] = '! ' . $user[$namefield];
                        $users2[] = $user;
                    }
                } else {
                    $users2[] = $user;
                }
            } else {
                if (!self::is_connected($user->$idfield)) {
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
     * Retrieve Actions for a specific module
     * @param module Module like 'admin', 'manage', ...
     * @param localized localize action labels.
    **/
    public static function get_actions($module, $localized = false) {
        $actions = array();
        switch($module) {
            case 'admin':
                $actions['backgrounds'] = 'admin:backgrounds:title';
                //$actions['blockfooter'] = 'admin:blockfooter:title';
                $actions['coursestuff'] = 'admin:coursestuff:title';
                $actions['defaultroles'] = 'defaultroles:title';
                $actions['questionbank'] = 'admin:questioncategories:title';
                $actions['map'] = 'admin:map';
                //$actions['modulecats'] = 'admin:modulecats:title';
                $actions['orgs'] = 'admin:orgs:title';
                $actions['phplist'] = 'admin:phplist:title';
                $actions['stats'] = 'admin:stats:title';
                //$actions['termsofuse'] = 'admin:termsofuse:title';
            break;
            case 'manage':
                //$actions['archive'] = 'manage:archive';
                //$actions['categories'] = 'manage:categories';
                $actions['mnet'] = 'manage:mnet:action';
                $actions['orgmenu'] = 'manage:orgmenu:title';
                $actions['style'] = 'manage:style';
                $actions['subcats'] = 'manage:subcats:title';
                $actions['users'] = 'manage:users';
                if (is_siteadmin()) {
                    $actions['stats'] = 'manage:stats';
                }
            break;
            case 'teacher':
                //$actions['createmodule'] = 'teacher:createmodule';
                $actions['createcourse'] = 'teacher:createcourse';
            break;
        }
        $actions_by_name = array();
        foreach ($actions AS $action => $name) {
            $actions_by_name[get_string($name, 'local_eduvidual')] = $action;
        }
        $names = array_keys($actions_by_name);
        asort($names);
        $sorted = array();
        foreach($names AS $name) {
            if ($localized) {
                $sorted[$actions_by_name[$name]] = get_string($actions[$actions_by_name[$name]], 'local_eduvidual');
            } else {
                $sorted[$actions_by_name[$name]] = $actions[$actions_by_name[$name]];
            }
        }
        return $sorted;
    }

    /**
     * Get the preferred orgid for a user.
     * @param user if not set, will use $USER.
     * @return int
     */
    public static function get_favorgid($user = 0) {
        if (empty($user)) {
            global $USER;
            $user = $USER;
        }
        return \get_user_preferences('local_eduvidual_favorgid', 0);
    }

    /**
     * Get organisation by categoryid.
     * @param int categoryid (optional) if not set determine current course.
     * @return Object organization
     */
    public static function get_org_by_categoryid($categoryid = 0) {
        global $COURSE, $DB;
        if (empty($categoryid)) {
            $categoryid = $COURSE->category;
        }

        $category = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSECAT, 'instanceid' => $categoryid), '*', IGNORE_MISSING);
        if (empty($category->id)) return false;
        $path = explode('/', $category->path);
        $catcontext =$DB->get_record('context', array('id' => $path[2]));

        // Get organisation by top level course category.
        return $DB->get_record('local_eduvidual_org', array('categoryid' => $catcontext->instanceid));
    }

    /**
     * Get the current org by a context.
     * @param ctxid (optional) context id, if empty will use $PAGE->context->id
     */
    public static function get_org_by_context($ctxid = 0) {
        global $CONTEXT, $DB, $PAGE;
        if (empty($ctxid)) $ctxid = $PAGE->context->id;
        $ctx = $DB->get_record('context', array('id' => $ctxid));
        if (empty($ctx->id)) return;
        $path = explode("/", $ctx->path);
        if (count($path) < 3) return;
        $rootctx = $DB->get_record('context', array('id' => $path[2]));
        return $DB->get_record('local_eduvidual_org', array('categoryid' => $rootctx->instanceid));
    }

    /**
     * Get organisation by courseid.
     * @param int courseid
     * @return Object organization
     */
    public static function get_org_by_courseid($courseid) {
        global $DB;
        $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
        if (empty($course->id)) return;
        return self::get_org_by_categoryid($course->category);
    }

    /**
     * Load all organisations in the scope of this user.
     * By default we only load organisations where we are manager.
     * @param role Specify another role that is used as filter (eg. Teacher), asterisk for any
     * @param allforadmin returns all organisations for website admin, default: true.
    **/
    public static function get_organisations($role="", $allforadmin=true){
        global $DB, $USER;
        if ($allforadmin && is_siteadmin()) {
        	return $DB->get_records_sql('SELECT * FROM {local_eduvidual_org} WHERE authenticated=1 ORDER BY orgid ASC', array());
        } elseif ($role == '*') {
            return $DB->get_records_sql('SELECT o.orgid,o.* FROM {local_eduvidual_org} AS o,{local_eduvidual_orgid_userid} AS ou WHERE o.orgid=ou.orgid AND ou.userid=? GROUP BY o.orgid ORDER BY o.orgid ASC', array($USER->id));
        } else {
        	return $DB->get_records_sql('SELECT o.orgid,o.* FROM {local_eduvidual_org} AS o,{local_eduvidual_orgid_userid} AS ou WHERE o.orgid=ou.orgid AND ou.userid=? AND (ou.role=? OR ou.role=?) GROUP BY o.orgid ORDER BY o.orgid ASC', array($USER->id, 'Manager', $role));
        }
    }

    /**
     * Check if a specific orgid is found in list of organisations
     * @param orgas List of all organisations
     * @param orgid orgid to search
     * @return Return an org if found or false
    **/
    public static function get_organisations_check($orgas, $orgid){
        if (count($orgas) == 0) return false;
        $orgids = array();
        foreach($orgas AS $org) {
            if ($orgid > 0 && $org->orgid == $orgid) {
                return $org;
            }
            $orgids[$org->orgid] = $org;
        }
        // We did not find this orgaid.
        // If there is a default one and in selection return this one.
        $defaultorg = self::get_favorgid();
        if (isset($orgids[$defaultorg])) {
            return $orgids[$defaultorg];
        }
        // No chance - return the first one.
        $k = array_keys($orgas);
        return $orgas[$k[0]];
    }

    /**
     * Retrieves the role of a user in an organization.
     * @param int orgid
     * @param int userid (optional) If not given, will use $USER.
     * @return String role in that organisation (Manager, Teacher, Student, Parent)
     */
    public static function get_orgrole($orgid, $userid = 0) {
        global $DB, $USER;
        if (empty($userid)) $userid = $USER->id;
        $r = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $userid));
        if (!empty($r->role)) return $r->role;
    }

    public static function get_orgsubcats($orgid, $key, $payload = "") {
        global $DB, $USER;
        $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
        if (empty($org->orgid)) return;

        $_options = explode("\n", $org->{$key});
        $options = array();
        if (count($_options) == 0 || empty(trim($_options[0]))) {
            return '';
        } else {
            foreach ($_options AS $a => $option) {
                if (strpos($option, '#') > 0) {
                    if (substr($option, 0, strlen($payload) + 1) == $payload . '#') {
                        $option = trim(substr($option, strlen($payload) + 1));
                    } else {
                        $option = ''; // This option should be removed.
                    }
                }
                $option = str_replace("{firstname}", $USER->firstname, $option);
                $option = str_replace("{lastname}", $USER->lastname, $option);
                if (!empty($option)) {
                    $options[] = trim($option);
                }
            }
        }
        return $options;
    }

    /**
     * Get the highest role of a certain user.
     * @param userid (optional) if not given will use $USER.
     * @return highest role as String (Manager, Teacher or Student)
     */
    public static function get_highest_role($userid = 0) {
        global $DB, $USER;
        if (empty($userid)) $userid = $USER->id;
        $memberships = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $userid));
        $highest = '';
        foreach ($memberships AS $membership) {
            switch ($membership->role) {
                case 'Parent':
                case 'Student':
                    if (empty($highest)) $highest = $membership->role;
                break;
                case 'Teacher':
                    if (empty($highest) || $highest == 'Student') $highest = $membership->role;
                break;
                case 'Manager':
                    $highest = $membership->role;
                break;
            }
        }
        return $highest;
    }

    /**
     * Get all orgs a user is member of. Optionally you can filter by role.
     * @param userid if empty will use $USER
     * @param roles restrict result to certain roles.
     */
    public static function get_user_memberships($userid = 0, $roles = array()) {
        global $DB, $USER;
        if (empty($userid)) $userid = $USER->id;
        $_memberships = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $userid));
        if (count($roles) == 0) return $_memberships;
        $memberships = array();
        foreach ($memberships AS $id => $membership) {
            if (in_array($membership->role, $roles)) {
                $memberships[$id] = $membership;
            }
        }
        return $memberships;
    }

    /**
     * Gets the custom profile field 'secret'
     * If not yet set, sets a new secret
     * @param userid UserID
     * @return returns the current secret
    **/
    public static function get_user_secret($userid) {
        global $DB;
        $fieldid = get_config('local_eduvidual', 'fieldid_secret');
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
        $fieldid = get_config('local_eduvidual', 'fieldid_supportflag');
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
        $strorgids = implode(',', $orgids);

        $sql = "SELECT userid
                    FROM {local_eduvidual_orgid_userid}
                    WHERE userid=?
                        AND orgid IN ($strorgids)";
        $params = array($touserid);
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
        $protectedorgs = explode(',', get_config('local_eduvidual', 'protectedorgs'));
        $orgids = array();
        $orgs = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $userid));
        foreach($orgs AS $org) {
            if (!in_array($org->orgid, $protectedorgs)) {
                $orgids[] = $org->orgid;
            }
        }
        return $orgids;
    }

    /**
     * Check if the current user is manager in any, or in a particular organization, based on category.
     * @param int categoryid (optional)
     */
    public static function is_manager($categoryid = 0) {
        return true;
        //if (is_siteadmin()) return true;
        global $DB, $USER;
        if (empty($categoryid)) {
            // Check if user is manager in any organization.
            $chk = $DB->get_record('local_eduvidual_orgid_userid', array('role' => 'Manager', 'userid' => $USER->id));
            return !empty($chk->orgid);
        } else {
            // Check if user is manager in a particular organization.
            $org = self::get_org_by_categoryid($categoryid);
            if (empty($org->orgid)) return false;
            $chk = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'role' => 'Manager', 'userid' => $USER->id));
            return !empty($chk->orgid);
        }
    }

    /**
     * List all files from a certain file area
     */
    public static function list_area_files($areaname, $itemid, $context = false) {
        if (!$context) {
            $context = context_system::instance();
        }
        $files = array();
        $fs = get_file_storage();
        $files_ = $fs->get_area_files($context->id, 'block_eduvidual', $areaname, $itemid);
        foreach ($files_ as $file) {
            if (str_replace('.', '', $file->get_filename()) != ""){
                $file->url = '' . moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $files[] = $file;
            }
        }
        return $files;
    }
    /**
     * Show a selector for all organistions a user has
     * @param role Role that is required ('Teacher', 'Manager', '*')
     * @param orgid org that should be selected. if not given tries to retrieve orgid via optional_param.
    **/
    public static function print_org_selector($role = '*', $orgid = 0) {
        global $DB,$PAGE;

        $orgs = self::get_organisations($role);
        $act = optional_param('act', '', PARAM_TEXT);
        if ($orgid == 0) {
            $orgid = optional_param('orgid', 0, PARAM_INT);
        }
        if ($orgid > 0) {
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
        } else {
            $org = new stdClass();
            $org->orgid = 0;
            $org->name = get_string('none');
        }

        $favorgid = \local_eduvidual\locallib::get_favorgid();

        $parts = parse_url($PAGE->url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo "\t<select onchange=\"var sel = this; require(['local_eduvidual/main'], function(MAIN) { MAIN.navigate('" . $url . "?act=" . $act . "&orgid=' + sel.value); });\">\n";
        foreach($orgs AS $org) {
            echo "\t\t<option value=\"" . $org->orgid . "\"" . (((empty($orgid) && $orgid == $favorgid) || $orgid == $org->orgid)?' selected="selected"':'') . ">" . $org->orgid . " | " . $org->name . "</option>\n";
        }
        echo "\t</select>\n";
    }
    /**
     * Show a selector for all actions that are available
     * @param actions array containing valid actions
    **/
    public static function print_act_selector($actions = array(), $act = '') {
        global $PAGE;
        if (empty($act)) {
            $act = optional_param('act', '', PARAM_TEXT);
        }
        $orgid = optional_param('orgid', '', PARAM_TEXT);
        $action = get_string('none');
        if ($act != '') {
            if (get_string_manager()->string_exists($actions[$act], 'local_eduvidual')) {
                $action = get_string($actions[$act], 'local_eduvidual');
            } else {
                $action = '[[' . $actions[$act] . ']]';
            }
        }
        $parts = parse_url($PAGE->url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo "\t<select onchange=\"var sel = this; require(['local_eduvidual/main'], function(MAIN) { MAIN.navigate('" . $url . "?orgid=" . $orgid . "&act=' + sel.value); });\">\n";
        $keys = array_keys($actions);
        foreach($keys AS $key) {
            echo "\t\t<option value=\"" . $key . "\"" . (($key == $act)?' selected':'') . ">" . get_string($actions[$key], 'local_eduvidual') . "</option>\n";
        }
        echo "\t</select>\n";
    }
}
