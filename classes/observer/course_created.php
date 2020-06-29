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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\observer;

defined('MOODLE_INTERNAL') || die;


class course_created {
    public static function event($event) {
        global $CFG, $DB, $PAGE, $SESSION, $USER;
        $debug = false; // ($USER->id == 3707); //false;
        $data = (object)$event->get_data();
        //error_log("COURSE WAS CREATED");
        //error_log(json_encode($data, JSON_NUMERIC_CHECK));
    }
}
