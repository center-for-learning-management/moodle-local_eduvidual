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

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

class educloud extends \core\task\adhoc_task {
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
        $mapped_identifier = \get_user_preferences('educloud_identifier', '', $userid);
        if (!empty($mapped_identifier)) {
            $mapped_user = \local_eduvidual\lib_educloud::api_get_user($mapped_identifier);
            if (!empty($user->deleted)) {
                // @todo remove user account from univention.
                mtrace("remove #$user->id from univention");
            } else {
                mtrace("update #$user->id to univention");
                // @todo update user
                // @todo synchronize groups
            }
        } else {
            mtrace("create #$user->id in univention");
            // @todo create user
            // @todo synchronize groups
        }
        // @todo remove next line to mark ad hoc tasks as finished.
        throw new \moodle_exception('We throw this exception to keep the ad hoc tasks');
    }
}
