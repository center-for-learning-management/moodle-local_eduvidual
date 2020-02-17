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

function block_eduvidual_before_standard_html_head() {
    global $CFG, $CONTEXT, $COURSE, $DB, $PAGE, $USER;

    if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/otherusers.php') > 0) {
        redirect($CFG->wwwroot . '/user/index.php?id=' . optional_param('id', 0, PARAM_INT));
    }

    // We want to get rid of bufferedmode.
    if (get_config('block_eduvidual', 'bufferedmode')) {
        require_once($CFG->dirroot . '/blocks/eduvidual/buffered_mode.php');
    }

    $data = array(
        'context' => $CONTEXT,
        'course' => (object) array(
            'id' => $COURSE->id,
            'contextid' => $PAGE->context->id,
        ),
    );
    $PAGE->requires->js_call_amd("block_eduvidual/jsinjector", "run", array($data));
    // General boost-modifications.
    $PAGE->requires->css('/blocks/eduvidual/style/theme_boost.css');
    if (strpos($_SERVER["SCRIPT_FILENAME"], '/course/delete.php') > 0) {
        $PAGE->requires->js_call_amd("block_eduvidual/jsinjector", "modifyRedirectUrl", array('coursedelete'));
    }

    // Now inject specific resources.
    $injects = array();
    $injects[] = "<script type=\"text/javascript\" src=\"" . $CFG->wwwroot . "/blocks/eduvidual/js/ajax_observer.js\"></script>";

    $inject_styles = array("<style type=\"text/css\" id=\"block_eduvidual_style_userextra\">");

    if ($_SERVER['SCRIPT_FILENAME'] == $CFG->dirroot . '/index.php') {
    }

    if (!empty(block_eduvidual::get('orgbanner'))) {
        $inject_styles[] = "body #page-header .card { background-image: url(" . block_eduvidual::get('orgbanner') . ") !important; }";
    }

    // @TODO echo goes here into the head of the page, not the body!!!
    // Check if we have selected a moo-level if required.
    if ($USER->id > 0 && !isguestuser($USER) && in_array(block_eduvidual::get('role'), array('Teacher', 'Manager', 'Administrator'))) {
        if (in_array(\block_eduvidual::get('role'), array('Administrator', 'Manager', 'Teacher'))) {
            $valid_moolevels = explode(',', get_config('block_eduvidual', 'moolevels'));
            if (count($valid_moolevels) > 0) {
                $context = \context_system::instance();
                $roles = get_user_roles($context, $USER->id, true);
                $found = false;
                foreach ($roles AS $role) {
                    if (in_array($role->roleid, $valid_moolevels)) {
                        $found = true;
                    }
                }
                if (!$found && strpos($_SERVER["SCRIPT_FILENAME"], '/blocks/eduvidual/pages/preferences.php') <= 0) {
                    redirect($CFG->wwroot . '/blocks/eduvidual/pages/preferences.php?act=moolevelinit');
                }
            }
        }
    }

    // Insert user-extra.
    $extra = block_eduvidual::get('userextra');
    if (!isguestuser($USER) && isset($extra->background) && !empty($extra->background)) {
        $inject_styles[] = "html { background: url(" . $extra->background . ") no-repeat center center fixed !important; background-size: cover !important; }";
    }

    // Check if we are allowed to access this page.
    $targetctx = $DB->get_record('context', array('id' => $PAGE->context->id));
    $path = explode('/', $targetctx->path);
    if (count($path) > 1) {
        $orgctx = $DB->get_record('context', array('id' => $path[2]));
        $org = $DB->get_record('block_eduvidual_org', array('categoryid' => $orgctx->instanceid));
        $protectedorgs = explode(',', get_config('block_eduvidual', 'protectedorgs'));
    }

    if (!empty($org->orgid) && !in_array($org->orgid, $protectedorgs)) {
        // This is an org and it does not belong to protectedorgs.
        // Perhaps user can access particular courses, but never coursecategories!

        $canaccess = false;
        $ctx = \context_coursecat::instance($orgctx->instanceid);
        if (has_capability('block/eduvidual:canaccess', $ctx)) {
            $canaccess = true;
        } elseif ($coursectx->contextlevel >= CONTEXT_COURSE) {
            // You have a second chance. Is that a course for guests or are you enrolled?
            $coursectx = $targetctx;
            while(!$coursectx->contextlevel >= CONTEXT_COURSE) {
                $path = explode('/'. $coursectx->path);
                $parentid = $path[count($path) - 2];
                $coursectx = $DB->get_record('context', array('id' => $parentid));
            }
            $ctx = \context_course::instance($coursectx->instanceid);
            $canaccess = is_enrolled($ctx, $USER, '', true);
        }

        if (!$canaccess) {
            if (is_siteadmin()) {
                if (!empty($COURSE->id) && $COURSE->id > 0) {
                    $html = '<p class="alert alert-danger accessbox hide-on-print" style="position: absolute; z-index: 9999;">';
                    $html .= '<a href="#" onclick="$(this).parent().css(\'display\', \'none\')">';
                    $html .= get_string('access_only_because_admin_course', 'block_eduvidual');
                    $html .= '</a></p>';
                    $injects[] = $html;
                } else {
                    $html = '<p class="alert alert-danger accessbox hide-on-print" style="position: absolute; z-index: 9999;">';
                    $html .= '<a href="#" onclick="$(this).parent().css(\'display\', \'none\')">';
                    $html .= get_string('access_only_because_admin_category', 'block_eduvidual');
                    $html .= '</a></p>';
                    $injects[] = $html;
                }
            } elseif($PAGE->context->contextlevel == CONTEXT_COURSECAT) {
                block_eduvidual::redirect_privacy_issue('context-' . $PAGE->context->id);
            }
        }
    }

    $customstyle = block_eduvidual::get('customcss');
    if ($customstyle != "") {
        $inject_styles[] = $customstyle;
    }

    $inject_styles[] = "</style>";
    $inject_styles[] = "<style type=\"text/css\" id=\"block_eduvidual_style_userextra\">
                    body { background-image: url(" . $extra->background . "); background-position: center; background-size: cover; }
                  </style>";
    $injects = array_merge($injects, $inject_styles);
    //$PAGE->requires->js_call_amd('block_eduvidual/ajax_observer', 'observe');
    return implode("\n", $injects);
}

// Will work since Moodle 3.6
function block_eduvidual_control_view_profile($user, $course = null, $usercontext = null) {
    // Check here if we can view this users profile.
    if (!block_eduvidual::is_connected($user->id)) {
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
        $node = $nav->find('courseadmin', null);   // 'courseadmin' is the menu key
        $nav->add(get_string('deletecourse'), new moodle_url('/course/delete.php?id=' . $course->id));
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
    $node->add(get_string('preferences:explevel', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/preferences.php?act=moolevel'));
    $node->add(get_string('preferences:questioncategories', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/preferences.php?act=qcats'));

    $users = $DB->get_records('user', array('email' => $USER->email, 'suspended' => 0));
    if (count($users) > 0) {
        $node->add(get_string('user:merge_accounts', 'block_eduvidual'), new moodle_url('/blocks/eduvidual/pages/user_merge.php'));
    }
}

/**
 * Override the return value of some webservice functions.
 */
// Will work since Moodle 3.6
function block_eduvidual_override_webservice_execution($function, $params) {
    if ($function->name === 'whatever') {
        $result = call_user_func_array([$function->classname, $function->methodname], $params);

        // Now modify $result.
        return $result;
    }
    // Implement that we do not list the global support-course in messages area of user.

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
