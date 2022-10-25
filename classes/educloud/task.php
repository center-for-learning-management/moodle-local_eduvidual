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
        global $DB;
        $data = $this->get_custom_data();
        if (empty($data->userid)) {
            mtrace(get_string('educloud:exception:nouseridgiven', 'local_eduvidual'));
            return;
        }
        $userid = $data->userid;
        $user = \core_user::get_user($userid);
        if (empty($user->id) || !empty($user->deleted)) {
            mtrace(get_string('educloud:exception:userwaserased', 'local_eduvidual', ['userid' => $userid]));
            return;
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
        $userorgs = \local_eduvidual\locallib::get_organisations('*', false);
        $educloudroles = explode(',', \get_config('local_eduvidual', 'educloud_orgroles'));
        mtrace("check if user #$user->id has educloud-roles assigned correctly");
        foreach ($userorgs as $userorg) {
            $context = \context_coursecat::instance($userorg->categoryid);
            if (empty($context->id)) {
                mtrace(" ==> aborting, this organization has an invalid context");
                continue;
            }
            $useseducloud = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $userorg->orgid]);
            if (empty($useseducloud->permitted)) {
                mtrace(" ==> org $userorg->orgid does not use educloud.");
                foreach ($educloudroles as $roleid) {
                    mtrace(" |   unassign $roleid from $userid in $context->id");
                    \role_unassign($roleid, $userid, $context->id);
                }
            } else {
                mtrace(" ==> org $userorg->orgid uses educloud.");
                foreach ($educloudroles as $roleid) {
                    mtrace(" |   assign $roleid to $userid in $context->id");
                    \role_assign($roleid, $userid, $context->id);
                }
            }
        }
    }
}
