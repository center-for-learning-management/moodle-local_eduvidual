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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @TODO implement a session-counter to prevent brute force attack on orgids

defined('MOODLE_INTERNAL') || die;

if (isloggedin() && !isguestuser()) {
    $stage = optional_param('stage', 0, PARAM_INT);
    switch ($stage) {
        case 0:
            /*
             * Request for specific orgid
             * Should return if orgid is valid
            */
            $orgid = optional_param('orgid', -1, PARAM_INT);
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid));
            if (isset($org->orgid) && $org->orgid == $orgid) {
                $reply['status'] = 'ok';
                $reply['name'] = substr($org->name, 0, 30);
                $reply['authenticated'] = $org->authenticated;
                $reply['mail'] = $org->mail;
            } else {
                $reply['status'] = 'silent';
            }
        break;
        case 1:
            /*
             * Generate registration token for this org and send to org-mail
             * Should return if mail was sent successfully
            */
            $orgid = optional_param('orgid', -1, PARAM_INT);
            $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid, 'authenticated' => 0));

            if (isset($org->orgid) && $org->orgid == $orgid) {
                $org->authtan = substr(md5(date('Y-m-d H:i:s') . rand(0, 99999)), 0, 10);
                $DB->update_record('local_eduvidual_org', $org);

                $touser = new \stdClass();
                $touser->email = $org->mail;
                $touser->firstname = $USER->firstname;
                $touser->lastname = $USER->lastname;
                $touser->maildisplay = true;
                $touser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
                $touser->id = -99; // invalid userid, as the user has no userid in our moodle.
                $touser->firstnamephonetic = "";
                $touser->lastnamephonetic = "";
                $touser->middlename = "";
                $touser->alternatename = "";

                $fromuser = \core_user::get_support_user();

                $messagehtml = $OUTPUT->render_from_template(
                    'local_eduvidual/register_mail_authtan',
                    (object) array(
                        'authtan' => $org->authtan,
                        'email' => $USER->email,
                        'orgid' => $org->orgid,
                        'registrationurl' => $CFG->wwwroot . '/local/eduvidual/pages/register.php',
                        'subject' => get_string('mailregister:header', 'local_eduvidual'),
                        'userfullname' => $USER->firstname . ' ' . $USER->lastname,
                        'userid' => $USER->id,
                        'wwwroot' => $CFG->wwwroot,
                    )
                );

                $messagetext = html_to_text($messagehtml);
                $subject = get_string('mailregister:subject' , 'local_eduvidual');
                email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, "", true);

                // Sending a short statement to CC-Users
                $ccmails = explode(',', get_config('local_eduvidual', 'registrationcc'));
                if (count($ccmails) > 0) {
                    foreach($ccmails AS $ccmail) {
                        if (!filter_var($ccmail, FILTER_VALIDATE_EMAIL)) continue;
                        $touser->email = trim($ccmail);
                        email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, "", true);
                    }
                }
                $reply['status'] = 'ok';
            } else {
                $reply['status'] = 'error';
            }
        break;
        case 2:
            /*
             * Check registration token for this org and create if correct
             * Should return if token was correct and organisation was created
            */

            $orgid = optional_param('orgid', -1, PARAM_INT);
            $authtan = optional_param('token', '', PARAM_TEXT);
            $name = substr(optional_param('name', '', PARAM_TEXT), 0, 30);
            $test = $DB->get_record('local_eduvidual_org', array('name' => $name));
            if (strlen($name) <= 5) {
                $reply['status'] = 'error';
                $reply['error'] = 'err_name_too_short';
            } elseif (isset($test->orgid) && $test->name == $name && $test->orgid != $orgid) {
                $reply['status'] = 'error';
                $reply['error'] = 'err_name_already_taken';
            } else {
                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $orgid, 'authtan' => $authtan));

                if (isset($org->orgid) && $org->orgid == $orgid) {
                    $org->name = $name;
                    $DB->set_field('local_eduvidual_org', 'name', $name, array('orgid' => $org->orgid));

                    //require_once($CFG->dirroot . '/lib/coursecatlib.php');
                    require_once($CFG->dirroot . '/course/externallib.php');

                    if (empty($org->categoryid)) {
                        // Create a course category for this org
                        $data = new \stdClass();
                        $data->name = $org->name;
                        $data->description = $org->name;
                        $data->idnumber = $org->orgid;
                        $category = \core_course_category::create($data);
                        $org->categoryid = $category->id;
                        $DB->set_field('local_eduvidual_org', 'categoryid', $org->categoryid, array('orgid' => $org->orgid));
                    }

                    $reply["name"] = $org->name;
                    $reply["categoryid"] = $org->categoryid;

                    if (empty($org->courseid)) {
                        // Create an org-course for this org
                        $orgcoursebasement = get_config('local_eduvidual', 'orgcoursebasement');

                        $course = \local_eduvidual\lib_helper::duplicate_course($orgcoursebasement, 'Digitaler Schulhof (' . $org->orgid . ')', $org->orgid, $org->categoryid, 1);
                        $org->courseid = $course->id;
                        $DB->set_field('local_eduvidual_org', 'courseid', $org->courseid, array('orgid' => $org->orgid));
                        $course->summary = '<p>Digitaler Schulhof der Schule ' . $org->name . '</p>';
                        $DB->set_field('course', 'summary', $course->summary, array('id' => $course->id));
                    }
                    if (empty($org->supportcourseid) && file_exists($CFG->dirroot . '/local/edusupport/version.php')) {
                        // Create a support course for this org.
                        $template = get_config('local_eduvidual', 'supportcourse_template');
                        if (!empty($template)) {
                            // Duplicate our template.
                            $supportcourse = \local_eduvidual\lib_helper::duplicate_course($template, 'Helpdesk (' . $org->name . ')', 'helpdesk_' . $org->orgid, $org->categoryid, 1);
                            if (!empty($supportcourse->id)) {
                                // Remove news forum in that course.
                                $sql = "SELECT * FROM {forum} WHERE type='news')";
                                $newsforums = $DB->get_records('forum', array('type' => 'news', 'course' => $supportcourse->id));
                                foreach ($newsforums as $newsforum) {
                                    $cm = \get_coursemodule_from_instance('forum', $newsforum->forumid);
                                    \course_delete_module($cmid);
                                }
                                $sql = "SELECT * FROM {forum} WHERE course=? AND type='general'";
                                $forums = $DB->get_records_sql($sql, array($supportcourse->id));
                                foreach ($forums as $forum) {
                                    \local_edusupport\lib::supportforum_enable($forum->id);
                                    if ($org->orgid > 500000 && $org->orgid < 600000) {
                                        // School from Salzburg
                                        \local_edusupport\lib::supportforum_setdedicatedsupporter($forum->id, 2098);
                                    }
                                }
                            }

                        }
                    }

                    $reply['roleset'] = \local_eduvidual\lib_enrol::role_set($USER->id, $org, 'Manager');

                    if (!empty($org->courseid)) {
                        $messagehtml = $OUTPUT->render_from_template(
                            'local_eduvidual/register_mail_completed',
                            (object) array(
                                'categoryurl' => $CFG->wwwroot . '/course/index.php?categoryid=' . $org->categoryid,
                                'managerscourseurl' => $CFG->wwwroot . '/course/view.php?id=398',
                                'orgid' => $org->orgid,
                                'orgname' => $org->name,
                                'subject' => get_string('mailregister:2:header', 'local_eduvidual'),
                                'wwwroot' => $CFG->wwwroot,
                            )
                        );

                        $messagetext = html_to_text($messagehtml);

                        $subject = get_string('mailregister:2:subject' , 'local_eduvidual');

                        $fromuser = \core_user::get_support_user();

                        $touser = new \stdClass();
                        $touser->email = '';
                        $touser->firstname = 'Registration';
                        $touser->lastname = 'Completed';
                        $touser->maildisplay = true;
                        $touser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
                        $touser->id = -99; // invalid userid, as the user has no userid in our moodle.
                        $touser->firstnamephonetic = "";
                        $touser->lastnamephonetic = "";
                        $touser->middlename = "";
                        $touser->alternatename = "";

                        // Sending a short statement to CC-Users
                        $ccmails = explode(',', get_config('local_eduvidual', 'registrationcc'));
                        if (count($ccmails) > 0) {
                            foreach($ccmails AS $ccmail) {
                                if (!filter_var($ccmail, FILTER_VALIDATE_EMAIL)) continue;
                                $touser->email = trim($ccmail);
                                email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, "", true);
                            }
                        }
                        $org->authenticated = time();
                        $org->authtan = '';
                        $DB->set_field('local_eduvidual_org', 'authenticated', $org->authenticated, array('orgid' => $org->orgid));
                        $DB->set_field('local_eduvidual_org', 'authtan', $org->authtan, array('orgid' => $org->orgid));
                        $reply['status'] = 'ok';
                    } else {
                        $reply['status'] = 'error';
                        $reply['error'] = 'error creating category or course';
                    }

                } else {
                    $reply['status'] = 'error';
                }
            }
        break;
    }
} else {
    $reply['error'] = get_string('registration:loginfirst', 'local_eduvidual');
}
