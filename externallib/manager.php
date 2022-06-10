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
    public static function create_users_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'orgid'),
            'firstname' => new external_value(PARAM_TEXT, 'firstname'),
            'lastname' => new external_value(PARAM_TEXT, 'lastname'),
            'email' => new external_value(PARAM_TEXT, 'email'),
            'role' => new external_value(PARAM_TEXT, 'role'),
            'cohorts_add' => new external_value(PARAM_TEXT, 'cohorts_add'),
            'cohorts_remove' => new external_value(PARAM_TEXT, 'cohorts_remove'),
            'password' => new external_value(PARAM_TEXT, 'password'),
            'forcechangepassword' => new external_value(PARAM_TEXT, 'forcechangepassword'),
        ));
    }
    public static function create_users($orgid, $firstname, $lastname, $email, $role, $cohorts_add, $cohorts_remove, $password, $forcechangepassword) {
        global $CFG, $DB, $org;
        $params = self::validate_parameters(self::create_users_parameters(), [
            'orgid' => $orgid,
            'firstname' => $firstname, 'lastname' => $lastname, 'email' => $email,
            'role' => $role, 'cohorts_add' => $cohorts_add, 'cohorts_remove' => $cohorts_remove,
            'password' => $password, 'forcechangepassword' => $forcechangepassword
        ]);

        $ret = (object) [
            'status' => 0, // 0 ... unknown, 1 ... success, -1 ... failed
            'message' => '', // On error contains error message
        ];

        // Check if orgid is valid.
        $org = \local_eduvidual\locallib::get_org('orgid', $params['orgid']);
        if (empty($org->orgid) || empty($org->categoryid)) {
            throw new \moodle_exception('no such organisation found or not registered');
        }
        // Check if we can manage this org
        $context = \context_coursecat::instance($org->categoryid);
        require_capability('local/eduvidual:canmanage', $context);

        // Validate user data
        require_once("$CFG->dirroot/local/eduvidual/classes/lib_import.php");
        $compiler = new local_eduvidual_lib_import_compiler_user();
        $obj = $compiler->compile((object)$params);

        // Check if user can be created / updated.
        if (empty($obj->payload->processed)) {
            $ret->status = -1;
            $ret->message = $obj->payload->action;
        } else {
            require_once("$CFG->dirroot/user/lib.php");
            $action = 'update'; // only used to indicate suitable return message.
            if (empty($obj->id)) {
                $action = 'create';
                // Create user.
                $user = (object) [
                    'confirmed'     => 1,
                    'mnethostid'    => 1,
                    'username'      => $obj->username,
                    'firstname'     => $obj->firstname,
                    'lastname'      => $obj->lastname,
                    'email'         => $obj->email,
                    'auth'          => 'manual',
                    'lang'          => 'de',
                    'calendartype'  => 'gregorian',
                ];
                try {
                    $user->id = \user_create_user($user, false, false);
                    $user->idnumber = $user->id;
                    // @todo attention, possible read-after-write gap!
                    $DB->set_field('user', 'idnumber', $user->idnumber, array('id' => $user->id));
                    $user->secret = \local_eduvidual\locallib::get_user_secret($user->id);
                    if (empty($obj->password)) {
                        $obj->password = $user->secret;
                    }
                    \update_internal_user_password($user, $obj->password, false);
                    \local_eduvidual\lib_enrol::choose_background($user->id);
                    \core\event\user_created::create_from_userid($user->id)->trigger();
                } catch(Exception $e) {
                    throw new \moodle_exception('could not create user');
                } finally {
                    if (!empty($user->id)) {
                        $obj->id = $user->id;
                    }
                }
            } else {
                $user = \core_user::get_user($obj->id);
            }

            if (empty($user->id)) {
                $ret->status = -1;
                $ret->message = 'no user object';
            } else {
                $user->firstname = $obj->firstname;
                $user->lastname = $obj->lastname;
                $user->email = $obj->email;
                $local_auth_methods = [ 'manual', 'email'];
                if (in_array($user->auth, $local_auth_methods)) {
                    // For these methods we also update the username.
                    $sql = "SELECT id,username,email
                                FROM {user}
                                WHERE username LIKE ?
                                    AND id <> ?";
                    $params = array($obj->username, $user->id);
                    $otheru = $DB->get_record_sql($sql, $params);
                    if (empty($otheru->id)) {
                        // Ok, we can also update the username.
                        $user->username = $obj->username;
                    }
                }
                $uservalidation = \core_user::validate($user);
                if ($uservalidation !== true) {
                    $ret->status = -1;
                    $ret->message = 'invalid user data';
                } else {
                    $DB->update_record('user', $user);
                    // Set password and forcechangepassword.
                    if (!empty($obj->password)) {
                        \update_internal_user_password($user, $obj->password, false);
                    }
                    if (!empty($obj->forcechangepassword) && intval($obj->forcechangepassword) == 1) {
                        \set_user_preference('auth_forcepasswordchange', true, $user->id);
                    }
                    if (!empty($obj->forcechangepassword) && intval($obj->forcechangepassword) == -1) {
                        \set_user_preference('auth_forcepasswordchange', false, $user->id);
                    }
                    if (!empty($obj->cohorts_add)) {
                        \local_eduvidual\lib_enrol::cohorts_add($user->id, $org, $obj->cohorts_add);
                    }
                    if (!empty($obj->cohorts_remove)) {
                        \local_eduvidual\lib_enrol::cohorts_remove($user->id, $org, $obj->cohorts_remove);
                    }
                    // Set role.
                    \local_eduvidual\lib_enrol::role_set($user->id, $org, $obj->role);
                    // Set status message.
                    $ret->status = 1;
                    if (strtolower($user->role) == 'remove') {
                        $ret->message = get_string('import:removed', 'local_eduvidual', array('id' => $user->id));
                    } else if ($action == 'update') {
                        $ret->message = get_string('import:updated', 'local_eduvidual', array('id' => $user->id));
                    } else if ($action == 'create') {
                        $ret->message = get_string('import:created', 'local_eduvidual', array('id' => $user->id));
                    }
                }
            }
        }

        return $ret;
    }
    public static function create_users_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, '0 ... unknown, -1 ... failed, 1 ... succeeded'),
                'message' => new external_value(PARAM_RAW, 'additional text.'),
            )
        );
    }


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
                $DB->set_field('user', 'firstname', $params['firstname'], [ 'id' => $params['userid']]);
                $DB->set_field('user', 'lastname', $params['lastname'], [ 'id' => $params['userid']]);
                $DB->set_field('user', 'email', $params['email'], [ 'id' => $params['userid']]);

                $sql = "SELECT id
                            FROM {user}
                            WHERE username LIKE ?
                                OR email LIKE ?";
                $params = [ $user->email, $user->email ];
                $otheru = $DB->get_record_sql($sql, $params);
                if (empty($otheru->id)) {
                    $DB->set_field('user', 'username', $params['email'], [ 'id' => $params['userid']]);
                }

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
