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

ini_set('max_execution_time', 0);

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');

$mail = optional_param('mail', '', PARAM_EMAIL);
$code = optional_param('code', '', PARAM_TEXT);

$ctx = \context_user::instance($USER->id);
$PAGE->set_context($ctx);
//$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/eduvidual/pages/user_merge.php', array());
$PAGE->set_title(get_string('user:merge_accounts', 'local_eduvidual'));
$PAGE->set_heading(get_string('user:merge_accounts', 'local_eduvidual'));
//$PAGE->set_cacheable(false);

//$PAGE->navbar->add(get_string('user:merge_accounts', 'local_eduvidual'), $PAGE->url);

echo $OUTPUT->header();

if (!empty($mail) && empty($code)) {
    $users = array_values($DB->get_records('user', [ 'email' => $mail, 'suspended' => 0 ]));
    if (count($users) == 0) {
        $params = [
            'content' => get_string('user:merge_accounts:mailnotfound', 'local_eduvidual'),
            'type' => 'danger',
        ];
        echo $OUTPUT->render_from_template('local_eduvidual/alert', $params);
        $mail = '';
    } elseif (count($users) > 1) {
        $params = [
            'content' => get_string('user:merge_accounts:mailmultiplefound', 'local_eduvidual'),
            'type' => 'danger',
        ];
        echo $OUTPUT->render_from_template('local_eduvidual/alert', $params);
        $mail = '';
    } else {
        $cache = \cache::make('local_eduvidual', 'session');
        $sendcode = uniqid(rand());
        $cache->set('user_merge_code', $sendcode);
        $cache->set('user_merge_mail', $mail);
        $cache->set('user_merge_user', $users[0]->id);
        $url = new \moodle_url('/local/eduvidual/pages/user_merge.php', [ 'mail' => $mail, 'code' => $sendcode]);

        $params = [
            'code' => $sendcode,
            'fullname' => \fullname($USER),
            'url' => $url->__toString(),
        ];
        $messagehtml = get_string('user:merge_accounts:mailtext', 'local_eduvidual', $params);
        $messagetext = html_to_text($messagehtml);

        $subject = get_string('user:merge_accounts:mailsubject' , 'local_eduvidual');

        $fromuser = \core_user::get_support_user();

        email_to_user($USER, $fromuser, $subject, $messagetext, $messagehtml, "", true);
    }
}

if (!empty($mail) && !empty($code)) {
    $cache = \cache::make('local_eduvidual', 'session');
    $sessioncode = $cache->get('user_merge_code');
    $sessionmail = $cache->get('user_merge_mail');
    $sessionuser = $cache->get('user_merge_user');

    if ($sessioncode != $code || $sessionmail != $mail) {
        $params = [
            'content' => get_string('user:merge_accounts:invalidcodeormail', 'local_eduvidual'),
            'type' => 'danger',
        ];
        echo $OUTPUT->render_from_template('local_eduvidual/alert', $params);
    } else {
        $params = [
            'content' => get_string('user:merge_accounts:mergestarted', 'local_eduvidual'),
            'type' => 'info',
        ];
        echo $OUTPUT->render_from_template('local_eduvidual/alert', $params);
        flush();

        $oldroles = \local_eduvidual\locallib::get_user_memberships($sessionuser);
        foreach ($oldroles as $oldrole) {
            \local_eduvidual\lib_enrol::role_set($USER->id, $oldrole->orgid, $oldrole->role);
        }

        require_once($CFG->dirroot . '/admin/tool/mergeusers/lib/autoload.php');
        //may abort execution if database not supported, for security
        $mut = new MergeUserTool();
        // Search tool for searching for users and verifying them
        $mus = new MergeUserSearch();
        $mus->verify_user($sessionuser, 'id');
        $mus->verify_user($USER->id, 'id');

        ob_start();
        $mut->merge($USER->id, $sessionuser);
        $debug = ob_get_contents();
        ob_end_clean();

        $params = [
            'content' => get_string('user:merge_accounts:mergefinished', 'local_eduvidual'),
            'type' => 'success',
        ];
        echo $OUTPUT->render_from_template('local_eduvidual/alert', $params);

        $cache->delete('user_merge_code');
        $cache->delete('user_merge_mail');
        $cache->delete('user_merge_user');

        $mail = '';
        $code = '';
    }

}

$params = [
    'code' => $code,
    'mail' => $mail,
    'user' => $USER,
];

echo $OUTPUT->render_from_template('local_eduvidual/user_merge_form', $params);

echo $OUTPUT->footer();
