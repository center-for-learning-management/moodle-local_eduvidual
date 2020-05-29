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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_eduvidual extends block_base /* was block_list */ {
    static $datainitalized = false;
    static $field_secret = ''; // Users Secret
    static $grantedadminaccess = false; // Set to true if access was granted for admin
    static $originallocation = ''; // Used if we come from an app
    static $orgs;
    static $org;
    static $orgrole;
    static $role = "Student"; // Role for user interface (Manager, Teacher, Student)
    static $qcats;
    static $scripts_on_load = array();
    static $pagelayout = ''; // Stores a force pagelayout (embedded prevents background image)

    /**
     * Initializes data if none is there
    **/
    public static function initData(){
        if (self::$datainitalized) return;
        global $CFG, $DB, $PAGE, $USER;
        $sysctx = context_system::instance();

        $orgs = $DB->get_records('block_eduvidual_orgid_userid', array('userid' => $USER->id));
        block_eduvidual::$orgs = array();
        foreach($orgs AS $org) {
            block_eduvidual::$orgs[] = $org;
            if ($org->role == "Manager") {
                block_eduvidual::$role = "Manager";
            } elseif ($org->role == "Teacher" && block_eduvidual::$role == "Student") {
                block_eduvidual::$role = "Teacher";
            }
        }
        if(is_siteadmin()) {
            block_eduvidual::$role = "Administrator";
        }

        $qcats = $DB->get_records('block_eduvidual_userqcats', array('userid' => $USER->id));
        block_eduvidual::$qcats = array();
        foreach($qcats AS $qcat) {
            block_eduvidual::$qcats[] = $qcat->categoryid;
        }
        block_eduvidual::determine_org();

        $cache = cache::make('block_eduvidual', 'appcache');
        block_eduvidual::$originallocation = $cache->get('originallocation');

        block_eduvidual::$datainitalized = true;
    }
    /**
     * Determines org by courseid. If no courseid given tries current course.
     * @param courseid courseid to determine org for or 0.
     * @return org as db-object.
     **/
    public static function get_org_by_courseid($courseid = 0){
        global $COURSE, $DB;
        if (empty($courseid) && !empty($COURSE->id)) {
            $courseid = $COURSE->id;
        }
        if (empty($courseid)) return;
        $course = $DB->get_record('course', array('id' => $courseid), '*', IGNORE_MISSING);
        if (empty($course->id)) return;
        $category = $DB->get_record('course_categories', array('id' => $course->category), '*', IGNORE_MISSING);
        $path = explode('/', $category->path);
        if (count($path) < 2) return;
        $org = $DB->get_record('block_eduvidual_org', array('categoryid' => $path[1]), '*', IGNORE_MISSING);
        return $org;
    }
    public static function determine_org() {
        global $DB, $PAGE;
        // Determine current organization
        $categoryid = 0;
        // This must be in a try catch block, otherwise we risk moodle errors
        // that we access course category before it is set up.
        try {
            if (!empty($PAGE->course->category)) $categoryid = $PAGE->course->category;
            if (!empty($PAGE->category->id)) $categoryid = $PAGE->category->id;
        } catch(Exception $e) {}


        if (!empty($categoryid)) {
            $category = $DB->get_record('course_categories', array('id' => $categoryid));
            if (isset($category->path)) {
                $path = explode("/", $category->path);
                $main = $DB->get_record('course_categories', array('id' => $path[1]));
                // Check if this category belongs to an organization
                $org = $DB->get_record('block_eduvidual_org', array('categoryid' => $main->id));
                if (!empty($org->orgid)) {
                    block_eduvidual::set_org($org->orgid);
                }
            }
        }

        return block_eduvidual::$orgrole;
    }

    /**
     * Set the current org by a given courseid
     * @param courseid
    **/
    public static function set_org_by_courseid($courseid) {
        global $DB, $PAGE;
        $course = $DB->get_record('course', array('id' => $courseid));
        if ($course && $course->id > 0) {
            try {
                // Sometimes this may be called when we are not
                // allowed to do that anymore. Therefore it is in a try catch block
                $PAGE->set_course($course);
            } catch(Exception $e) {}
            return block_eduvidual::set_org_by_categoryid($course->category);
        } else {
            return "No Course with #" . $courseid . " found";
        }
    }
    /**
     * Set the current org by a given categoryid
     * @param categoryid
    **/
    public static function set_org_by_categoryid($categoryid) {
        global $DB;
        $category = $DB->get_record('course_categories', array('id' => $categoryid));
        if ($category && isset($category->path)) {
            $path = explode('/', $category->path);
            if (count($path) > 0) {
                $org = $DB->get_record('block_eduvidual_org', array('categoryid' => $path[1]));
                if (isset($org->orgid)) {
                    return self::set_org($org->orgid);
                }
            } else {
                return "Path {$category->path} of Category $category to short";
            }
        } else {
            return "No path found for Category $category";
        }
    }
    /**
     * Set the current orgid and store in block_eduvidual::$org
    **/
    public static function set_org($orgid) {
        global $DB, $USER;
        $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid));
        if (isset($org->orgid) && $org->orgid == $orgid) {
            block_eduvidual::$org = $org;
            $entry = $DB->get_record('block_eduvidual_orgid_userid', array('orgid' => $orgid, 'userid' => $USER->id));
            if (isset($entry->role)) {
                block_eduvidual::$orgrole = $entry->role;
            } else {
                block_eduvidual::$orgrole = '';
            }
        }
        return $org;
    }
    /**
     * Checks, Sets or updates a token.
     * @param token to check for
     * @param userid the token belongs to
     * @param insert do we insert the token if it does not exist?
     * @return true if token existed, false if did not exist.
    **/
    public static function set_token($token, $userid, $insert = false) {
        global $DB;
        $entry = $DB->get_record('block_eduvidual_usertoken', array('token' => $token, 'userid' => $userid));
        if (isset($entry->userid) && $entry->userid == $userid) {
            $entry->used = time();
            $DB->update_record('block_eduvidual_usertoken', $entry);
            return true;
        } else {
            if ($insert) {
                $entry = (object) array(
                    'created' => time(),
                    'token' => $token,
                    'used' => time(),
                    'userid' => $userid,
                );
                $DB->insert_record('block_eduvidual_usertoken', $entry);
            }
            return false;
        }
    }
    /**
     * Loads a user and sets it as the logged in user.
     * @param userid to be activated
    **/
    public static function set_user($userid) {
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user, true, true);
        // Do the user log-in.
        if (!$user = get_complete_user_data('id', $user->id)) {
            throw new moodle_exception('cannotfinduser', '', '', $user->id);
        }
        complete_user_login($user);
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());
        return ($user->id == $userid);
    }
    /**
     * Get a specific key
     * @param key the key to get.
     * @param payload some additional value.
    **/
    public static function get($key, $payload = "") {
        global $CFG, $DB, $USER;
        if (!block_eduvidual::$datainitalized) {
            block_eduvidual::initData();
        }
        if ($key == "defaultorg") {
            return get_user_preferences('block_eduvidual_defaultorg');
        }
        if ($key == "role") {
            return block_eduvidual::$role;
        }
        if ($key == "orgs") {
            return block_eduvidual::$orgs;
        }
        if ($key == "qcats") {
            return block_eduvidual::$qcats;
        }
        if ($key == "org") {
            if (isset(block_eduvidual::$org) && block_eduvidual::$org->id > 0) {
                return block_eduvidual::$org;
            } else {
                global $COURSE;
                return self::set_org_by_courseid($COURSE->id);
            }
        }
        if ($key == "orgbanner") {
            if (isset(block_eduvidual::$org->banner)) {
                // The str_replace can be removed once only eduvidual.at is used.
                return str_replace('https://www.eduvidual.org', $CFG->wwwroot, block_eduvidual::$org->banner);
            } else {
                return "";
            }
        }
        if ($key == "orgrole") {
            if (isset(block_eduvidual::$orgrole)) {
                return block_eduvidual::$orgrole;
            } else {
                return '';
            }
        }
        if ($key == "orgid") {
            if (isset(block_eduvidual::$org->orgid)) {
                return block_eduvidual::$org->orgid;
            } else {
                return 0;
            }
        }
        // We will deprecate this once createcourse is done with ajax.
        if ($key == "subcats1" || $key == "subcats2" || $key == "subcats3") {
            if (isset(block_eduvidual::$org->{$key})) {
                $_options = explode("\n", block_eduvidual::$org->{$key});
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
            } else {
                return '';
            }
        }
        if ($key == "subcats1lbl" || $key == "subcats2lbl" || $key == "subcats3lbl" || $key == "subcats4lbl") {
            if (!empty(block_eduvidual::$org->{$key})) {
                return block_eduvidual::$org->{$key};
            } else {
                switch ($key) {
                    case "subcats1lbl": return get_string('createcourse:subcat1', 'block_eduvidual'); break;
                    case "subcats2lbl": return get_string('createcourse:subcat2', 'block_eduvidual'); break;
                    case "subcats3lbl": return get_string('createcourse:subcat3', 'block_eduvidual'); break;
                    case "subcats4lbl": return get_string('createcourse:subcat4', 'block_eduvidual'); break;
                }
                // This should not happen...
                return 'n/a';
            }
        }
        if ($key == "subcats1org" || $key == "subcats2org" || $key == "subcats3org" || $key == "subcats4org") {
            if (!empty(block_eduvidual::$org->{substr($key, 0, 8) . 'lbl'})) {
                return block_eduvidual::$org->{substr($key, 0, 8) . 'lbl'};
            } else {
                return '';
            }
        }
        if ($key == "customcss") {
            if (isset(block_eduvidual::$org->customcss)) {
                return block_eduvidual::$org->customcss;
            } else {
                return "";
            }
        }
        if ($key == "field_secret") {
            if (isset(block_eduvidual::$field_secret)) {
                return block_eduvidual::$field_secret;
            } else {
                return "";
            }
        }
        if ($key == "originallocation") {
            if (isset(block_eduvidual::$originallocation)) {
                return block_eduvidual::$originallocation;
            } else {
                return "";
            }
        }
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
        	return $DB->get_records_sql('SELECT * FROM {block_eduvidual_org} WHERE authenticated=1 ORDER BY orgid ASC', array());
        } elseif ($role == '*') {
            return $DB->get_records_sql('SELECT o.orgid,o.* FROM {block_eduvidual_org} AS o,{block_eduvidual_orgid_userid} AS ou WHERE o.orgid=ou.orgid AND ou.userid=? GROUP BY o.orgid ORDER BY o.orgid ASC', array($USER->id));
        } else {
        	return $DB->get_records_sql('SELECT o.orgid,o.* FROM {block_eduvidual_org} AS o,{block_eduvidual_orgid_userid} AS ou WHERE o.orgid=ou.orgid AND ou.userid=? AND (ou.role=? OR ou.role=?) GROUP BY o.orgid ORDER BY o.orgid ASC', array($USER->id, 'Manager', $role));
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
        $defaultorg = self::get('defaultorg');
        if (isset($orgids[$defaultorg])) {
            return $orgids[$defaultorg];
        }
        // No chance - return the first one.
        $k = array_keys($orgas);
        return $orgas[$k[0]];
    }
    /**
     * Print the rotary menu
     * @return The mainmenu and submenu to be shown in the corner
    **/
    public static function print_mainmenu(){
        global $CFG, $USER, $PAGE;
        $options = block_eduvidual::get_mainmenu_options();

        $menu = new stdClass();
        $menu->header = "";
        $menu->main = "";
        $menu->sub = "";

        if (count($options) == 0) return $menu;

        $menu->sub .= "<div class=\"block_eduvidual_submenu_wrapper list-group\"><div class=\"block_eduvidual_submenu hide-on-print\">";
        //$menu->sub .= "<div class=\"secret list-group-item\">" . $USER->id . '#' . block_eduvidual::get('field_secret') . "</div>";
        foreach($options AS $option) {
                if (!isset($option["href"])) {
                    if (!isset($option["type"])) {
                        $option["type"] = 'div';
                    }
                    if (!isset($option["class"])) {
                        $option["class"] = 'divider';
                    }
                    $menu->sub .= "\t<" . $option["type"] . " class=\"" . $option["class"] . "\">" . $option["title"] . "</" . $option["type"] . ">";
                } else {
                    if (!isset($option["attributes"])) {
                        $option["attributes"] = '';
                    }
                    $menu->sub .= "\t<div class=\"_list-group-item\">\t\t<img src=\"" . $option["icon"] . "\" class=\"icon\">";
                    $menu->sub .= "\t\t<a href=\"" . $option["href"] . "\"";
                    $menu->sub .= " " . $option["attributes"] . ">" . $option["title"] . "</a>";
                    $menu->sub .= "\t</div>";
                }
        }
        //$menu[] = "<div class=\"attributions\"><a href=\"" . $CFG->wwwroot . "/blocks/eduvidual/pages/attributions.php\"><img src=\"" . $CFG->wwwroot . "/blocks/eduvidual/pix/cc.svg\" alt=\"cc\" style=\"height: 1em;\" /></a></div></div></div>";
        $menu->main .= "<div class=\"block_eduvidual_mainmenu hide-on-print\" style=\"filter: grayscale(100%);\">";
        $menu->main .= "\t<a href=\"#\" onclick=\"require(['block_eduvidual/user'], function(USER) { USER.toggleSubmenu(); }); return false;\">&nbsp;</a>";
        $menu->main .= "</div>";

        return $menu;
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
                $actions['blockfooter'] = 'admin:blockfooter:title';
                $actions['coursestuff'] = 'admin:coursestuff:title';
                $actions['defaultroles'] = 'defaultroles:title';
                $actions['explevel'] = 'explevel:title';
                $actions['map'] = 'admin:map';
                $actions['modulecats'] = 'admin:modulecats:title';
                $actions['orgs'] = 'admin:orgs:title';
                $actions['phplist'] = 'admin:phplist:title';
                $actions['stats'] = 'admin:stats:title';
                $actions['termsofuse'] = 'admin:termsofuse:title';
            break;
            case 'manage':
                $actions['archive'] = 'manage:archive';
                $actions['categories'] = 'manage:categories';
                $actions['mnet'] = 'manage:mnet:action';
                $actions['style'] = 'manage:style';
                $actions['subcats'] = 'manage:subcats:title';
                $actions['users'] = 'manage:users';
                if (is_siteadmin())
                    $actions['stats'] = 'manage:stats';
            break;
            case 'teacher':
                $actions['createmodule'] = 'teacher:createmodule';
                $actions['createcourse'] = 'teacher:createcourse';
            break;
        }
        $actions_by_name = array();
        foreach ($actions AS $action => $name) {
            $actions_by_name[get_string($name, 'block_eduvidual')] = $action;
        }
        $names = array_keys($actions_by_name);
        asort($names);
        $sorted = array();
        foreach($names AS $name) {
            if ($localized) {
                $sorted[$actions_by_name[$name]] = get_string($actions[$actions_by_name[$name]], 'block_eduvidual');
            } else {
                $sorted[$actions_by_name[$name]] = $actions[$actions_by_name[$name]];
            }
        }
        return $sorted;
    }
    /**
     * Retrieve the main menu options for this user
     * @return array list of array-items with "title", "href" and "icon"
    **/
    public static function get_mainmenu_options(){
        global $CFG, $COURSE, $DB, $PAGE, $USER;
        $ORG = block_eduvidual::get('org');
        $ORGS = block_eduvidual::get('orgs');
        $ORGID = (!empty($ORG->orgid)?$ORG->orgid:'');
        $CATEGORYID = (!empty($PAGE->category->id)?$PAGE->category->id:'');
        $context = $PAGE->context;

        $options = array();
        if (isset($USER->id) && $USER->id > 0 && !isguestuser($USER) && count($ORGS) > 0) {
            $options[] = array(
                "title" => get_string('Browse_org', 'block_eduvidual'),
                "href" => '/blocks/eduvidual/pages/categories.php',
                "icon" => '/pix/i/withsubcat.svg', //'/blocks/eduvidual/pix/user_courses.svg',
            );
        }
        if (in_array(block_eduvidual::get('role'), array('Manager', 'Teacher')) || is_siteadmin()) {
            $options[] = array(
                "title" => get_string('teacher:createcourse', 'block_eduvidual'),
                "href" => '/blocks/eduvidual/pages/teacher.php?act=createcourse&orgid=' . $ORGID .
                          '&categoryid=' . $CATEGORYID,
                "icon" => '/pix/t/cohort.svg', //'/blocks/eduvidual/pix/teacher_createcourse.svg',
            );
        }
        $context = $context || (isset($PAGE->context->id)) ? $PAGE->context : context_system::instance();
        if (has_capability('block/edupublisher:canuse', $context)) {
            $options[] = array(
                "title" => get_string('teacher:addfromcatalogue', 'block_eduvidual'),
                "href" => '/blocks/edupublisher/pages/search.php',
                "icon" => '/pix/t/add.svg',
            );
        }
        if (in_array(block_eduvidual::get('role'), array('Manager')) || is_siteadmin()) {
            $options[] = array(
                "title" => get_string('Management', 'block_eduvidual'),
                "href" => '/blocks/eduvidual/pages/manage.php?act=&orgid=' . $ORGID,
                "icon" => '/pix/i/backup.svg', //'/blocks/eduvidual/pix/manage_archive.svg',
            );
        }

        $navbar = explode("\n", get_config('block_eduvidual', 'navbar'));
        foreach($navbar as $__option) {
            if (empty($__option)) continue;
            $_option = explode("|", $__option);
            $option = array('title' => $__option, 'href' => '#', 'icon' => '');
            if (count($_option) > 1) {
                $option['title'] = $_option[0];
                $option['href'] = $_option[1];
            }
            if (isset($_option[2])) {
                $option['icon'] = $_option[2];
            }
            if (isset($_option[3])) {
                $option['target'] = $_option[3];
            }
            $options[] = $option;
        }
        return $options;
    }
    /**
     * DEPRECATED, now in classes\locallib.php.
    **/
    public static function is_connected($touserid, $orgids = array(), $srcuserid = 0) {
        error_log('A script is using a deprecated call to block_eduvidual::is_connected');
        return \block_eduvidual\is_connected($touserid, $orgids, $srcuserid);
    }

    /**
     * DEPRECATED, now in classes\locallib.php.
    **/
    public static function is_connected_orglist($userid = 0) {
        error_log('A script is using a deprecated call to block_eduvidual::is_connected_orglist');
        return \block_eduvidual\is_connected_orglist($userid);
    }
    /**
     * User wanted to access a page he is not permitted to.
     * If user is logged in redirect to privacy warning.
     * If user is not logged in redirect to login.
    **/
    public static function redirect_privacy_issue($camefrom) {
        global $CFG, $PAGE, $SESSION, $USER;
        $SESSION->wantsurl = $PAGE->url->__toString();
        header('Location: ' . $CFG->wwwroot . '/blocks/eduvidual/pages/restricted.php?camefrom=' . $camefrom);
    }
    /**
     * Automatically sets the context based on the PAGE or current org
     * Fall back to system-context if nothing else applies
     * @param courseid to force
    **/
    public static function set_context_auto($courseid = 0, $categoryid = 0) {
        global $COURSE, $org, $PAGE;
        $myorg = block_eduvidual::get('org');
        if ($courseid <= 1 && $PAGE->course->id > 1) {
            $courseid = $PAGE->course->id;
        } else if ($courseid <= 1 && $COURSE->id > 1) {
            $courseid = $COURSE->id;
        }
        if ($categoryid <= 1 && isset($myorg->categoryid) && $myorg->categoryid > 1) {
            $categoryid = $myorg->categoryid;
        } else if ($categoryid <= 1 && isset($org->categoryid) && $org->categoryid > 1) {
            $categoryid = $org->categoryid;
        } else if ($categoryid <= 1 && $PAGE->course->category > 1) {
            $categoryid = $PAGE->course->category;
        } else if ($categoryid <= 1 && $COURSE->category > 1) {
            $categoryid = $COURSE->category;
        }

        $PAGE->set_context(context_system::instance());
        if ($categoryid > 1) {
            $PAGE->set_context(context_coursecat::instance($categoryid));
            $PAGE->set_pagelayout('coursecategory');
            try { $PAGE->set_category_by_id($categoryid); } catch(Exception $e) {}
            $PAGE->navbar->add($myorg->name, new moodle_url('/course/index.php', array('categoryid' => $myorg->categoryid)));
            if ($myorg->categoryid != $categoryid) {
                $category = $DB->get_record('course_categories', array('id' => $categoryid));
                $PAGE->navbar->add($category->name, new moodle_url('/course/index.php', array('categoryid' => $categoryid)));
            }
        }

        if ($courseid > 1) {
            $PAGE->set_context(context_course::instance($courseid));
            $PAGE->set_pagelayout('course');
            try {  $PAGE->set_course(get_course($courseid)); } catch(Exception $e) {}
        }

        //$PAGE->navbar->add('manage', $PAGE->url);
    }
    public static function set_is_app($force=0) {
        global $PAGE;
        $cache = cache::make('block_eduvidual', 'appcache');
        // Originallocation is sent by app
        $originallocation = optional_param('originallocation', '', PARAM_TEXT);
        if (!empty($originallocation) && $originallocation != '') {
            block_eduvidual::$originallocation = $originallocation;
            $cache->set('originallocation', $originallocation);
        }
    }
    public static function print_app_header() {
        global $CFG, $org, $OUTPUT, $PAGE, $USER;
        $PAGE->requires->css('/blocks/eduvidual/style/jqm-icon-pack-fa.css');
        $PAGE->requires->css('/blocks/eduvidual/style/main.css');
        $PAGE->requires->css('/blocks/eduvidual/style/spinner.css');
        $PAGE->requires->css('/blocks/eduvidual/style/ui.css');
        // Determine layout from cache > param > default
        $cache = cache::make('block_eduvidual', 'appcache');
        $layout = $cache->get('layout');
        $layout = optional_param('layout', $layout, PARAM_TEXT);
        $availablelayouts = array('mydashboard', 'incourse', 'embedded', 'popup');
        if (!in_array($layout, $availablelayouts)) {
            $layout = $PAGE->pagelayout;
        }
        $cache->set('layout', $layout);
        if (!empty(optional_param('layout', $layout, PARAM_TEXT)) && $layout != $PAGE->pagelayout) {
            block_eduvidual::$pagelayout = $layout;
            // Possibly we are in a state where we can not change the pagelayout.
            try { $PAGE->set_pagelayout($layout); } catch(Exception $e) {}
        }
        //die($PAGE->pagelayout);

        //if ($layout !== 'embedded') {
            echo $OUTPUT->header();
        //}

        //echo '<div class="spinner-grid"><div /><div /><div /><div /></div>';
        echo "<div class=\"ui-eduvidual\">";
    }
    /**
     * Show a selector for all organistions a user has
     * @param role Role that is required ('Teacher', 'Manager', '*')
     * @param orgid org that should be selected. if not given tries to retrieve orgid via optional_param.
    **/
    public static function print_org_selector($role = '*', $orgid = 0) {
        global $DB,$PAGE;
        $orgs = block_eduvidual::get_organisations($role);
        $act = optional_param('act', '', PARAM_TEXT);
        if ($orgid == 0) {
            $orgid = optional_param('orgid', 0, PARAM_INT);
        }
        if ($orgid > 0) {
            $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid));
        } else {
            $org = new stdClass();
            $org->orgid = 0;
            $org->name = get_string('none');
        }
        $parts = parse_url($PAGE->url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo "\t<select onchange=\"var sel = this; require(['block_eduvidual/main'], function(MAIN) { MAIN.navigate('" . $url . "?act=" . $act . "&orgid=' + sel.value); });\">\n";
        foreach($orgs AS $org) {
            echo "\t\t<option value=\"" . $org->orgid . "\"" . (($orgid == $org->orgid)?' selected':'') . ">" . $org->orgid . " | " . $org->name . "</option>\n";
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
            if (get_string_manager()->string_exists($actions[$act], 'block_eduvidual')) {
                $action = get_string($actions[$act], 'block_eduvidual');
            } else {
                $action = '[[' . $actions[$act] . ']]';
            }
        }
        $parts = parse_url($PAGE->url);
        $url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];
        echo "\t<select onchange=\"var sel = this; require(['block_eduvidual/main'], function(MAIN) { MAIN.navigate('" . $url . "?orgid=" . $orgid . "&act=' + sel.value); });\">\n";
        $keys = array_keys($actions);
        foreach($keys AS $key) {
            echo "\t\t<option value=\"" . $key . "\"" . (($key == $act)?' selected':'') . ">" . get_string($actions[$key], 'block_eduvidual') . "</option>\n";
        }
        echo "\t</select>\n";
    }
    public static function print_app_footer() {
        global $CFG, $OUTPUT;
        echo "</div>"; // close div with class ui-eduvidual
        if (count(block_eduvidual::$scripts_on_load) > 0) {
            ?>
            <script type="text/javascript" class="block_eduvidual scripts_on_load">
            <?php
                // ATTENTION, we should not use localstorage directly because of safari quota exceeded-bug
                // Tested in app - should work.
                // Store referrer in localstorage if we came from outside.
                if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $CFG->wwwroot) === FALSE) {
                    ?>
                    //localStorage.setItem('block_eduvidual_referer', '<?php echo $_SERVER['HTTP_REFERER']; ?>');
                    <?php
                }
                $embedded = !empty(block_eduvidual::$pagelayout) && block_eduvidual::$pagelayout == 'embedded';
                ?>
                //localStorage.setItem('block_eduvidual_isembedded', <?php echo ($embedded) ? 1 : 0; ?>);
                <?php
            ?>
            window.addEventListener("load", function() {
                <?php
                echo implode("\n", block_eduvidual::$scripts_on_load);
                ?>
            });
            </script>
            <?php
        }
        //if ($PAGE->pagelayout !== 'embedded') {
            echo $OUTPUT->footer();
        //}
    }
    /**
     * Store a piece of javascript that should be executed after site has loaded
    **/
    public static function add_script_on_load($script) {
        block_eduvidual::$scripts_on_load[] = $script;
    }
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

    public function init() {
        $this->title = get_string('pluginname', 'block_eduvidual');
    }
    public function get_content() {
        global $PAGE;
        $PAGE->requires->css('/blocks/eduvidual/style/main.css');
        if ($this->content !== null) {
          return $this->content;
        }

        $this->content = new stdClass;
        $this->content->title = "";
        $this->content->text  = '';
        $options = block_eduvidual::get_mainmenu_options();
        foreach($options AS $option) {
            $tx = $option["title"];
            if (!empty($option["icon"])) $tx = "<img src='" . $option["icon"] . "' class='icon'>" . $tx;
            if (!empty($option["href"])) $tx = "
                <a href='" . $option["href"] . "' " . ((!empty($option["onclick"])) ? " onclick=\"" . $option["onclick"] . "\"" : "") . "
                   class='btn' " . ((!empty($option["target"])) ? " target=\"" . $option["target"] . "\"" : "") . "'>" . $tx . "</a>";
            else  $tx = "<a class='btn'>" . $tx . "</a>";
            $this->content->text .= $tx;
        }
        /* FOR block_list
        $this->content->items  = array();
        $options = block_eduvidual::get_mainmenu_options();
        foreach($options AS $option) {
            if (isset($option["href"])) {
                $this->content->items[] = html_writer::tag('a', $option["title"], $option); // array('href' => $option["href"])
                $this->content->icons[] = html_writer::empty_tag('img', array('src' => $option["icon"], 'class' => 'icon'));
            } else {
                if (!isset($option["class"])) { $option["class"] = 'divider'; }
                if (!isset($option["target"])) { $option["target"] = '_self'; }
                $this->content->items[] = html_writer::tag('div', $option["title"], array('class' => $option["class"], 'target' => $option["target"]));
                $this->content->icons[] = '';
            }
        }
        */

        $blockfooter = get_config('block_eduvidual', 'blockfooter');
        if (!empty($blockfooter)) {
            $this->content->footer = '<div id="block_eduvidual_footer">' . $blockfooter . '</div>';
        }

        return $this->content;
    }
    public function hide_header() {
        return true;
    }
    public function has_config() {
        return true;
    }
}
