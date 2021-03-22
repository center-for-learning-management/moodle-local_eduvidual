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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Ensure that every organiation has a support course.
 */

require_once('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/admin_supportcourses.php', array());
$PAGE->set_title(get_string('admin:supportcourses', 'local_eduvidual'));
$PAGE->set_heading(get_string('admin:supportcourses', 'local_eduvidual'));

require_login();

if (!is_siteadmin()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('access_denied', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();

// We accidentially created some courses multiple times. We remove those, that we do not need.
// REMOVE WRONG COURSES BY MANUAL LIST
/*
$list = explode(",", "");
for ($a = 0; $a<count($list); $a++) {
    $courseid = $list[$a];
    if (empty($courseid)) continue;
    $course = $DB->get_record('course', array('id' => $courseid));
    if (empty($course->id)) continue;
    echo "Removing course #$course->id<br />";
    flush();
    \delete_course($course);
}
*/
// REMOVING WRONG COURSES BY AUTOMATED LIST
/*
$sql = "SELECT * FROM {local_edusupport}
            WHERE courseid NOT IN (
                     SELECT supportcourseid FROM {local_eduvidual_org}
                         WHERE supportcourseid>0
                 )
                 AND courseid<>606";
$wrongs = $DB->get_records_sql($sql);
foreach ($wrongs as $wrong) {
     $course = $DB->get_record('course', array('id' => $wrong->courseid));
     if (empty($course->id)) {
          // This course has already been removed and is a ghost.
          echo "Removing ghost #$wrong->forumid<br />";
          $DB->delete_records('local_edusupport', array('forumid' => $wrong->forumid));
          continue;
     }
     $forums = $DB->get_records('local_edusupport', array('courseid' => $wrong->id));
     foreach ($forums as $forum) {
          echo "Removing supportforum #$forum->id<br />";
          \local_edusupport\lib::supportforum_disable($forum->id);
     }
     echo "Removing course #$course->id<br />";
     flush();
     \delete_course($course);
}
die();
*/


// Accidentially moodle created news forums after duplicating the template. We do not want news forums. We remove them.
/*
$sql = "SELECT * FROM {local_edusupport} WHERE forumid IN (SELECT id FROM {forum} WHERE type='news')";
$newsforums = $DB->get_records_sql($sql);
foreach ($newsforums as $newsforum) {
    \local_edusupport\lib::supportforum_disable($newsforum->forumid);
    $cm = \get_coursemodule_from_instance('forum', $newsforum->forumid);
    echo "Delete cm #$cm->id<br />";
    \course_delete_module($cmid);
}
die();
*/


$template = get_config('local_eduvidual', 'supportcourse_template');
if (empty($template)) {
    echo $OUTPUT->render_from_template('local_eduvidual/alert', array(
        'content' => get_string('admin:supportcourse:missingsetup', 'local_eduvidual'),
        'type' => 'danger'
    ));
    echo $OUTPUT->footer();
    die();
}



$templatecourse = $DB->get_record('course', array('id' => $template), '*', IGNORE_MISSING);
if (empty($templatecourse->id)) {
    echo "THERE IS NO SUPPORT TEMPLATE COURSE";
    echo $OUTPUT->footer();
    die();
}
/*
// Some supportforums were not enabled. We go through all and test if we should activate them.
echo "<ul>\n";
require_once($CFG->dirroot . '/course/externallib.php');
$orgs = $DB->get_records_sql("SELECT * FROM {local_eduvidual_org} WHERE supportcourseid>0");
foreach ($orgs AS $org) {
    if ($org->categoryid == 0) continue;
    $supportcourse = $DB->get_record('course', array('id' => $org->supportcourseid), 'id', IGNORE_MISSING);

    echo "<li class='alert alert-success'>Re-activate supportcourse <a href=\"$CFG->wwwroot/course/view.php?id=$supportcourse->id\">#$supportcourse->id</a></li>\n";
    // Retrieve all forums from course and configure as supportforum.
    $sql = "SELECT * FROM {forum} WHERE course=? AND type='general'";
    $forums = $DB->get_records_sql($sql, array($supportcourse->id));
    if (count($forums) == 0) {
        echo "<li class='alert alert-danger'>There are no forums in supportcourse <a href=\"$CFG->wwwroot/course/view.php?id=$supportcourse->id\">#$supportcourse->id</a></li>\n";
    } else {
        $members = $DB->get_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid));
        $managers = array();
        $others = array();
        foreach ($members AS $member) {
            if ($member->role == 'Manager') $managers[] = $member->userid;
            else $others[] = $member->userid;
        }
        foreach ($forums AS $forum) {
            if (\local_edusupport\lib::is_supportforum($forum->id)) {
                echo "<li>Skipping forum #$forum->id</li>\n";
                continue;
            }
            echo "<li>Enabling forum #$forum->id</li>\n";
            \local_edusupport\lib::supportforum_enable($forum->id);
            // Add subscriptions for managers.
            foreach ($managers AS $managerid) {
                $chk = $DB->get_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                if (empty($chk->id)) {
                    echo "<li>Added subscription for Manager #$managerid in forum #$forum->id</li>\n";
                    $DB->insert_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                }
            }
            if ($org->orgid > 500000 && $org->orgid < 600000) {
                // School from Salzburg
                 echo "<li>Added dedicated supporter #2098</li>\n";
                 \local_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
             }
         }
     }
 }
 echo "</ul>\n";
 die();
*/


echo "<ul>\n";
require_once($CFG->dirroot . '/course/externallib.php');
$sql = "SELECT * FROM {local_eduvidual_org} WHERE authenticated > ?";
$orgs = $DB->get_records_sql($sql, array(0));
foreach ($orgs AS $org) {
    if ($org->categoryid == 0) continue;
    // Reload entry, if another process created the course in the meanwhile
    $org = $DB->get_record('local_eduvidual_org', array('orgid' => $org->orgid));
    $course = $DB->get_record('course', array('id' => $org->supportcourseid), 'id', IGNORE_MISSING);
    $coursen = $DB->get_record('course', array('shortname' => 'helpdesk_' . $org->orgid), 'id', IGNORE_MISSING);
    if (!empty($org->supportcourseid) && !empty($course->id)) {
        echo "<li>$org->name has a supportcourse with <a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">#$course->id</a></li>\n";
    } elseif (!empty($coursen->id)) {
        echo "<li>$org->name is currently getting a supportcourse with <a href=\"$CFG->wwwroot/course/view.php?id=$coursen->id\">#$coursen->id</a></li>\n";
        $DB->set_field('local_eduvidual_org', 'supportcourseid', $coursen->id, array('orgid' => $org->orgid));
        // Now enrol all users of that organisation.
        $members = $DB->get_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid));
        $managers = array();
        $others = array();
        foreach ($members AS $member) {
            if ($member->role == 'Manager') $managers[] = $member->userid;
            else $others[] = $member->userid;
        }
        \local_eduvidual\lib_enrol::course_manual_enrolments(array($coursen->id), $managers, get_config('local_eduvidual', 'defaultroleteacher'));
        \local_eduvidual\lib_enrol::course_manual_enrolments(array($coursen->id), $others, get_config('local_eduvidual', 'defaultrolestudent'));
        echo "<li>Added " . count($managers) . " Managers with teacher role</li>\n";
        echo "<li>Added " . count($others) . " Users with student role</li>\n";
        $sql = "SELECT * FROM {forum} WHERE course=? AND type='general'";
        $forums = $DB->get_records_sql($sql, array($coursen->id));
        if (count($forums) == 0) {
            echo "<li class='alert alert-danger'>There are no forums in supportcourse <a href=\"$CFG->wwwroot/course/view.php?id=$coursen->id\">#$coursen->id</a></li>\n";
        } else {
            foreach ($forums AS $forum) {
                \local_edusupport\lib::supportforum_enable($forum->id);
                // Add subscriptions for managers.
                foreach ($managers AS $managerid) {
                    $chk = $DB->get_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                    if (empty($chk->id)) {
                        echo "<li>Added subscription for Manager #$managerid in forum #$forum->id</li>\n";
                        $DB->insert_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                    }
                }
                if ($org->orgid > 500000 && $org->orgid < 600000) {
                    // School from Salzburg
                    echo "<li>Added dedicated supporter #2098</li>\n";
                    \local_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
                }
            }
        }
    } else {
        echo "<li>$org->name needs a supportcourse<ul>\n";
        $supportcourse = \local_eduvidual\lib_helper::duplicate_course($template, 'Helpdesk (' . $org->name . ')', 'helpdesk_' . $org->orgid, $org->categoryid, 1);
        if (empty($supportcourse->id)) {
            echo "<li class='alert alert-danger'><strong>Error creating supportcourse</strong></li>\n";
        } else {
            \local_eduvidual\lib_enrol::course_manual_enrolments(array($supportcourse->id), array($USER->id), -1);
            echo "<li class='alert alert-success'>Supportcourse created successfully <a href=\"$CFG->wwwroot/course/view.php?id=$supportcourse->id\">#$supportcourse->id</a></li>\n";
            $DB->set_field('local_eduvidual_org', 'supportcourseid', $supportcourse->id, array('orgid' => $org->orgid));
            // Now enrol all users of that organisation.
            $members = $DB->get_records('local_eduvidual_orgid_userid', array('orgid' => $org->orgid));
            $managers = array();
            $others = array();
            foreach ($members AS $member) {
                if ($member->role == 'Manager') $managers[] = $member->userid;
                else $others[] = $member->userid;
            }
            \local_eduvidual\lib_enrol::course_manual_enrolments(array($supportcourse->id), $managers, get_config('local_eduvidual', 'defaultroleteacher'));
            \local_eduvidual\lib_enrol::course_manual_enrolments(array($supportcourse->id), $others, get_config('local_eduvidual', 'defaultrolestudent'));
            echo "<li>Added " . count($managers) . " Managers with teacher role</li>\n";
            echo "<li>Added " . count($others) . " Users with student role</li>\n";

            // Retrieve all forums from course and configure as supportforum.
            $sql = "SELECT * FROM {forum} WHERE course=? AND type='general'";
            $forums = $DB->get_records_sql($sql, array($supportcourse->id));
            if (count($forums) == 0) {
                echo "<li class='alert alert-danger'>There are no forums in supportcourse <a href=\"$CFG->wwwroot/course/view.php?id=$supportcourse->id\">#$supportcourse->id</a></li>\n";
            } else {
                foreach ($forums AS $forum) {
                    \local_edusupport\lib::supportforum_enable($forum->id);
                    // Add subscriptions for managers.
                    foreach ($managers AS $managerid) {
                        $chk = $DB->get_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                        if (empty($chk->id)) {
                            echo "<li>Added subscription for Manager #$managerid in forum #$forum->id</li>\n";
                            $DB->insert_record('forum_subscriptions', array('userid' => $managerid, 'forum' => $forum->id));
                        }
                    }
                    if ($org->orgid > 500000 && $org->orgid < 600000) {
                        // School from Salzburg
                        echo "<li>Added dedicated supporter #2098</li>\n";
                        \local_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
                    }
                }
            }
        }
        echo "</ul></li>\n";
    }
}
echo "</ul>\n";

echo $OUTPUT->footer();
