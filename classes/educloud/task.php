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
 * @copyright  2021 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates a backup when a template course has been modified.
*/

namespace local_eduvidual\educloud;

defined('MOODLE_INTERNAL') || die;

class task extends \core\task\adhoc_task {
    /**
     * Executes a synchronisation action to univention portal.
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data->userid)) {
            throw new \moodle_exception('educloud:exception:nouseridgiven', 'local_eduvidual');
        }
        $userid = $data->userid;
        $user = \core_user::get_user($userid);
        if (empty($user->id)) {
            throw new \moodle_exception('educloud:exception:userwaserased', 'local_eduvidual', '', ['userid' => $userid]);
        }
        $educloudorgs = \local_eduvidual\educloud\user::get_orgs($user->id);
        if (count($educloudorgs) > 0) {
            // There should be a user in Univention.
            $ucsuser = \local_eduvidual\educloud\user::get($user->id);
            if (empty($ucsuser->name)) {
                mtrace("create #$user->id in univention");
                \local_eduvidual\educloud\user::create($user);
            } else {
                mtrace("update #$user->id to univention");
                \local_eduvidual\educloud\user::update($user);
            }
        } else {
            // Any user in Univention must be removed.
            mtrace("remove #$user->id from univention");
            \local_eduvidual\educloud\user::delete($user);
        }
    }
}
