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

class local_eduvidual_external_user extends external_api {
    public static function course_news_parameters() {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT, 'courseid'),
        ));
    }

    public static function course_news($userid) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::course_news_parameters(), array('courseid' => $courseid));

        require_login($params['courseid']);

        $reply = (object)array(
            'label' => 'hi', // label for link, if there are no items, leave empty.
            'content' => '', // Content for div, that is shown.
        );

        return $reply;
    }

    public static function course_news_returns() {
        return new external_single_structure(
            array(
                'label' => new external_value(PARAM_TEXT, 'the label to show, if empty, no label'),
                'content' => new external_value(PARAM_RAW, 'the content as HTML'),
            )
        );
    }
}
