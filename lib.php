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

function local_eduvidual_after_config() {
    global $CFG, $DB, $PAGE, $USER;

    \local_eduvidual\locallib::set_xorg_data();

    if ($CFG->autologinguests && $CFG->theme == 'edumaker') {
        // keine Weiterleitung zu login bei neuem theme, gäste werden automatisch eingeloggt
    } elseif (!isloggedin() && $_SERVER["SCRIPT_FILENAME"] == $CFG->dirroot . '/index.php') {
        // Fore redirect to login from frontpage.
        redirect($CFG->wwwroot . '/login');
    }

    $PAGE->add_body_class('theme-' . $CFG->theme);
    // Check for particular scripts, whose output has to be protected.
    $scripts = array(
        '/enrol/manual/manage.php', '/mod/jazzquiz/edit.php', '/mod/activequiz/edit.php',
        '/question/category.php', '/question/edit.php',
        '/question/export.php', '/user/selector/search.php'
    );
    $script = str_replace($CFG->dirroot, '', $_SERVER["SCRIPT_FILENAME"]);
    if (in_array($script, $scripts)) {
        \local_eduvidual\lib_wshelper::buffer();
    }

    // data privacy request for deletion only allowed when not member in any org.
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/admin/tool/dataprivacy/createdatarequest.php') > 0) {
        $type = optional_param('type', 0, PARAM_INT);
        if ($type == 2) {
            $orgs = \local_eduvidual\locallib::get_organisations('*', false);
            if (count($orgs) > 0) {
                $url = new \moodle_url('/local/eduvidual/pages/redirects/dataprivacyorgerror.php', array());
                redirect($url);
            }
        }
    }

    // Protect core question bank from being exported.
    if (!is_siteadmin() && strpos($_SERVER["SCRIPT_FILENAME"], '/question/export.php') > 0) {
        $category = optional_param('category', 0, PARAM_RAW);
        if (!empty($category)) {
            $category = explode(',', $category);
            $ctx = \context_system::instance();
            if (count($category) > 1 && $category[1] == $ctx->id) {
                $_POST['category'] = '';
                $_GET['category'] = '';
            }
        }
    }

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/view.php') > 0
        || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/guestlink.php') > 0
        || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_ajax.php') > 0
        || strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_view.php') > 0
        || strpos($_SERVER["SCRIPT_FILENAME"], '/webservice/rest/server.php') > 0 && optional_param('component', '', PARAM_TEXT) == 'mod_bigbluebuttonbn') {
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/guestlink.php') > 0) {
            // get cmid dependent on guestlinkid (gid)
            $gid = optional_param('gid', '', PARAM_ALPHANUM);
            $bbb = $DB->get_record('bigbluebuttonbn', array('guestlinkid' => $gid));
            if ($bbb->guestlinkenabled) {
                list($course, $cm) = get_course_and_cm_from_instance($bbb, 'bigbluebuttonbn');
                if (!empty($cm->id)) {
                    $cmid = $cm->id;
                }
            }
        } elseif(strpos($_SERVER["SCRIPT_FILENAME"], '/mod/bigbluebuttonbn/bbb_ajax.php') > 0) {
            $bbbtn = optional_param('bigbluebuttonbn', 0, PARAM_INT);
            $bbb = $DB->get_record('bigbluebuttonbn', array('id' => $bbbtn));
            list($course, $cm) = get_course_and_cm_from_instance($bbb, 'bigbluebuttonbn');
            if (!empty($cm->id)) {
                $cmid = $cm->id;
            }
        } elseif(strpos($_SERVER["SCRIPT_FILENAME"], '/webservice/rest/server.php') > 0) {
            $params = array_merge($_GET, $_POST);
            $args = $params['args'];
            if (!empty($args)) {
                foreach ($args as $i => $arg) {
                    if (!empty($arg['name']) && $arg['name'] == 'cmid') {
                        $cmid = $arg['value'];
                    }
                }
            }
        } else {
            $cmid = optional_param('id', 0, PARAM_INT);
        }
        if (!empty($cmid)) {
            $cm = get_coursemodule_from_id('bigbluebuttonbn', $cmid, 0, false, IGNORE_MISSING);
            if (!empty($cm->course)) {
                $course = $DB->get_record('course', array('id' => $cm->course), '*', IGNORE_MISSING);
                if (!empty($course->id)) {
                    $org = \local_eduvidual\locallib::get_org_by_courseid($course->id);
                    if (!empty($org->orgid)) {
                        $bbb_serverurl = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_server_url'));
                        $bbb_sharedsecret = $DB->get_record('local_eduvidual_overrides', array('orgid' => $org->orgid, 'field' => 'bigbluebuttonbn_shared_secret'));
                        if (!empty($bbb_serverurl->value) && !empty($bbb_sharedsecret->value)) {
                            $CFG->bigbluebuttonbn['server_url'] = $bbb_serverurl->value;
                            $CFG->{'bigbluebuttonbn_server_url'} = $bbb_serverurl->value;
                            $CFG->bigbluebuttonbn['shared_secret'] = $bbb_sharedsecret->value;
                            $CFG->{'bigbluebuttonbn_shared_secret'} = $bbb_sharedsecret->value;
                        }
                    }
                }
            }
        }
    }
}

function local_eduvidual_after_require_login() {

}

function local_eduvidual_before_standard_html_head() {
    global $CFG, $CONTEXT, $COURSE, $DB, $OUTPUT, $PAGE, $USER;

    $RET = [];

    // Protect question banks on course level.
    if (!empty($PAGE->context->contextlevel) && $PAGE->context->contextlevel == CONTEXT_COURSE) {
        if (strpos($_SERVER["SCRIPT_FILENAME"], '/question/edit.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/question/category.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/question/import.php') > 0
            || strpos($_SERVER["SCRIPT_FILENAME"], '/question/export.php') > 0) {
            if (!\local_eduvidual\locallib::can_access_course_questionbank($PAGE->context)) {
                throw new \required_capability_exception($PAGE->context, 'moodle/question:viewall', get_string('access_denied', 'local_eduvidual'), '');
            }
        }
    }

    // Direct JS commands.
    $PAGE->requires->js('/local/eduvidual/js/direct.js');
    $PAGE->requires->js('/local/eduvidual/js/ajax_observer.js');

    // Main styles for eduvidual.
    if (!\local_eduvidual\locallib::is_moodle_4()) {
        $PAGE->requires->css('/local/eduvidual/style/main.css');
        $PAGE->requires->css('/local/eduvidual/style/spinner.css');
        //$PAGE->requires->css('/local/eduvidual/style/ui.css');
        // General boost-modifications.
        $PAGE->requires->css('/local/eduvidual/style/theme_boost.css');

	    // Wenn das neue edumaker theme (August 2022) aktiv ist, sollen die Anpassungen für theme boost_campus im theme_39.css nicht geladen werden
		if ($CFG->theme != 'edumaker') {
			$PAGE->requires->css('/local/eduvidual/style/theme_39.css');
		}
    }

    // The default banner needs to be injected via Internal Stylesheet to
    // assure the correct absolute path to the image.
    $RET[] = implode("\n", [
        '<style>',
        '    body.theme-boost #page-header .card, body.theme-boost_campus #page-header .card {',
        '        background-image: url("' . $CFG->wwwroot . '/local/eduvidual/pix/banner-curve.jpg");',
        '    }',
        '</style>',
    ]);

    $org = \local_eduvidual\locallib::get_org_by_context();

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/otherusers.php') > 0) {
        redirect($CFG->wwwroot . '/user/index.php?id=' . optional_param('id', 0, PARAM_INT));
    }
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/login/signup.php') > 0) {
        echo $OUTPUT->render_from_template('local_eduvidual/inject', [ 'signupPage' => 1]);
    }
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/edit.php') > 0) {
        echo $OUTPUT->render_from_template('local_eduvidual/inject', [ 'courseEditPage' => 1, 'userid' => $USER->id, 'issiteadmin' => is_siteadmin()]);
    }

    $data = array(
        'context' => $CONTEXT,
        'course' => (object) array(
            'id' => $COURSE->id,
            'contextid' => $PAGE->context->id,
        ),
    );
    $PAGE->requires->js_call_amd("local_eduvidual/jsinjector", "run", array($data));


    $RET[] = \local_eduvidual\lib_helper::orgmenus_rendered();

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/delete.php') > 0) {
        $PAGE->requires->js_call_amd("local_eduvidual/jsinjector", "modifyRedirectUrl", array('coursedelete'));
    }

    // Now inject organisation-specific resources.
    $inject_styles = array("<style type=\"text/css\" id=\"local_eduvidual_style_userextra\">");
    $background = get_user_preferences('local_eduvidual_background');
    if (!isguestuser($USER) && !empty($background)) {
        $inject_styles[] = "body { background: url(" . $CFG->wwwroot . $background . ") no-repeat center center fixed; background-size: cover !important; }";
    }
    if (!empty($extra->background)) {
        $inject_styles[] = "body { background-image: url(" . $CFG->wwwroot . $extra->background . "); background-position: center; background-size: cover; }";
    }
    $inject_styles[] = "</style>";

    $inject_styles[] = "<style type=\"text/css\" id=\"local_eduvidual_style_org\">";
    if (!empty($org->customcss)) {
        $inject_styles[] = $org->customcss;
    }
    if (!empty($org->banner)) {
        $inject_styles[] = "body #page-header .card { background-image: url(" . $CFG->wwwroot . $org->banner . ") !important; }";
    }
    $inject_styles[] = "</style>";

    // Disabled, needs performance tests.
    // \local_eduvidual\lib_helper::fix_navbar();

    $RET[] = implode("\n", $inject_styles);

    // Favicon & Apple Touch Icons
    $RET[] = '<link rel="icon" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/favicon.ico" type="image/x-icon">';
    $RET[] = '<link rel="apple-touch-icon" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/apple-touch-icon.png">';
    $sizes = [ '57x57', '72x72', '76x76', '114x114', '120x120', '144x144', '152x152', '180x180' ];
    foreach ($sizes as $size) {
        $RET[] = '<link rel="apple-touch-icon" sizes="' . $size . '" href="' . $CFG->wwwroot . '/local/eduvidual/pix/favicons/apple-touch-icon-57x57.png">';
    }



    return implode("\n", $RET);
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
function local_eduvidual_extend_navigation($navigation) {
    $highestrole = \local_eduvidual\locallib::get_highest_role();
    if (empty($highestrole)) return;

    $nodehome = $navigation->get('home');
    if (empty($nodehome)){
        $nodehome = $navigation;
    }

    if (in_array($highestrole, array('Manager', 'Teacher'))) {
        $label = get_string('createcourse:here', 'local_eduvidual');
        $link = new moodle_url('/local/eduvidual/pages/createcourse.php', array());
        $icon = new pix_icon('create-course', '', 'local_eduvidual');
        $nodecreatecourse = $nodehome->add($label, $link, navigation_node::NODETYPE_LEAF, $label, 'createcourse', $icon);
        $nodecreatecourse->showinflatnavigation = true;
    }

    $label = get_string('Browse_org', 'local_eduvidual');
    $link = new moodle_url('/local/eduvidual/pages/myorgs.php');
    $icon = new pix_icon('my-orgs', '', 'local_eduvidual');
    $nodemyorgs = $nodehome->add($label, $link, navigation_node::NODETYPE_LEAF, $label, 'browseorgs', $icon);
    $nodemyorgs->showinflatnavigation = true;
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
 * Extend frontpage navigation.
 */
function local_eduvidual_extend_navigation_frontpage($parentnode, $course, $context) {
}

/**
 * Extend User Profile Page.
 */
function local_eduvidual_extend_navigation_user($parentnode, $user, $context, $course, $coursecontext) {

}

/**
 * Extend User Settings Page.
 */
function local_eduvidual_extend_navigation_user_settings($nav, $user, $context, $course, $coursecontext) {
    global $DB, $USER;
    if (!isguestuser($user)) {
        $node = $nav->add_node(navigation_node::create(get_string('pluginname', 'local_eduvidual')));
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
        foreach ($memberships AS $membership) {
            $org = \local_eduvidual\locallib::get_org('orgid', $membership->orgid);
            if (empty($org->id)) continue;
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
        'core_user_get_users', 'tool_lp_search_cohorts', 'tool_lp_search_users'
    );
    $func = $function->classname . '_' . $function->methodname;
    if ($CFG->debug == 32767) error_log($func);
    if (in_array($func, $supported)) {
        global $CFG;
        require_once($CFG->dirroot . '/local/eduvidual/classes/lib_wshelper.php');
        if ($CFG->debug == 32767) error_log('Overriding ' . $func);
        return \local_eduvidual\lib_wshelper::override($function->classname, $function->methodname, $params);
    }
    return false;
}

/**
 * Can we use this to remove membership in organisations?
 */
function local_eduvidual_pre_user_delete() {

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
function local_eduvidual_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    $areas = array('backgrounds', 'backgrounds_cards', 'globalfiles', 'orgfiles', 'orgbanner', 'module');
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
    /*
    ** UPDATE: We will not restrict this anymore, otherwise courses with guest access will not show the correct styling!
    $restrict_to_org = array('orgfiles', 'orgbanner');
    if (in_array($filearea, $restrict_to_org)) {
        global $CFG;

        $orgs = \local_eduvidual\locallib::get_organisations('*');
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
    */

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
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

    $entry = (object) [
        'courseid'    => $course->id,
        'categoryid'  => $course->category,
        'fullname'    => $course->fullname,
        'shortname'   => $course->shortname,
        'userid'      => $USER->id,
        'timedeleted' => time(),
    ];

    $DB->insert_record('local_eduvidual_coursedelete', $entry);
}
