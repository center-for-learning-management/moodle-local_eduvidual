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
    private static $cache_application;
    private static $cache_request;
    private static $cache_session;

    const ROLE_UNKNOWN = '';
    const ROLE_STUDENT = 'Student';
    const ROLE_PARENT = 'Parent';
    const ROLE_TEACHER = 'Teacher';
    const ROLE_MANAGER = 'Manager';

    /**
     * Method used as getter and setter for caches.
     * @param type either application or session
     * @param key (optional) used to get a certain key from the cache
     * @param value (optional) used to set a value for a certain key in the cache
     * @param delete (optional) delete this key.
     */
    public static function cache($type, $key = '', $value = '', $delete = 0) {
        if (!in_array($type, array('application', 'request', 'session'))) return;
        $cache = self::${'cache_' . $type};
        if (empty($cache)) {
            $cache = \cache::make('local_eduvidual', $type);
            self::${'cache_' . $type} = $cache;
        }
        if (!empty($key) && !empty($delete)) {
            $cache->delete($key);
            return;
        } elseif (!empty($key) && !empty($value)) {
            // We act as setter
            $cache->set($key, $value);
            return $value;
        } elseif (!empty($key)) {
            // We act as getter
            return $cache->get($key);
        } else {
            // We return the cache itself.
            return $cache;
        }
    }

    /**
     * We only want users to access the question bank if the capabilities were
     * given by roles in the course context. We do not want this capability
     * to be inherited from parent contexts!
     * @param coursecontext the current course context to check.
     * @return boolean true if the capability was given by a role in course context.
     */
    public static function can_access_course_questionbank($coursecontext, $user = null, $doanything = true) {
        global $DB, $USER;
        if (empty($user)) $user = $USER;
        if ($USER->id == $user->id && is_siteadmin() && $doanything) return true;

        $cachefieldid = "can_access_course_questionbank-" . $user->id . "-" . $coursecontext->id;
        $canaccess = self::cache('session', $cachefieldid);
        if (!empty($canaccess)) return ($canaccess == 1) ? true : false;

        $syscontext = \context_system::instance();
        $roles = get_user_roles($coursecontext, $user->id);
        foreach ($roles as $role) {
            // We only accept roles in the course context itself.
            if ($role->contextid != $coursecontext->id) continue;
            $sql = "SELECT id,contextid FROM {role_capabilities}
                        WHERE roleid=?
                            AND (contextid=? OR contextid=?)
                            AND (capability=? OR capability=?)";
            $params = array($role->roleid, $syscontext->id, $coursecontext->id, 'moodle/question:viewall', 'moodle/question:viewmine');
            $chks = $DB->get_records_sql($sql, $params);
            foreach ($chks as $chk) {
                if (!empty($chk->id)) {
                    self::cache('session', $cachefieldid, 1);
                    return true;
                }
            }
        }
        // We are not allowed.
        self::cache('session', $cachefieldid, -1);
    }
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
                    } else {
                        // @TODO if we filter the entries out and would have been more than 100, user would not see anything.
                    }
                } else {
                    $users2[] = $user;
                }
            } else {
                if (!self::is_connected($user->$idfield)) {
                    if (is_siteadmin()) {
                        $user->$namefield = '! ' . $user->$namefield;
                        $users2[] = $user;
                    } else {
                        // @TODO if we filter the entries out and would have been more than 100, user would not see anything.
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
                $actions['coursedelete'] = 'admin:coursedelete:title';
                $actions['coursestuff'] = 'admin:coursestuff:title';
                $actions['defaultroles'] = 'defaultroles:title';
                $actions['questionbank'] = 'admin:questioncategories:title';
                // $actions['licence'] = 'admin:licence:title';
                $actions['map'] = 'admin:map:title';
                $actions['orgs'] = 'admin:orgs:title';
                $actions['stats'] = 'admin:stats:title';
                //$actions['termsofuse'] = 'admin:termsofuse:title';
                break;
            case 'manage':
                //$actions['archive'] = 'manage:archive';
                //$actions['categories'] = 'manage:categories';
                $actions['coursesettings'] = 'manage:coursesettings';
                $actions['login'] = 'manage:login:action';
                $actions['orgmenu'] = 'manage:orgmenu:title';
                $actions['style'] = 'manage:style';
                $actions['subcats'] = 'manage:subcats:title';
                $actions['users'] = 'manage:users';
                $actions['educloud'] = 'manage:educloud';
                if (get_config('local_webuntis', 'version') >= 2021121500) {
                    $actions['webuntis'] = 'manage:webuntis';
                }
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
     * Returns the dummydomain for creating user accounts without email.
     * @param pattern (String) to be prepended before wwwroot, by default 'doesnotexist'
     */
    public static function get_dummydomain($pattern = "a.") {
        global $CFG;
        return '@' . $pattern . str_replace(array('https://', 'http://', 'www.'), '', $CFG->wwwroot);
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
     * Get an org by a specific field parameter.
     * Uses cache api.
     * @param field the field to use.
     * @param value that the field must have.
     */
    public static function get_org($field, $value) {
        global $DB;
        $orgid = self::cache('session', "orgid-$field-$value");
        if (!empty($orgid)) {
            $org = self::cache('session', "org-$orgid");
        }
        if (!empty($org)) return $org;

        $org = $DB->get_record('local_eduvidual_org', array($field => $value));
        if (!empty($org->orgid)) {
            // Store this org in cache.
            self::cache('session', "org-$org->orgid", $org);
            // Use the requested field anyway.
            self::cache('session', "orgid-$field-$value", $org->orgid);
            // Use some other fields too.
            self::cache('session', "orgid-orgid-$org->orgid", $org->orgid);
            self::cache('session', "orgid-categoryid-$org->categoryid", $org->orgid);
            self::cache('session', "orgid-courseid-$org->courseid", $org->orgid);
            self::cache('session', "orgid-supportcourseid-$org->supportcourseid", $org->orgid);
        }

        return $org;
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
        $ctx = \context_coursecat::instance($categoryid);
        if (empty($ctx->id)) return false;

        return self::get_org_by_context($ctx->id);
    }

    /**
     * Get the current org by a context.
     * @param ctxid (optional) context id, if empty will use $PAGE->context->id
     */
    public static function get_org_by_context($ctxid = 0) {
        global $CONTEXT, $DB, $PAGE;
        if (empty($ctxid)) $ctxid = $PAGE->context->id;
        $ctx = \context::instance_by_id($ctxid);
        if (empty($ctx->id)) return;
        $path = explode("/", $ctx->path);
        if (count($path) < 3) return;
        $rootctx = \context::instance_by_id($path[2]);
        return self::get_org('categoryid', $rootctx->instanceid);
    }

    /**
     * Get organisation by courseid.
     * @param int courseid
     * @param int strictness
     * @return Object organization
     */
    public static function get_org_by_courseid($courseid, $strictness = MUST_EXIST) {
        global $DB;
        $ctx = \context_course::instance($courseid, $strictness);
        if (empty($ctx->id)) return;
        return self::get_org_by_context($ctx->id);
    }

    /**
     * Load all organisations in the scope of this user.
     * By default we only load organisations where we are manager.
     * @param role Specify another role that is used as filter (eg. Teacher), asterisk for any
     * @param allforadmin returns all organisations for website admin, default: true.
     **/
    public static function get_organisations($role="*", $allforadmin=true){
        global $DB, $USER;
        if ($allforadmin && is_siteadmin()) {
            return $DB->get_records_sql('SELECT * FROM {local_eduvidual_org} WHERE authenticated>0 ORDER BY orgid ASC', array());
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

        $highest = self::cache('session', "highestrole-$userid");
        if (!empty($highest)) return $highest;

        $memberships = $DB->get_records('local_eduvidual_orgid_userid', array('userid' => $userid));
        $highest = '';
        foreach ($memberships AS $membership) {
            switch ($membership->role) {
                case static::ROLE_PARENT:
                case static::ROLE_STUDENT:
                    if (empty($highest)) {
                        $highest = $membership->role;
                    }
                    break;
                case static::ROLE_TEACHER:
                    if (empty($highest) || $highest == static::ROLE_STUDENT) {
                        $highest = $membership->role;
                    }
                    break;
                case static::ROLE_MANAGER:
                    $highest = $membership->role;
                    break;
            }
        }
        self::cache('session', "highestrole-$userid", $highest);
        return $highest;
    }
    /**
     * Create a temporary directory and return its path.
     * @return path to tempdir.
     */
    public static function get_tempdir() {
        global $CFG;
        $dir = $CFG->tempdir . '/eduvidual-coursefiles';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        return $dir;
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
     * Determine if this is Moodle 4.
     * @param getbuildnumber if true returns the build number, else returns boolean.
     * @return boolean in case of getbuildnumber = false, int in case of getbuildnumber = true.
     */
    public static function is_4($getbuildnumber = false) {
        global $CFG;
        if ($getbuildnumber) return $CFG->version;
        else return ($CFG->version >= 2021110600);
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
        if (empty($orgids)) {
            $orgids = [0];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($orgids);
        $sql = "SELECT DISTINCT(userid)
                    FROM {local_eduvidual_orgid_userid}
                    WHERE userid=?
                        AND orgid $insql";

        // If we already had a positive result for this search in cache, use it.
        $cachefieldid = "isconnected-" . $touserid . "-" . md5($sql);
        $isconnected = self::cache('session', $cachefieldid);
        if ($isconnected) return true;

        $params = array($touserid);
        $chks = $DB->get_records_sql($sql, array_merge($params,$inparams));

        foreach ($chks AS $chk) {
            if (!empty($chk->userid) && $chk->userid == $touserid) {
                self::cache('session', $cachefieldid, 1);
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
    public static function is_manager($categoryid = 0, $nocaches = false) {
        if (is_siteadmin()) return true;
        global $DB, $USER;
        $ismanager = false;
        if (empty($categoryid)) {
            // Check if user is manager in any organization.
            if (empty($nocaches)) {
                $ismanager = self::cache('session', "ismanager");
            }
            if (empty($ismanager)) {
                $chk = $DB->get_records('local_eduvidual_orgid_userid', array('role' => 'Manager', 'userid' => $USER->id));
                $ismanager = self::cache('session', "ismanager-$USER->id", count($chk) > 0);
            }
            return $ismanager;
        } else {
            // Check if user is manager in a particular organization.
            if (empty($nocaches)) {
                $ismanager = self::cache('session', "ismanager-$USER->id-$categoryid");
            }
            if (empty($ismanager)) {
                $org = self::get_org_by_categoryid($categoryid);
                if (empty($org->orgid)) return false;
                $chk = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $org->orgid, 'role' => 'Manager', 'userid' => $USER->id));
                $ismanager = self::cache('session', "ismanager-$USER->id-$categoryid", !empty($chk->orgid));
            }
            return $ismanager;
        }
    }
    /**
     * Checks whether a course is a template course.
     * @param courseid to check.
     * @return true or false.
     */
    public static function is_templatecourse($courseid) {
        $identifiers = array('coursebasementempty', 'coursebasementrestore', 'coursebasementtemplate', 'orgcoursebasement', 'supportcourse_template');
        foreach ($identifiers as $identifier) {
            if ($courseid == get_config('local_eduvidual', $identifier)) return true;
        }
        return false;
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
        $files_ = $fs->get_area_files($context->id, 'local_eduvidual', $areaname, $itemid);
        foreach ($files_ as $file) {
            if (str_replace('.', '', $file->get_filename()) != ""){
                $file->url = '' . \moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
                $files[] = $file;
            }
        }
        return $files;
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
        echo "\t<select class=\"custom-select\" onchange=\"var sel = this; require(['local_eduvidual/main'], function(MAIN) { MAIN.navigate('" . $url . "?orgid=" . $orgid . "&act=' + sel.value); });\">\n";
        $keys = array_keys($actions);
        foreach($keys AS $key) {
            echo "\t\t<option value=\"" . $key . "\"" . (($key == $act)?' selected':'') . ">" . get_string($actions[$key], 'local_eduvidual') . "</option>\n";
        }
        echo "\t</select>\n";
    }
    /**
     * Set the X-orgclass and X-orgid for the current user.
     * @return the given X-orgclass.
     */
    public static function set_xorg_data() {
        global $_COOKIE, $DB, $USER;

        $xorgcl = 'X-orgclass';
        $xorgid = 'X-orgid';
        $xuseri = 'X-userid';
        $fallback = 0;

        if (empty($_COOKIE[$xuseri]) || $_COOKIE[$xuseri] != $USER->id) {
            if (isloggedin() && !isguestuser()) {
                $primaryorg = (object) array('orgid' => '');
                $sql = "SELECT o.orgid,o.orgclass
                            FROM {local_eduvidual_org} o, {local_eduvidual_orgid_userid} ou
                            WHERE o.orgid=ou.orgid
                                AND o.orgclass IS NOT NULL
                                AND ou.userid=?
                            ORDER BY orgclass ASC
                            LIMIT 0,1";
                $primaryorg = $DB->get_record_sql($sql, array($USER->id));

                if (!empty($primaryorg->orgid)) {
                    header($xorgid . ': ' . $primaryorg->orgid);
                    header($xorgcl . ': ' . $primaryorg->orgclass);
                    setcookie($xorgid, $primaryorg->orgid, 0,'/');
                    setcookie($xorgcl, $primaryorg->orgclass, 0,'/');
                    setcookie($xuseri, $USER->id, 0,'/');
                    return $primaryorg->orgclass;
                }
            }
        } else {
            // We have data for this user. Set header and return.
            if (!empty($_COOKIE[$xorgid])) {
                header($xorgid . ': ' . $_COOKIE[$xorgid]);
            }
            if (!empty($_COOKIE[$xorgcl])) {
                header($xorgcl . ': ' . $_COOKIE[$xorgcl]);
                return $_COOKIE[$xorgcl];
            }
        }
    }
}
