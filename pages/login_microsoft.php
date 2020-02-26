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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Just redirect to the microsoft login provider

require_once('../../../config.php');

if (!empty($SESSION->wantsurl)) $wantsurl = str_replace($CFG->wwwroot, "", $SESSION->wantsurl);
if (empty($wantsurl)) $wantsurl = '/my';
redirect($CFG->wwwroot . '/auth/oauth2/login.php?id=1&wantsurl=' . rawurlencode($wantsurl) . '&sesskey=' . sesskey());
