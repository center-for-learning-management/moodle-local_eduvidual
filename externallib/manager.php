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

require_once($CFG->libdir . "/externallib.php");

class local_eduvidual_external_manager extends external_api {
    public static function user_exportform_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'orgid'),
            'userids' => new external_value(PARAM_TEXT, 'userids'),
        ));
    }
    public static function user_exportform($orgid, $userids) {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $params = self::validate_parameters(self::user_exportform_parameters(), array('orgid' => $orgid, 'userids' => $userids));

        if (!\local_eduvidual\locallib::get_orgrole($params['orgid']) == 'Manager' && !is_siteadmin()) {
            return "";
        }
        $org = $DB->get_record('local_eduvidual_org', array('orgid' => $params['orgid']));
        $context = context_coursecat::instance($org->categoryid);
        $PAGE->set_context($context);

        return str_replace('method="get"', 'method="post"', $OUTPUT->download_dataformat_selector(
            get_string('userbulkdownload', 'admin'),
            $CFG->wwwroot . '/local/eduvidual/pages/sub/manage_usersdownload.php',
            'dataformat',
            array('orgid' => $params['orgid'], 'userids' => $params['userids'])
        ));
    }
    public static function user_exportform_returns() {
        return new external_value(PARAM_RAW, 'Returns the form as html.');
    }
    public static function user_form_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'orgid'),
            'userid' => new external_value(PARAM_INT, 'userid'),
        ));
    }
    public static function user_form($orgid, $userid) {
        global $CFG, $DB, $PAGE;
        $params = self::validate_parameters(self::user_form_parameters(), array('orgid' => $orgid, 'userid' => $userid));

        $membership = $DB->get_record('local_eduvidual_orgid_userid', $params);
        if (empty($membership->id) || (\local_eduvidual\locallib::get_orgrole($params['orgid']) != 'Manager' && !is_siteadmin())) {
            // We are not allowed to to this!
            return get_string('missing_permission', 'local_eduvidual');
        } else {
            require_once($CFG->dirroot . '/local/eduvidual/classes/manage_user_form.php');
            $user = $DB->get_record('user', array('id' => $params['userid']));
            $context = context_system::instance();
            $PAGE->set_context($context);
            $mform = new local_eduvidual_manage_user_form();
            $mform->set_data($user);
            return $mform->render();
        }
    }
    public static function user_form_returns() {
        return new external_value(PARAM_RAW, 'Returns the form as html.');
    }
    public static function user_update_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'orgid'),
            'userid' => new external_value(PARAM_INT, 'userid'),
            'firstname' => new external_value(PARAM_TEXT, 'firstname'),
            'lastname' => new external_value(PARAM_TEXT, 'lastname'),
            'email' => new external_value(PARAM_TEXT, 'email'),
        ));
    }
    public static function user_update($orgid, $userid, $firstname, $lastname, $email) {
        global $CFG, $DB, $PAGE;
        $params = self::validate_parameters(self::user_update_parameters(), array('orgid' => $orgid, 'userid' => $userid, 'firstname' => $firstname, 'lastname' => $lastname, 'email' => $email));

        $membership = $DB->get_record('local_eduvidual_orgid_userid', array('orgid' => $params['orgid'], 'userid' => $params['userid']));
        $reply = (object)array(
            'message' => '',
            'subject' => '',
            'success' => 0,
        );
        if (empty($membership->id) || (\local_eduvidual\locallib::get_orgrole($params['orgid']) != 'Manager' && !is_siteadmin())) {
            // We are not allowed to to this!
            $reply->message = get_string('missing_permission', 'local_eduvidual');
            $reply->subject = get_string('error');
            $reply->success = -1;
        } else {
            require_once($CFG->dirroot . '/local/eduvidual/classes/manage_user_form.php');
            $dummydomain = \local_eduvidual\locallib::get_dummydomain();
            $context = context_system::instance();
            $PAGE->set_context($context);
            $mform = new local_eduvidual_manage_user_form();
            $errors = $mform->validation($params, null);
            if (count($errors) == 0) {
                $user = $DB->get_record('user', array('id' => $params['userid']));
                $user->firstname = $params['firstname'];
                $user->lastname = $params['lastname'];
                $user->email = $params['email'];
                $user->username = str_replace($dummydomain, '', $user->email);

                $otheru = $DB->get_record('user', array('username' => $user->email));
                if (empty($otheru->id)) {
                    $user->username = $user->email;
                }

                $DB->update_record('user', $user);
                $reply->message = '';
                $reply->subject = get_string('store:success', 'local_eduvidual');
                $reply->success = 1;
            } else {
                $reply->message = '<ul><li>' . implode('</li><li>', array_values($errors)) . '</li></ul>';
                $reply->subject = get_string('error');
                $reply->success = 0;
            }
        }
        return $reply;
    }
    public static function user_update_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_RAW, 'message to show to user.'),
                'subject' => new external_value(PARAM_TEXT, 'subject to show to user.'),
                'success' => new external_value(PARAM_INT, '1 if successful, 0 if not.'),
            )
        );
    }
}
