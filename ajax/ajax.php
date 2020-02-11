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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

header('Access-Control-Allow-Origin: *');

require_once('../../../config.php');
//$PAGE->set_context(context_system::instance());

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$PAGE->set_context(context_system::instance());

$module = optional_param('module', '', PARAM_TEXT);
$reply = array('status' => 'error');
$modules = array('admin', 'manage', 'preferences', 'register', 'teacher', 'user');
if (in_array($module, $modules) && file_exists($CFG->dirroot . '/blocks/eduvidual/ajax/sub/' . $module . '.php')) {
    require_once($CFG->dirroot . '/blocks/eduvidual/ajax/sub/' . $module . '.php');
}

die(json_encode($reply, JSON_NUMERIC_CHECK));
