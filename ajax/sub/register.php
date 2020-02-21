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

// @TODO implement a session-counter to prevent brute force attack on orgids

defined('MOODLE_INTERNAL') || die;

if ($USER->id > 1) {
    $stage = optional_param('stage', 0, PARAM_INT);
    switch ($stage) {
        case 0:
            /*
             * Request for specific orgid
             * Should return if orgid is valid
            */
            $orgid = optional_param('orgid', -1, PARAM_INT);
            $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid));
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
            $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid, 'authenticated' => 0));

            if (isset($org->orgid) && $org->orgid == $orgid) {
                $org->authtan = substr(md5(date('Y-m-d H:i:s') . rand(0, 99999)), 0, 10);
                $DB->update_record('block_eduvidual_org', $org);

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

                $fromuser = core_user::get_support_user();

                $messagehtml = $OUTPUT->render_from_template(
                    'block_eduvidual/register_mail_authtan',
                    (object) array(
                        'authtan' => $org->authtan,
                        'email' => $USER->email,
                        'orgid' => $org->orgid,
                        'registrationurl' => $CFG->wwwroot . '/blocks/eduvidual/pages/register.php',
                        'subject' => get_string('mailregister:header', 'block_eduvidual'),
                        'userfullname' => $USER->firstname . ' ' . $USER->lastname,
                        'userid' => $USER->id,
                        'wwwroot' => $CFG->wwwroot,
                    )
                );

                $messagetext = html_to_text($messagehtml);
                $subject = get_string('mailregister:subject' , 'block_eduvidual');
                email_to_user($touser, $fromuser, $subject, $messagetext, $messagehtml, "", true);

                // Sending a short statement to CC-Users
                $ccmails = explode(',', get_config('block_eduvidual', 'registrationcc'));
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
            $test = $DB->get_record('block_eduvidual_org', array('name' => $name));
            if (strlen($name) <= 5) {
                $reply['status'] = 'error';
                $reply['error'] = 'err_name_too_short';
            } elseif (isset($test->orgid) && $test->name == $name && $test->orgid != $orgid) {
                $reply['status'] = 'error';
                $reply['error'] = 'err_name_already_taken';
            } else {
                $org = $DB->get_record('block_eduvidual_org', array('orgid' => $orgid, 'authtan' => $authtan));

                if (isset($org->orgid) && $org->orgid == $orgid) {
                    $org->name = $name;
                    $org->authenticated = 1;
                    $org->authtan = '';

                    require_once($CFG->dirroot . '/lib/coursecatlib.php');
                    require_once($CFG->dirroot . '/course/externallib.php');

                    // Create a course category for this org
                    $data = new \stdClass();
                    $data->name = $org->name;
                    $data->description = $org->name;
                    $data->idnumber = $org->orgid;
                    $category = coursecat::create($data);
                    $org->categoryid = $category->id;

                    $reply["name"] = $org->name;
                    $reply["categoryid"] = $org->categoryid;

                    // Create an org-course for this org
                    $orgcoursebasement = get_config('block_eduvidual', 'orgcoursebasement');
                    $basement = $DB->get_record('block_eduvidual_basements', array('courseid' => $orgcoursebasement));
                    if ($basement->courseid > 0) {
                        // Grant a role that allows course duplication in source and target category
                        $basecategory = $DB->get_record('course', array('id' => $basement->courseid));
                        $sourcecontext = context_coursecat::instance($basecategory->category);
                        $targetcontext = context_coursecat::instance($org->categoryid);
                        $roletoassign = 1; // Manager
                        $revokesourcerole = true;
                        $revoketargetrole = true;
                        $roles = get_user_roles($sourcecontext, $USER->id, false);
                        foreach($roles AS $role) {
                            if ($role->roleid == $roletoassign) {
                                // User had this role before - we do not revoke!
                                $revokesourcerole = false;
                                $reply['msgs'][] = 'Has already been manager in source';
                            }
                        }
                        $roles = get_user_roles($targetcontext, $USER->id, false);
                        foreach($roles AS $role) {
                            if ($role->roleid == $roletoassign) {
                                // User had this role before - we do not revoke!
                                $revoketargetrole = false;
                                $reply['msgs'][] = 'Has already been manager in target';
                            }
                        }
                        $reply['msgs'][] = 'Assigning role manager in source/target';
                        role_assign($roletoassign, $USER->id, $sourcecontext->id);
                        role_assign($roletoassign, $USER->id, $targetcontext->id);

                        // Duplicate course
                        $course = core_course_external::duplicate_course($basement->courseid, 'Digitaler Schulhof (' . $org->orgid . ')', $org->orgid, $org->categoryid, true);
                        $org->courseid = $course["id"];
                        $course['summary'] = '<p>Digitaler Schulhof der Schule ' . $org->name . '</p>';
                        $DB->update_record('course', $course);

                        // Revoke role that allows course duplication in source and target category
                        if ($revokesourcerole) {
                            role_unassign($roletoassign, $USER->id, $sourcecontext->id);
                            $reply['msgs'][] = 'Revoke role from source';
                        }
                        if ($revoketargetrole) {
                            role_unassign($roletoassign, $USER->id, $targetcontext->id);
                            $reply['msgs'][] = 'Revoke role from target';
                        }
                    }
                    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                    $reply['roleset'] = block_eduvidual_lib_enrol::role_set($USER->id, $org, 'Manager');

                    $DB->update_record('block_eduvidual_org', $org);

                    $messagehtml = $OUTPUT->render_from_template(
                        'block_eduvidual/register_mail_completed',
                        (object) array(
                            'categoryurl' => $CFG->wwwroot . '/course/index.php?categoryid=' . $org->categoryid,
                            'managerscourseurl' => $CFG->wwwroot . '/course/view.php?id=398',
                            'orgid' => $org->orgid,
                            'orgname' => $org->name,
                            'subject' => get_string('mailregister:2:header', 'block_eduvidual'),
                            'wwwroot' => $CFG->wwwroot,
                        )
                    );

                    $messagetext = html_to_text($messagehtml);

                    $subject = get_string('mailregister:2:subject' , 'block_eduvidual');

                    $fromuser = core_user::get_support_user();

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
                    $ccmails = explode(',', get_config('block_eduvidual', 'registrationcc'));
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
            }
        break;
    }
} else {
    $reply['error'] = get_string('registration:loginfirst', 'block_eduvidual');
}
