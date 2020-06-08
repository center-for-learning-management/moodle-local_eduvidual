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
 * @package    block_edusupport
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

class block_eduvidual_external_user extends external_api {
    public static function orgmenu_parameters() {
        return new external_function_parameters(array(
            'userid' => new external_value(PARAM_INT, 'userid'),
        ));
    }
    public static function orgmenu($userid) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        $params = self::validate_parameters(self::orgmenu_parameters(), array('userid' => $userid));

        if ($params['userid'] != $USER->id) return "";

        $PAGE->set_context(\context_system::instance());
        $orgmenus = \block_eduvidual\lib_helper::orgmenus();

        return $OUTPUT->render_from_template('block_eduvidual/orgmenu', array('menuright' => 1, 'orgmenus' => $orgmenus));
    }
    public static function orgmenu_returns() {
        return new external_value(PARAM_RAW, 'Returns the orgmenu as html.');
    }
}
