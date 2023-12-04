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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');

$PAGE->set_url('/local/eduvidual/pages/createcourse.php', array());
//$PAGE->set_cacheable(false);
$PAGE->set_context(context_system::instance());

// Only allow a certain user group access to this page
$allow = array("Manager", "Teacher");
if (!in_array(\local_eduvidual\locallib::get_highest_role(), $allow) && !is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'type' => 'danger',
        'content' => get_string('access_denied', 'local_eduvidual'),
    ));
    echo $OUTPUT->footer();
    exit;
}

// Used to determine if we can teach in this org
$PAGE->set_title(get_string('teacher:createcourse', 'local_eduvidual'));
$PAGE->set_heading(get_string('teacher:createcourse', 'local_eduvidual'));

$formsent = optional_param('formsent', 0, PARAM_INT);
$orgs = \local_eduvidual\locallib::get_organisations('Teacher');

$orgid = optional_param('orgid', 0, PARAM_INT);
$subcat1 = optional_param('subcat1', '', PARAM_TEXT);
$subcat2 = optional_param('subcat2', '', PARAM_TEXT);
$subcat3 = optional_param('subcat3', '', PARAM_TEXT);
$subcat4 = optional_param('subcat4', '', PARAM_TEXT);
$basement = optional_param('basement', '', PARAM_ALPHANUM);

$subcats1 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats1');
$subcats2 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats2', $subcat1);
$subcats3 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats3', $subcat2);

$redirect = '';
$msg = array();

if ($formsent) {
    $org = \local_eduvidual\locallib::get_organisations_check($orgs, $orgid);

    if (empty($subcat1)) {
        $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('createcourse:subcat1emptyerror', 'local_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } elseif (empty($org->id) ||
        !empty($subcat1) && !empty($subcats1) && !in_array($subcat1, $subcats1) ||
        !empty($subcat2) && !empty($subcats2) && !in_array($subcat2, $subcats2) ||
        !empty($subcat3) && !empty($subcats3) && !in_array($subcat3, $subcats3)) {

        $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('missing_permission', 'local_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } else {
        // We can create a course in that org!
        $parts = array();
        if (!empty($subcat2))
            $parts[] = $subcat2;
        if (!empty($subcat3))
            $parts[] = $subcat3;
        if (!empty($subcat4))
            $parts[] = $subcat4;
        $coursename = implode(' ', $parts);
        if (empty(str_replace(' ', '', $coursename))) {
            $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                'content' => get_string('createcourse:coursenameemptyerror', 'local_eduvidual'),
                'url' => $CFG->wwwroot . '/my',
                'type' => 'error',
            ));
        } else {
            $coursename = $subcat1 . ' ' . $coursename;
            $cat1 = $DB->get_record('course_categories', array('parent' => $org->categoryid, 'name' => $subcat1));
            if (empty($cat1->id)) {
                // Create this category!

                $cat1 = (object)array(
                    'name' => $subcat1,
                    'description' => '',
                    'parent' => $org->categoryid,
                    'visible' => 1,
                );
                $cat1 = \core_course_category::create($cat1);
            }
            // If it is still empty - error
            if (empty($cat1->id)) {
                $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                    'content' => get_string('createcourse:catcreateerror', 'local_eduvidual'),
                    'url' => $CFG->wwwroot . '/my',
                    'type' => 'error',
                ));
            } else {
                $targcat = $cat1;
                if (!empty($subcat2)) {
                    //print_r($cat1);
                    $cat2 = $DB->get_record('course_categories', array('parent' => $cat1->id, 'name' => $subcat2));
                    if (empty($cat2->id)) {
                        // Create this category!
                        $cat2 = (object)array(
                            'name' => $subcat2,
                            'description' => '',
                            'parent' => $cat1->id,
                            'visible' => 1,
                        );
                        $cat2 = \core_course_category::create($cat2);
                    }
                    // If it is still empty - error
                    if (empty($cat2->id)) {
                        $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                            'content' => get_string('createcourse:catcreateerror', 'local_eduvidual'),
                            'url' => $CFG->wwwroot . '/my',
                            'type' => 'error',
                        ));
                    } else {
                        $targcat = $cat2;
                    }
                }
                if (!empty($targcat->id)) {
                    // Now check if basement is valid
                    $basementcourseid = 0;
                    switch ($basement) {
                        case 'empty':
                            $basementcourseid = get_config('local_eduvidual', 'coursebasementempty');
                            break;
                        case 'restore':
                            $basementcourseid = get_config('local_eduvidual', 'coursebasementrestore');
                            break;
                        case 'template':
                            $basementcourseid = get_config('local_eduvidual', 'coursebasementtemplate');
                            break;
                    }

                    if (!empty($basementcourseid)) {
                        // Create course here
                        $fullname = $coursename;
                        $categoryid = $targcat->id;
                        $shortname = $org->orgid . '-' . $USER->id . '-' . date('YmdHis');
                        if (strlen($shortname) > 30)
                            $shortname = substr($shortname, 0, 30);

                        if (strlen($fullname) > 5) {
                            // First check if the template is valid.
                            require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
                            $fs = \get_file_storage();
                            $files = $fs->get_area_files(\context_course::instance($basementcourseid)->id, 'local_eduvidual', 'coursebackup', 0, '', false);
                            $files = array_values($files);

                            if (!isset($files[0])) {
                                throw new \moodle_exception('coursebackupnotset', 'local_eduvidual');
                            }

                            // Now create a course.
                            require_once($CFG->dirroot . '/course/lib.php');
                            $data = $DB->get_record('course', array('id' => $basementcourseid));
                            $data->category = $categoryid;
                            $data->fullname = $fullname;
                            $data->shortname = $shortname;
                            $course = \create_course($data);

                            $fp = \get_file_packer('application/vnd.moodle.backup');
                            $backuptempdir = \make_backup_temp_directory('template' . $basementcourseid);
                            $files[0]->extract_to_pathname($fp, $backuptempdir);

                            $settings = [
                                'logs' => false,
                                'grade_histories' => false,
                                'groups' => true,
                                'competencies' => true,
                                'contentbankcontent' => true,
                                'course_shortname' => $course->shortname,
                                'course_fullname' => $course->fullname,
                                'customfields' => true,
                                'overwrite_conf' => true,
                                'users' => false,
                                'keep_roles_and_enrolments' => false,
                                'keep_groups_and_groupings' => false,
                            ];

                            try {
                                // Now restore the course.
                                $target = \backup::TARGET_EXISTING_DELETING;
                                $rc = new \restore_controller('template' . $basementcourseid, $course->id, \backup::INTERACTIVE_NO,
                                    \backup::MODE_IMPORT, $USER->id, $target);

                                foreach ($settings as $settingname => $value) {
                                    $plan = $rc->get_plan();
                                    if (!empty($plan)) {
                                        $setting = $rc->get_plan()->get_setting($settingname);
                                        if ($setting->get_status() == \base_setting::NOT_LOCKED) {
                                            $rc->get_plan()->get_setting($settingname)->set_value($value);
                                        }
                                    }

                                }
                                $rc->execute_precheck();
                                $rc->execute_plan();
                                $rc->destroy();
                            } catch (\Exception $e) {
                                if ($rc) {
                                    \core\notification::error('Restore failed with status: ' . $rc->get_status());
                                }
                                throw $e;
                            } finally {
                                $course->fullname = $fullname;
                                $course->shortname = $shortname;
                                $course->startdate = (date("m") < 6) ? strtotime((date("Y") - 1) . '0901000000') : strtotime(date("Y") . '0901000000');
                                $course->enddate = (date("m") < 6) ? strtotime((date("Y")) . '0831000000') : strtotime((date("Y") + 1) . '0831000000');
                                $course->summary = "";
                                if (!empty($subcat1))
                                    $course->summary .= $org->subcats1lbl . ': ' . $subcat1 . "<br />\n";
                                if (!empty($subcat2))
                                    $course->summary .= $org->subcats2lbl . ': ' . $subcat2 . "<br />\n";
                                if (!empty($subcat3))
                                    $course->summary .= $org->subcats3lbl . ': ' . $subcat3 . "<br />\n";
                                if (!empty($subcat4))
                                    $course->summary .= $org->subcats4lbl . ': ' . $subcat4 . "<br />\n";
                                $DB->update_record('course', $course);
                                rebuild_course_cache($course->id);

                                // Override course settings based on organizational standards.
                                \local_eduvidual\lib_helper::override_coursesettings($course->id);

                                // Enrol user as teacher.
                                $role = get_config('local_eduvidual', 'defaultroleteacher');
                                $enroluser = $USER->id;
                                \local_eduvidual\lib_enrol::course_manual_enrolments(array($course->id), array($enroluser), $role);

                                $redirect = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                                if ($basement == 'restore') {
                                    $coursectx = \context_course::instance($course->id);
                                    $redirect = $CFG->wwwroot . '/backup/restorefile.php?contextid=' . $coursectx->id;
                                }

                                $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                                    'content' => get_string('createcourse:created', 'local_eduvidual'),
                                    'url' => $redirect,
                                    'type' => 'success',
                                ));
                            }

                            if (empty($course->id)) {
                                $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                                    'content' => get_string('createcourse:createerror', 'local_eduvidual'),
                                    'url' => $CFG->wwwroot . '/my',
                                    'type' => 'error',
                                ));
                            }
                        } else {
                            $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                                'content' => get_string('createcourse:nametooshort', 'local_eduvidual'),
                                'url' => $CFG->wwwroot . '/my',
                                'type' => 'error',
                            ));
                        }
                    } else {
                        $msg[] = $OUTPUT->render_from_template('local_eduvidual/alert', array(
                            'content' => get_string('createcourse:invalidbasement', 'local_eduvidual'),
                            'url' => $CFG->wwwroot . '/my',
                            'type' => 'error',
                        ));
                    }
                }
            }

        }
    }
}

if (!empty($redirect)) {
    redirect($redirect);
}
echo $OUTPUT->header();
if (count($msg) > 0) {
    echo implode('', $msg);
} else {
    if (count($orgs) == 0) {
        echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
            'content' => get_string('missing_permission', 'local_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } else {
        $_orgs = array();
        foreach ($orgs as $_org) {
            $_orgs[] = $_org;
        }
        $schoolyears = array('SJ 19/20', 'SJ 20/21');

        $favorgid = \local_eduvidual\locallib::get_favorgid();
        foreach ($_orgs as &$_org) {
            $_org->isselected = ((empty($orgid) && $favorgid == $_org->orgid) || (!empty($orgid) && $orgid == $_org->orgid)) ? 1 : 0;
        }

        echo $OUTPUT->render_from_template('local_eduvidual/teacher_createcourse', array(
            'coursebasementempty' => get_config('local_eduvidual', 'coursebasementempty'),
            'coursebasementrestore' => get_config('local_eduvidual', 'coursebasementrestore'),
            'coursebasementtemplate' => get_config('local_eduvidual', 'coursebasementtemplate'),
            'has_subcats1' => empty($subcats1) ? 0 : 1,
            'has_subcats2' => empty($subcats2) ? 0 : 1,
            'has_subcats3' => empty($subcats3) ? 0 : 1,
            'ismanager' => (\local_eduvidual\locallib::get_highest_role() == 'Manager') ? 1 : 0,
            'multipleorgs' => (count($_orgs) > 1),
            'orgs' => $_orgs,
            'orgfirst' => $_orgs[0]->orgid,
            'subcats1' => $subcats1,
            'subcats2' => $subcats2,
            'subcats3' => $subcats3,
            'subcats1lbl' => get_string('loading'),
            'subcats2lbl' => get_string('loading'),
            'subcats3lbl' => get_string('loading'),
            'subcats4lbl' => get_string('loading'),
            'subcats1org' => '',
            'subcats2org' => '',
            'subcats3org' => '',
            'subcats4org' => '',
            'wwwroot' => $CFG->wwwroot,
        ));
    }
}

echo $OUTPUT->footer();
