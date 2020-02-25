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
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib {
    /**
     * Get all explevels of the current user
     * @param user either userid or object. If empty use $USER.
     * @return array with all roleids.
     */
    public static function get_explevels($user = null) {
        if (empty($user)) {
            global $USER;
            $user = $USER;
        } elseif (is_int($user)) {
            $user = $DB->get_record('user', array('id' => $user), '*', IGNORE_MISSING);
        }
        if (empty($user->id)) return array();

        $valid_moolevels = explode(',', get_config('block_eduvidual', 'moolevels'));
        $found_moolevels = array();
        if (count($valid_moolevels) > 0) {
            $context = \context_system::instance();
            $roles = get_user_roles($context, $USER->id, true);
            foreach ($roles AS $role) {
                if (in_array($role->roleid, $valid_moolevels)) {
                    $found_moolevels[] = $role->roleid;
                }
            }
        }
        return $found_moolevels;
    }
}
