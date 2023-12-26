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

if (!is_siteadmin()) {
    throw new \moodle_exception(get_string('access_denied', 'local_eduvidual'));
}

function delete($dry_run = true) {
    global $CFG, $DB;

    if (!isset($CFG->eledia_informinactiveuserafter)) {
        set_config('eledia_informinactiveuserafter', '365');
    }
    if (!isset($CFG->eledia_deleteinactiveuserafter)) {
        set_config('eledia_deleteinactiveuserafter', '120');
    }

    $today = time();

    $informexpired = time() - (((int)$CFG->eledia_informinactiveuserafter) * 24 * 60 * 60);

    // Get inactice user.
    $not_to_check_user = [$CFG->siteguest];
    $admins = get_admins();

    foreach ($admins as $adm) {
        $not_to_check_user[] = $adm->id;
    }

    list ($in_sql, $in_params) = $DB->get_in_or_equal($not_to_check_user, SQL_PARAMS_QM, '', false);

    $sql = "deleted = 0
            AND confirmed = 1 AND id $in_sql
            AND ((lastaccess < $informexpired AND lastaccess > 0)
                OR (lastaccess = 0 AND firstaccess < $informexpired AND firstaccess > 0)
                OR (auth = 'manual' AND firstaccess = 0 AND lastaccess = 0  AND timemodified > 0 AND timemodified < $informexpired))";
    $users_to_delete = $DB->get_records_select('user', $sql, $in_params, '', 'id, email, lang, firstname, lastname, currentlogin, username, lastaccess');

    foreach ($users_to_delete as $key => $user) {
        // sie sind KEINER Schule zugeordnet
        $sql = "SELECT o.*
            FROM {local_eduvidual_org} o,
                 {local_eduvidual_orgid_userid} ou
            WHERE o.orgid=ou.orgid
                AND ou.userid=?";
        $params = array($user->id);
        $memberships = $DB->get_records_sql($sql, $params);

        if ($memberships) {
            unset($users_to_delete[$key]);
        }
    }

    echo "inactive user found: ".count($users_to_delete)."\n";

    foreach ($users_to_delete as $user) {
        echo 'delete: ';
        echo $user->username.' ('.$user->email.')';
        echo ' login: '.date('d.m.Y', max($user->lastlogin, $user->lastaccess));
        echo "\n";
    }

    if ($dry_run) {
        echo "dry run! ending here...\n";
        return;
    }

    // Get user which already get mails.
    $informeduser = []; // $DB->get_records('block_eledia_usercleanup');
    if ($informeduser) {
        // Remove user which are active from table.
        foreach ($informeduser as $iuser) {
            if (!array_key_exists($iuser->userid, $users_to_delete)) {
                $DB->delete_records('block_eledia_usercleanup', array('userid' => $iuser->userid));
                echo "\n $iuser->user active again, deletet from user cleanup table";
            }
        }
    } else {
        echo "\ninformuserlist empty";
    }

    // Mail users.
    echo "\nuser to mail timestamp $informexpired";
    foreach ($users_to_delete as $informuser) {
        if (in_array($informuser->id, $informeduser)) {
            // No mail when already send one.
            continue;
        }

        if (preg_match('!(@doesnotexist.eduvidual.at|@a.eduvidual.at)$!', $informuser->email)) {
            // E-Mail-Adressen mit der Domain @doesnotexist.eduvidual.at bzw. @a.eduvidual.at sollen kein E-Mail erhalten, da diese E-Mail-Adressen nicht existieren.
            continue;
        }

        // TODO
        continue;

        $user = new object();
        $user->lang = $informuser->lang;
        $user->email = $informuser->email;
        $user->mailformat = 1;  // Always send HTML version as well.

        $site = get_site();
        $supportuser = generate_email_supportuser();

        $data = new object();
        $data->userinactivedays = $CFG->eledia_informinactiveuserafter;
        $data->eledia_deleteinactiveuserafter = $CFG->eledia_deleteinactiveuserafter;
        $data->firstname = $informuser->firstname;
        $data->lastname = $informuser->lastname;
        $data->sitename = format_string($site->fullname);
        $data->admin = generate_email_signoff();
        $data->link = $CFG->wwwroot.'/index.php';

        $subject = get_string('email_subject', 'block_eledia_usercleanup', $data);

        $message = get_string('email_message', 'block_eledia_usercleanup', $data);
        $messagehtml = text_to_html(get_string('email_message', 'block_eledia_usercleanup', $data), false, false, true);

        echo "\ntry mail to user $informuser->username and mail: $informuser->email";
        email_to_user($user, $supportuser, $subject, $message, $messagehtml);

        // Save mailed user.
        $saveuserinfo = new object();
        $saveuserinfo->userid = $informuser->id;
        $saveuserinfo->mailedto = $informuser->email;
        $saveuserinfo->timestamp = time();
        $DB->insert_record('block_eledia_usercleanup', $saveuserinfo);
    }

    // Delete users.
    $deleteexpired = ((int)$CFG->eledia_deleteinactiveuserafter) * 24 * 60 * 60;
    $deleteuserids = $DB->get_records_select('block_eledia_usercleanup', "(timestamp + $deleteexpired) < $today AND userid > 2", null, '', 'user');
    $deleteuserlist = [];

    if ($deleteuserids) {
        $deleteuserstring = false;
        foreach ($deleteuserids as $u) {
            if (!$deleteuserstring) {
                $deleteuserstring = "($u->user";
            } else {
                $deleteuserstring .= ", $u->user";
            }
        }
        $deleteuserstring .= ')';

        $deleteuserlist = $DB->get_records_select('user', "id IN $deleteuserstring AND deleted = 0 AND confirmed = 1");
    }

    foreach ($deleteuserlist as $deleteuser) {
        delete_user($deleteuser);
        $DB->delete_records('block_eledia_usercleanup', array('user' => $deleteuser->id));
        echo "\ndeleting inactive user $deleteuser->username";
    }
}

echo $OUTPUT->header();

echo '<pre>';
delete();
echo '</pre>';

echo $OUTPUT->footer();
