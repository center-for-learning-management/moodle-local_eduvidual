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
 * @copyright  2021 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\observer;

defined('MOODLE_INTERNAL') || die;


class user_updated {
    // wenn der user seine emailadresse ändert, könnte sich der benutzer mit der alten und neuen emailadresse anmelden
    // wenn der benutzername mit dieser email bereits existiert, wird die emailadresse nicht auf den Benutzernamen übertragen
    public static function event(\core\event\base $event) {
        global $CFG, $DB;

        $enabled = get_config('local_eduvidual', 'emailmustbeusername');
        if (empty($enabled)) {
            error_log('ERROR \local_eduvidual\observer\user_updated is not enabled.');
            return;
        }

        if (empty($CFG->extendedusernamechars)) {
            error_log('ERROR \local_eduvidual\observer\user_updated does not work if specialchars are not allowed for usernames');
            return;
        }

        $entry = (object)$event->get_data();
        $user = $DB->get_record('user', ['id' => $entry->relateduserid]);
        if ($user->username != $user->email) {
            // Check if there is no user already using this username within the same auth-type.
            $chk = $DB->get_record('user', ['username' => $user->email, 'auth' => $user->auth, 'mnethostid' => $user->mnethostid]);
            if (empty($chk->id)) {
                $user->username = $user->email;
                $DB->set_field('user', 'username', $user->username, ['id' => $user->id]);
            }
        }

        $userid = $entry->relateduserid;
        $educloudorgs = \local_eduvidual\educloud\user::get_orgs($userid);
        if (count($educloudorgs) > 0) {
            mtrace("Schedule sync with eduCloud for user #$userid");
            \local_eduvidual\educloud\user::action($userid);
        } else {
            mtrace("User #$userid is not in any org that uses educloud");
        }
    }
}
