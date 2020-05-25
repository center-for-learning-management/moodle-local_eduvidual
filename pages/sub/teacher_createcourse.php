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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$formsent = optional_param('formsent', 0, PARAM_INT);
$orgs = block_eduvidual::get_organisations('Teacher');

$orgid = optional_param('orgid', 0, PARAM_INT);
$subcat1 = optional_param('subcat1', '', PARAM_TEXT);
$subcat2 = optional_param('subcat2', '', PARAM_TEXT);
$subcat3 = optional_param('subcat3', '', PARAM_TEXT);
$subcat4 = optional_param('subcat4', '', PARAM_TEXT);

$basement = optional_param('basement', 0, PARAM_INT);

$redirect = '';
$msg = array();

if ($formsent) {
    $org = block_eduvidual::get_organisations_check($orgs, $orgid);
    $subcats1 = block_eduvidual::get('subcats1');
    $subcats2 = block_eduvidual::get('subcats2');
    $subcats3 = block_eduvidual::get('subcats3');

    if (empty($subcat1)) {
        $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
            'content' => get_string('createcourse:subcat1emptyerror', 'block_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } elseif (empty($org->id) ||
            !empty($subcat1) && !empty($subcats1) && !in_array($subcat1, $subcats1) ||
            !empty($subcat2) && !empty($subcats2) && !in_array($subcat2, $subcats2) ||
            !empty($subcat3) && !empty($subcats3) && !in_array($subcat3, $subcats3)) {
        $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
            'content' => get_string('missing_permission', 'block_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } else {
        // We can create a course in that org!
        $parts = array();
        if (!empty($subcat2)) $parts[] = $subcat2;
        if (!empty($subcat3)) $parts[] = $subcat3;
        if (!empty($subcat4)) $parts[] = $subcat4;
        $coursename = implode(' ', $parts);
        if (empty(str_replace(' ', '', $coursename))) {
            $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                'content' => get_string('createcourse:coursenameemptyerror', 'block_eduvidual'),
                'url' => $CFG->wwwroot . '/my',
                'type' => 'error',
            ));
        } else {
            $coursename = $subcat1 . ' ' . $coursename;
            $cat1 = $DB->get_record('course_categories', array('parent' => $org->categoryid, 'name' => $subcat1));
            if (empty($cat1->id)) {
                // Create this category!
                require_once($CFG->dirroot . '/lib/coursecatlib.php');
                $cat1 = (object) array(
                    'name' => $subcat1,
                    'description' => '',
                    'parent' => $org->categoryid,
                    'visible' => 1
                );
                $cat1 = coursecat::create($cat1);
            }
            // If it is still empty - error
            if (empty($cat1->id)) {
                $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                    'content' => get_string('createcourse:catcreateerror', 'block_eduvidual'),
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
                            'visible' => 1
                        );
                        $cat2 = core_course_category::create($cat2);
                    }
                    // If it is still empty - error
                    if (empty($cat2->id)) {
                        $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                            'content' => get_string('createcourse:catcreateerror', 'block_eduvidual'),
                            'url' => $CFG->wwwroot . '/my',
                            'type' => 'error',
                        ));
                    } else {
                        $targcat = $cat2;
                    }
                }
                if (!empty($targcat->id)) {
                    //echo "Target Category: ";
                    //print_r($targcat);
                    // Now create course in this category!
                    // Now check if basement is valid
                    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                    if(block_eduvidual_lib_enrol::is_valid_course_basement('all', $basement)){
                        // Create course here
                        $fullname = $coursename;
                        $categoryid = $targcat->id;
                        $shortname = '[' . $USER->id . '-' . date('YmdHis') . '] ' . $coursename . '(' . $org->orgid . ')';

                        if (strlen($fullname) > 5) {
                            require_once($CFG->dirroot . '/course/externallib.php');
                            if (strlen($shortname) > 30) $shortname = substr($shortname, 0, 30);
                            // Grant a role that allows course duplication in source and target category
                            $basecourse = $DB->get_record('course', array('id' => $basement));
                            $sourcecatcontext = context_coursecat::instance($basecourse->category);
                            $targetcatcontext = context_coursecat::instance($categoryid);
                            $roletoassign = 1; // Manager
                            $revokesourcerole = true;
                            $revoketargetrole = true;
                            $roles = get_user_roles($sourcecatcontext, $USER->id, false);
                            foreach($roles AS $role) {
                                if ($role->roleid == $roletoassign) {
                                    // User had this role before - we do not revoke!
                                    $revokesourcerole = false;
                                }
                            }
                            $roles = get_user_roles($targetcatcontext, $USER->id, false);
                            foreach($roles AS $role) {
                                if ($role->roleid == $roletoassign) {
                                    // User had this role before - we do not revoke!
                                    $revoketargetrole = false;
                                }
                            }
                            role_assign($roletoassign, $USER->id, $sourcecatcontext->id);
                            role_assign($roletoassign, $USER->id, $targetcatcontext->id);

                            // Create new course.
                            require_once($CFG->dirroot . '/course/lib.php');
                            $coursedata = $basecourse;
                            unset($coursedata->id);
                            unset($coursedata->idnumber);
                            unset($coursedata->sortorder);
                            $coursedata->fullname = $fullname;
                            $coursedata->shortname = $shortname;
                            $coursedata->category = $categoryid;
                            $coursedata->startdate = (date("m") < 6)?strtotime((date("Y")-1) . '0901000000'):strtotime(date("Y") . '0901000000');
                            $coursedata->summary = "";
                            if (!empty($subcat1)) $coursedata->summary .= $org->subcats1lbl . ': ' . $subcat1 . "<br />\n";
                            if (!empty($subcat2)) $coursedata->summary .= $org->subcats2lbl . ': ' . $subcat2 . "<br />\n";
                            if (!empty($subcat3)) $coursedata->summary .= $org->subcats3lbl . ': ' . $subcat3 . "<br />\n";
                            if (!empty($subcat4)) $coursedata->summary .= $org->subcats4lbl . ': ' . $subcat4 . "<br />\n";
                            //print_r($coursedata);
                            $course = create_course($coursedata);

                            $basecontext = context_course::instance($basement);
                            $targetcontext = context_course::instance($course->id);
                            $overviewimages = $DB->get_records('files', array('contextid' => $basecontext->id, 'component' => 'course', 'filearea' => 'overviewfiles'));
                            foreach ($overviewimages AS $ovi) {
                                unset($ovi->id);
                                $ovi->contextid = $targetcontext->id;
                                $ovi->pathnamehash = sha1(implode('/', array($targetcontext->id, $ovi->component, $ovi->filearea, $ovi->filepath, $ovi->filename )));
                                $ovi->timecreated = time();
                                $ovi->timemodified = time();
                                $DB->insert_record('files', $ovi);
                            }

                            // ATTENTION - Revoking the role is MANDATORY and is done AFTER the roles are set in the course!
                            if (!empty($course->id)) {
                                // If we should enrol another user, we do it.
                                $enroluser = !empty($enroluser) ? $enroluser : $USER->id;
                                $context = context_course::instance($course->id);
                                $role = get_config('block_eduvidual', 'defaultroleteacher');
                                $enroluser = optional_param('setteacher', 0, PARAM_INT);
                                if (empty($enroluser) || $enroluser == 0) $enroluser = $USER->id;
                                block_eduvidual_lib_enrol::course_manual_enrolments(array($course->id), array($enroluser), $role);

                                // Do the import from basement.
                                require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

                                // Make backup from basement.
                                $course_to_backup = $basement; // id of the course to backup.
                                $course_to_restore  = $course->id; // id of the target course.
                                $user_performing = $USER->id; // id of the user performing the backup.
                                //print_r($course);

                                $bc = new backup_controller(backup::TYPE_1COURSE, $course_to_backup, backup::FORMAT_MOODLE,
                                                            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $user_performing);
                                //$bc->get_plan()->get_setting('users')->set_value(0);
                                $bc->execute_plan();
                                $bc->get_results();
                                $bc->destroy();

                                $tempdestination = make_backup_temp_directory($bc->get_backupid(), false);
                                if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
                                    print_error('unknownbackupexporterror'); // shouldn't happen ever
                                }

                                require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

                                // Transaction.
                                $transaction = $DB->start_delegated_transaction();

                                // Restore backup into course.
                                $rc = new restore_controller($bc->get_backupid(), $course_to_restore,
                                        backup::INTERACTIVE_NO, backup::MODE_IMPORT, $user_performing,
                                        backup::TARGET_EXISTING_DELETING);
                                if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
                                    $rc->convert();
                                }
                                $rc->execute_precheck();
                                $rc->execute_plan();

                                // Commit.
                                $transaction->allow_commit();

                                $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                                    'content' => get_string('createcourse:created', 'block_eduvidual'),
                                    'url' => $CFG->wwwroot . '/course/view.php?id=' . $course_to_restore,
                                    'type' => 'success',
                                ));
                                $redirect = $CFG->wwwroot . '/course/view.php?id=' . $course_to_restore;

                                $PAGE->set_context(context_system::instance());
                            } else {
                                $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                                    'content' => get_string('createcourse:createerror', 'block_eduvidual'),
                                    'url' => $CFG->wwwroot . '/my',
                                    'type' => 'error',
                                ));
                            }

                            /*
                            // DO THE MAGIC! CLONE THE COURSE!
                            $course = (object) core_course_external::duplicate_course($basement, $fullname, $shortname, $categoryid, true);
                            // ATTENTION - Revoking the role is MANDATORY and is done AFTER the roles are set in the course!
                            if (!empty($course->id)) {
                                $context = context_course::instance($course->id);
                                $role = get_config('block_eduvidual', 'defaultroleteacher');
                                $enroluser = optional_param('setteacher', 0, PARAM_INT);
                                if (empty($enroluser) || $enroluser == 0) $enroluser = $USER->id;
                                block_eduvidual_lib_enrol::course_manual_enrolments(array($course->id), array($enroluser), $role);
                                // Set the start date of this course to sep 1st of the school year
                                $course = $DB->get_record('course', array('id' => $course->id));
                                $course->startdate = (date("m") < 6)?strtotime((date("Y")-1) . '0901000000'):strtotime(date("Y") . '0901000000');
                                $course->description = "";
                                if (!empty($subcat1)) $course->description .= block_eduvidual::get('subcats1lbl') . ': ' . $subcat1 . "<br />\n";
                                if (!empty($subcat2)) $course->description .= block_eduvidual::get('subcats2lbl') . ': ' . $subcat2 . "<br />\n";
                                if (!empty($subcat3)) $course->description .= block_eduvidual::get('subcats3lbl') . ': ' . $subcat3 . "<br />\n";
                                if (!empty($subcat4)) $course->description .= block_eduvidual::get('subcats4lbl') . ': ' . $subcat4 . "<br />\n";
                                $DB->update_record('course', $course);
                                $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                                    'content' => get_string('createcourse:created', 'block_eduvidual'),
                                    'url' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
                                    'type' => 'success',
                                ));
                                $redirect = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

                                $PAGE->set_context(context_system::instance());
                            } else {
                                $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                                    'content' => get_string('createcourse:createerror', 'block_eduvidual'),
                                    'url' => $CFG->wwwroot . '/my',
                                    'type' => 'error',
                                ));
                            }
                            */

                            // Revoke role that allows course duplication in source and target category
                            if ($revokesourcerole) {
                                role_unassign($roletoassign, $USER->id, $sourcecatcontext->id);
                            }
                            if ($revoketargetrole) {
                                role_unassign($roletoassign, $USER->id, $targetcatcontext->id);
                            }
                        } else {
                            $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                                'content' => get_string('createcourse:nametooshort', 'block_eduvidual'),
                                'url' => $CFG->wwwroot . '/my',
                                'type' => 'error',
                            ));
                        }
                    } else {
                        $msg[] = $OUTPUT->render_from_template('block_eduvidual/alert', array(
                            'content' => get_string('createcourse:invalidbasement', 'block_eduvidual'),
                            'url' => $CFG->wwwroot . '/my',
                            'type' => 'error',
                        ));
                    }
                }
            }

        }
    }
    if (!empty($redirect)) {
        $PAGE->requires->js_call_amd('block_eduvidual/jquery-ba-postmessage', 'post', array('open_course|' . $course->id));
        block_eduvidual::print_app_header();
        echo implode('', $msg);
        block_eduvidual::print_app_footer();
        redirect($redirect);
        die();
    }
}

$PAGE->navbar->add(get_string('teacher:createcourse', 'block_eduvidual'), $PAGE->url);
block_eduvidual::print_app_header();
if (count($msg) > 0) {
    echo implode('', $msg);
} else {
    if (count($orgs) == 0) {
        echo $OUTPUT->render_from_template('block_eduvidual/alert', array(
            'content' => get_string('missing_permission', 'block_eduvidual'),
            'url' => $CFG->wwwroot . '/my',
            'type' => 'error',
        ));
    } else {
        $_orgs = array();
        foreach($orgs AS $_org) { $_orgs[] = $_org; }
        $schoolyears = array('SJ 19/20', 'SJ 20/21');
        $basements = array();
        $_basements = block_eduvidual_lib_enrol::get_course_basements('all');
        $basecats = array_keys($_basements);
        foreach($basecats AS $basecat) {
            $basements[] = array(
                'name' => $basecat,
                'templates' => $_basements[$basecat],
            );
        }

        foreach ($_orgs AS &$_org) {
            $_org->isselected = (!empty($org->orgid) && !empty($_org->orgid) && $_org->orgid == $org->orgid) ? 1 : 0;
        }

        echo $OUTPUT->render_from_template('block_eduvidual/teacher_createcourse', array(
            'basements' => $basements, // Coursetemplates
            'has_subcats1' => empty(block_eduvidual::get('subcats1')) ? 0 : 1,
            'has_subcats2' => empty(block_eduvidual::get('subcats2')) ? 0 : 1,
            'has_subcats3' => empty(block_eduvidual::get('subcats3')) ? 0 : 1,
            'ismanager' => (block_eduvidual::get('orgrole') == 'Manager') ? 1 : 0,
            'multipleorgs' => (count($_orgs) > 1),
            'orgs' => $_orgs,
            'orgfirst' => $_orgs[0]->orgid,
            'subcats1' => block_eduvidual::get('subcats1'),
            'subcats2' => block_eduvidual::get('subcats2'),
            'subcats3' => block_eduvidual::get('subcats3'),
            'subcats1lbl' => block_eduvidual::get('subcats1lbl'),
            'subcats2lbl' => block_eduvidual::get('subcats2lbl'),
            'subcats3lbl' => block_eduvidual::get('subcats3lbl'),
            'subcats4lbl' => block_eduvidual::get('subcats4lbl'),
            'subcats1org' => block_eduvidual::get('subcats1org'),
            'subcats2org' => block_eduvidual::get('subcats2org'),
            'subcats3org' => block_eduvidual::get('subcats3org'),
            'subcats4org' => block_eduvidual::get('subcats4org'),
            'wwwroot' => $CFG->wwwroot . '/blocks/eduvidual/pages/teacher.php?act=createcourse',
        ));
    }
}
