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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
	case 'background':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $background = str_replace('none', '', str_replace($CFG->wwwroot, '', optional_param('background', '', PARAM_TEXT)));
			$chk = set_user_preference('local_eduvidual_background', $background);
            if ($chk) {
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'db_error';
            }
        }
    break;
}
