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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

function block_eduvidual_after_config() {
    global $CFG, $PAGE;
    $PAGE->add_body_class('theme-' . $CFG->theme);
    // Check for particular scripts, whose output has to be protected.
    $scripts = array('/user/selector/search.php');
    $script = str_replace($CFG->dirroot, '', $_SERVER["SCRIPT_FILENAME"]);
    if (in_array($script, $scripts)) {
        \block_eduvidual\lib_wshelper::buffer();
    }
}

function block_eduvidual_after_require_login() {

}

function block_eduvidual_before_standard_html_head() {
    global $CFG, $CONTEXT, $COURSE, $DB, $PAGE, $USER;

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/otherusers.php') > 0) {
        redirect($CFG->wwwroot . '/user/index.php?id=' . optional_param('id', 0, PARAM_INT));
    }
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/login/signup.php') > 0) {
        $PAGE->requires->js_call_amd("block_eduvidual/jsinjector", "signupPage", array());
    }

    $data = array(
        'context' => $CONTEXT,
        'course' => (object) array(
            'id' => $COURSE->id,
            'contextid' => $PAGE->context->id,
        ),
    );
    $PAGE->requires->js_call_amd("block_eduvidual/jsinjector", "run", array($data));
    // Main.css changes some styles for eduvidual.
    $PAGE->requires->css('/blocks/eduvidual/style/main.css');
    // General boost-modifications.
    $PAGE->requires->css('/blocks/eduvidual/style/theme_boost.css');
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/delete.php') > 0) {
        $PAGE->requires->js_call_amd("block_eduvidual/jsinjector", "modifyRedirectUrl", array('coursedelete'));
    }

    // If we are going to build a organisation-specific mainmenu-extension, the following will be useful:
    // $PAGE->set_headingmenu('something');

    // Now inject organisation-specific resources.
    $PAGE->requires->js('/blocks/eduvidual/js/ajax_observer.js');
    $inject_styles = array("<style type=\"text/css\" id=\"block_eduvidual_style_userextra\">");
    block_eduvidual::determine_org();
    if (!empty(block_eduvidual::get('orgbanner'))) {
        $inject_styles[] = "body #page-header .card { background-image: url(" . block_eduvidual::get('orgbanner') . ") !important; }";
    }
    $background = get_user_preferences('block_eduvidual_background');
    if (!isguestuser($USER) && !empty($background)) {
        $inject_styles[] = "body { background: url(" . $background . ") no-repeat center center fixed; background-size: cover !important; }";
    }
    $customstyle = block_eduvidual::get('customcss');
    if ($customstyle != "") {
        $inject_styles[] = $customstyle;
    }
    if (!empty($extra->background)) {
        $inject_styles[] = "body { background-image: url(" . $extra->background . "); background-position: center; background-size: cover; }";
    }
    $inject_styles[] = "</style>";
    return implode("\n", $injects_styles);
}


// Will work since Moodle 3.6
function block_eduvidual_control_view_profile($user, $course = null, $usercontext = null) {
    // Check here if we can view this users profile.
    if (!\block_eduvidual\locallib::is_connected($user->id) && !is_siteadmin()) {
        return core_user::VIEWPROFILE_PREVENT;
    }
    return core_user::VIEWPROFILE_DO_NOT_PREVENT;
}

/**
 * Extend course settings
 */
function block_eduvidual_extend_navigation_category_settings($nav, $context) {
}

/**
 * Extend course settings
 */
function block_eduvidual_extend_navigation_course($nav, $course, $context) {
    $coursecontext = \context_course::instance($course->id);
    if (has_capability('moodle/course:delete', $coursecontext)) {
        //$node = $nav->find('courseadmin', null);   // 'courseadmin' is the menu key
        $nav->add(get_string('deletecourse'), new moodle_url('/course/delete.php?id=' . $course->id));
    }
    if (\block_eduvidual\locallib::is_manager($course->category)) {
        $nav->add(get_string('manage:enrolmeasteacher', 'block_eduvidual'), new \moodle_url('/blocks/eduvidual/pages/redirects/forceenrol.php', array('courseid' => $course->id)));
    }
    if ($otherusers = $nav->find('otherusers', global_navigation::TYPE_SETTING)) {
        $otherusers->remove();
    }
}

/**
 * Extend frontpage navigation.
 */
function block_eduvidual_extend_navigation_frontpage($parentnode, $course, $context) {
}

/**
 * Extend User Profile Page.
 */
function block_eduvidual_extend_navigation_user($parentnode, $user, $context, $course, $coursecontext) {

}

/**
 * Extend User Settings Page.
 */
function block_eduvidual_extend_navigation_user_settings($nav, $user, $context, $course, $coursecontext) {
    global $DB, $USER;
    $node = $nav->add_node(navigation_node::create(get_string('pluginname', 'block_eduvidual')));
    //print_r($nav);die();
    //$nav->add(get_string('test'), new moodle_url('/blocks/eduvidual/pages/preferendes.php'));
    $node->add(get_string('preferences:selectbg:title', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/preferences.php?act=backgrounds'));
    if (has_capability('moodle/question:viewall', $context)) {
        $node->add(get_string('preferences:questioncategories', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/preferences.php?act=qcats'));
    }

    $users = $DB->get_records('user', array('email' => $USER->email, 'suspended' => 0));
    if (count($users) > 0) {
        $node->add(get_string('user:merge_accounts', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/user_merge.php'));
    }
}

/**
 * Extend users profile
 */
function block_eduvidual_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
    global $DB;
    $category = new \core_user\output\myprofile\category('eduvidual', get_string('pluginname', 'block_eduvidual'), null);
    $tree->add_category($category);
    if (is_siteadmin()) {
        $node = new \core_user\output\myprofile\node('eduvidual', 'eduvidualsecret', $user->id . '#' . \block_eduvidual\locallib::get_user_secret($user->id));
        $category->add_node($node);
        $memberships = \block_eduvidual\locallib::get_user_memberships();
        foreach ($memberships AS $membership) {
            $org = $DB->get_record('block_eduvidual_org', array('orgid' => $membership->orgid));
            if (empty($org->id)) continue;
            $link = '<a href="' . $CFG->wwwroot . '/blocks/eduvidual/pages/manage.php?orgid=' . $org->orgid . '">' . $org->name . '</a>';
            $node = new \core_user\output\myprofile\node('eduvidual', 'eduvidualmembership-' . $membership->orgid, $link);
            $category->add_node($node);
        }
    }

}

/**
 * Override the return value of some webservice functions.
 */
// Will work since Moodle 3.6
function block_eduvidual_override_webservice_execution($function, $params) {
    $supported = array(
        'block_exacomp_diggr_get_students_of_cohort', 'core_cohort_add_cohort_members',
        'core_cohort_search_cohorts', 'core_enrol_external_get_potential_users',
        'core_get_fragment',
        'core_message_message_search_users', 'core_message_data_for_messagearea_search_users',
        'core_message_search_contacts', 'core_search_get_relevant_users',
        'core_user_get_users', 'tool_lp_search_cohorts', 'tool_lp_search_users'
    );
    $func = $function->classname . '_' . $function->methodname;
    error_log($func);
    if (in_array($func, $supported)) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_wshelper.php');
        error_log('Overriding ' . $func);
        return \block_eduvidual\lib_wshelper::override($function->classname, $function->methodname, $params);
    }
    return false;
}

/**
 * Can we use this to remove membership in organisations?
 */
function block_eduvidual_pre_user_delete() {

}


/**
 * Serve the files from the MYPLUGIN file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function block_eduvidual_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    $areas = array('backgrounds', 'backgrounds_cards', 'globalfiles', 'orgfiles', 'orgbanner', 'mnetlogo', 'modulecat', 'module');
    if (in_array($filearea, $areas)) {
        $forcedownload = false;
        $options['embed'] = true;
    }

    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    $contexts = array(CONTEXT_BLOCK, CONTEXT_COURSE, CONTEXT_MODULE, CONTEXT_SYSTEM);
    if (!in_array($context->contextlevel, $contexts)) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, $areas)) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    // We do not need that (at least for backgrounds)
    // require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    // We do not need that (at least for backgrounds)
    /*
    if (!has_capability('mod/MYPLUGIN:view', $context)) {
        return false;
    }
    */

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
    $restrict_to_org = array('orgfiles', 'orgbanner');
    if (in_array($filearea, $restrict_to_org)) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
        $orgs = block_eduvidual::get_organisations('*');
        $ok = false;
        foreach($orgs AS $org) {
            if ($org->orgid == $itemid) {
                $ok = true;
            }
        }
        if (!$ok) {
            return false;
        }
    }

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_eduvidual', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
