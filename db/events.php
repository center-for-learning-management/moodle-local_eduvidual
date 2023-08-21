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

defined('MOODLE_INTERNAL') || die;

$observers = array(
    array(
        'eventname' => '\core\event\course_created',
        'callback' => '\local_eduvidual\observer\course_created::event',
        'includefile' => '/local/eduvidual/classes/observer/course_created.php',
        'priority' => 9999,
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_eduvidual\observer\login::event',
        'includefile' => '/local/eduvidual/classes/observer/login.php',
        'priority' => 9999,
    ),
    array(
        'eventname' => '\core\event\user_updated',
        'callback' => '\local_eduvidual\observer\user_updated::event',
        'priority' => 1,
        'internal' => false,
    ),
);

$events = array(
    '\core\event\course_module_created',
    '\core\event\course_module_deleted',
    '\core\event\course_module_updated',
    '\core\event\course_updated',

);
foreach ($events as $event) {
    $observers[] = array(
        'eventname' => $event,
        'callback' => '\local_eduvidual\observer\course_changed::event',
        'includefile' => '/local/eduvidual/classes/observer/course_changed.php',
        'priority' => 9999,
    );
}
