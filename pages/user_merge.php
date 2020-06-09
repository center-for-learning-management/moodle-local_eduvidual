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

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/eduvidual/block_eduvidual.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/local/eduvidual/pages/user_merge.php', array());
$PAGE->set_title(get_string('user:merge_accounts', 'local_eduvidual'));
$PAGE->set_heading(get_string('user:merge_accounts', 'local_eduvidual'));
//$PAGE->set_cacheable(false);

local_eduvidual::print_app_header();

$users = $DB->get_records('user', array('email' => $USER->email, 'suspended' => 0));
$keep = optional_param('user_keep', 0, PARAM_INT);
if ($keep > 0) {
    $user_to_keep = $DB->get_record('user', array('id' => $keep));
    echo $OUTPUT->render_from_template(
        'local_eduvidual/user_merge_keep',
        $user_to_keep
    );

    require_once($CFG->dirroot . '/admin/tool/mergeusers/lib/autoload.php');
    //may abort execution if database not supported, for security
    $mut = new MergeUserTool();
    // Search tool for searching for users and verifying them
    $mus = new MergeUserSearch();

    foreach ($users AS $user) {
        // Just to make sure we only touch users with matching mailaddresses
        if ($user->email != $USER->email) continue;
        // Continue when this is the user we want to keep
        if ($user->id == $keep) continue;
        echo $OUTPUT->render_from_template(
            'local_eduvidual/user_merge_merge',
            $user
        );
        list($fromuser, $oumessage) = $mus->verify_user($user->id, 'id');
        list($touser, $numessage) = $mus->verify_user($user_to_keep->id, 'id');
        if ($fromuser === NULL || $touser === NULL) {
            echo $OUTPUT->render_from_template(
                'local_eduvidual/alert',
                (object) array('content' => $oumessage . '<br />' . $numessage, 'type' => 'warning')
            );
        } else {
            // Merge the users
            $log = array();
            $success = true;
            list($success, $log, $logid) = $mut->merge($touser->id, $fromuser->id);
            $fromusero = $DB->get_record('user', array('id' => $fromuser->id));
            $fromusero->email .= '.' . time();
            $fromusero->deleted = 1;
            $DB->update_record('user', $fromusero);
        }
    }
    if ($USER->id != $user_to_keep->id && $USER->email == $user_to_keep->email) {
        // Login as new user
        complete_user_login($user_to_keep);
        redirect($CFG->wwwroot . '/my');
    }
}

$params = (object) array(
    'dashboard' => $CFG->wwwroot . '/my',
    'users' => array()
);
$users = $DB->get_records('user', array('email' => $USER->email, 'suspended' => 0));
if (count(array_keys($users)) > 1) {
    foreach($users AS $user) {
        switch($user->auth) {
            case 'manual': case 'email': $user->loginhint = 'Direct Login'; break;
            case 'lti': $user->loginhint = 'From External System via LTI'; break;
            case 'mnet':
                $user->loginhint = 'Via ';
                $mnet = $DB->get_record('mnet_host', array('id' => $user->mnethostid));
                $user->loginhint .= $mnet->name . ' (' . $mnet->wwwroot . ')';
            break;
            case 'oauth2':
                $user->loginhint = 'Via ';
                $issuer = $DB->get_records_sql('SELECT oi.id,oi.name FROM {oauth2_issuer} oi,{auth_oauth2_linked_login} oll WHERE oi.id=oll.issuerid AND oll.userid=?', array($user->id));
                foreach($issuer AS $o) {
                    $user->loginhint .= $o->name;
                }
            break;
        }
        $params->users[] = $user;
    }


    echo $OUTPUT->render_from_template(
        'local_eduvidual/user_merge',
        $params
    );
} else {
    echo $OUTPUT->render_from_template(
        'local_eduvidual/user_merge_ok',
        $params
    );
}


local_eduvidual::print_app_footer();
