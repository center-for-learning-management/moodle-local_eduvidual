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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 *             2020 onwards Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// can be removed in moodle 4.5
function local_eduvidual_after_config() {
    \local_eduvidual\hook_callbacks::after_config();
}

// Will work since Moodle 3.6
function local_eduvidual_control_view_profile($user, $course = null, $usercontext = null) {
    // Check here if we can view this users profile.
    if (!\local_eduvidual\locallib::is_connected($user->id) && !is_siteadmin()) {
        return core_user::VIEWPROFILE_PREVENT;
    }
    return core_user::VIEWPROFILE_DO_NOT_PREVENT;
}

/**
 * Extend Moodle Navigation.
 */
function local_eduvidual_extend_navigation(global_navigation $navigation) {
    global $CFG;

    $custommenu = '';
    $highestrole = \local_eduvidual\locallib::get_highest_role();

    if ($highestrole) {
        $custommenu .= get_string('browse_org', 'local_eduvidual') . "|/local/eduvidual/pages/myorgs.php\n";

        if (in_array($highestrole, array('Manager', 'Teacher'))) {
            $custommenu .= get_string('createcourse:here', 'local_eduvidual') . "|/local/eduvidual/pages/createcourse.php\n";
        }
    }

    if (class_exists(\block_edupublisher\lib::class)) {
        $custommenu .= get_string('resource_catalogue', 'block_edupublisher') . "|/blocks/edupublisher/pages/search.php\n";
    }

    if ($highestrole) {
        if (class_exists(\local_edusupport\lib::class) && \local_edusupport\lib::is_supportteam()) {
            $custommenu .= get_string('issues', 'local_edusupport') . "|/local/edusupport/issues.php\n";
        }

        if (is_siteadmin()) {
            $custommenu .= 'eduvidual-Administration' . "|/admin/category.php?category=local_eduvidual\n";
        }
        $custommenu .= "Edutube|/local/eduvidual/pages/redirects/edutube.php\n";
    }

    $CFG->custommenuitems = $custommenu . $CFG->custommenuitems;
}

/**
 * Extend course settings
 */
function local_eduvidual_extend_navigation_category_settings($nav, $context) {
    global $DB;
    $org = \local_eduvidual\locallib::get_org_by_context($context->id);
    if (!empty($org->orgid) && (\local_eduvidual\locallib::get_orgrole($org->orgid) == 'Manager' || is_siteadmin())) {
        $label = get_string('Management', 'local_eduvidual');
        $link = new moodle_url('/local/eduvidual/pages/manage.php', array('orgid' => $org->orgid));
        $icon = new pix_icon('/t/gears', '', '');
        $nodecreatecourse = $nav->add($label, $link, navigation_node::NODETYPE_LEAF, $label, 'eduvidualmanagement', $icon);
        $nodecreatecourse->showinflatnavigation = true;
    }
}

/**
 * Extend course settings
 */
function local_eduvidual_extend_navigation_course($nav, $course, $context) {
    global $DB, $USER;
    $coursecontext = \context_course::instance($course->id);
    if (has_capability('moodle/course:delete', $coursecontext)) {
        //$node = $nav->find('courseadmin', null);   // 'courseadmin' is the menu key
        $nav->add(get_string('deletecourse'), new moodle_url('/course/delete.php?id=' . $course->id));
    }
    if (\local_eduvidual\locallib::is_manager($course->category)) {
        $nav->add(get_string('manage:enrolmeasteacher', 'local_eduvidual'), new \moodle_url('/local/eduvidual/pages/redirects/forceenrol.php', array('courseid' => $course->id)));
    }
    if ($otherusers = $nav->find('otherusers', global_navigation::TYPE_SETTING)) {
        $otherusers->remove();
    }

    // We try to find out if we can see the question bank. We only want access to it,
    // if the capability was given in the course context itself - not parent contexts!!!
    if (!\local_eduvidual\locallib::can_access_course_questionbank($coursecontext)) {
        if ($node = $nav->find('questionbank', global_navigation::TYPE_CONTAINER)) {
            $node->remove();
        }
    }
}

/**
 * Extend User Settings Page.
 */
function local_eduvidual_extend_navigation_user_settings($nav, $user, $context, $course, $coursecontext) {
    global $DB, $USER;
    if (!isguestuser($user)) {
        $node = $nav->add_node(navigation_node::create(get_string('pluginname', 'local_eduvidual')));

        $is_on = get_user_preferences('local_experience_level', 0) == 1;

        $node->add(get_string('advanced_options', 'local_experience') . ': ' . get_string($is_on ? 'on' : 'off', 'mnet'), new moodle_url('/local/experience/pages/advanced_options.php'));

        //print_r($nav);die();
        //$nav->add(get_string('test'), new moodle_url('/local/eduvidual/pages/preferendes.php'));
        $node->add(get_string('preferences:selectbg:title', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/preferences.php', array('act' => 'backgrounds', 'userid' => $user->id)));
        $sysctx = \context_system::instance();
        if (has_capability('moodle/question:viewall', $sysctx, $user)) {
            $node->add(get_string('preferences:questioncategories', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/preferences.php', array('act' => 'qcats', 'userid' => $user->id)));
        }

        $node->add(get_string('user:merge_accounts', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/user_merge.php'));
    }

}

/**
 * Extend users profile
 */
function local_eduvidual_myprofile_navigation($tree, $user, $iscurrentuser, $course) {
    global $CFG, $DB;
    $category = new \core_user\output\myprofile\category('eduvidual', get_string('pluginname', 'local_eduvidual'), null);
    $tree->add_category($category);
    if (is_siteadmin()) {
        $node = new \core_user\output\myprofile\node('eduvidual', 'eduvidualsecret', $user->id . '#' . \local_eduvidual\locallib::get_user_secret($user->id));
        $category->add_node($node);
        $memberships = \local_eduvidual\locallib::get_user_memberships($user->id);
        foreach ($memberships as $membership) {
            $org = \local_eduvidual\locallib::get_org('orgid', $membership->orgid);
            if (empty($org->id))
                continue;
            $link = '<a href="' . $CFG->wwwroot . '/local/eduvidual/pages/manage.php?orgid=' . $org->orgid . '">' . $org->name . ' (' . $membership->role . ')</a>';
            $node = new \core_user\output\myprofile\node('eduvidual', 'eduvidualmembership-' . $membership->orgid . '-' . $membership->role, $link);
            $category->add_node($node);
        }
    }

}

/**
 * Override the return value of some webservice functions.
 */
// Will work since Moodle 3.6
function local_eduvidual_override_webservice_execution($function, $params) {
    global $CFG;
    $supported = array(
        'block_exacomp_diggr_get_students_of_cohort', 'core_calendar_external_get_calendar_action_events_by_timesort',
        'core_cohort_add_cohort_members',
        'core_cohort_search_cohorts', 'core_course_external_get_enrolled_courses_by_timeline_classification',
        'core_enrol_external_get_potential_users', 'core_external_get_fragment',
        'core_message_message_search_users', 'core_message_data_for_messagearea_search_users',
        'core_message_search_contacts', 'core_search_get_relevant_users',
        'core_user_get_users', 'tool_lp_search_cohorts', 'tool_lp_search_users',
    );
    $func = $function->classname . '_' . $function->methodname;
    if ($CFG->debug == 32767)
        error_log($func);
    if (in_array($func, $supported)) {
        global $CFG;
        require_once($CFG->dirroot . '/local/eduvidual/classes/lib_wshelper.php');
        if ($CFG->debug == 32767)
            error_log('Overriding ' . $func);
        return \local_eduvidual\lib_wshelper::override($function->classname, $function->methodname, $params);
    }
    return false;
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
function local_eduvidual_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    $areas = array('backgrounds', 'backgrounds_cards', 'globalfiles', 'orgfiles', 'orglogo', 'module');
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

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/' . implode('/', $args) . '/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_eduvidual', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Track deletion requests.
 * @param course the course that will be deleted.
 */
function local_eduvidual_pre_course_delete($course) {
    global $DB, $USER;

    $entry = (object)[
        'courseid' => $course->id,
        'categoryid' => $course->category,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'userid' => $USER->id,
        'timedeleted' => time(),
    ];

    $DB->insert_record('local_eduvidual_coursedelete', $entry);
}
