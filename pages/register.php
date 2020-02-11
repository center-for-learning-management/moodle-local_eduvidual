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

require_once('../../../config.php');
//require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$orgid = optional_param('orgid', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/blocks/eduvidual/pages/register.php', array());
$PAGE->set_title(get_string('Registration', 'block_eduvidual'));
$PAGE->set_heading(get_string('Registration', 'block_eduvidual'));
//$PAGE->set_cacheable(false);

block_eduvidual::print_app_header();

if ($USER->id > 1) {
    echo $OUTPUT->render_from_template(
        'block_eduvidual/register',
        (object) array(
            'orgid' => $orgid,
            'registrationsupport' => get_config('block_eduvidual', 'registrationsupport'),
            'userid' => $USER->id,
            'wwwroot' => $CFG->wwwroot
        )
    );
} else {
    $SESSION->wantsurl = $PAGE->url . '#'; // This should prevent that wantsurl is an object instead of string!
    echo $OUTPUT->render_from_template(
        'block_eduvidual/register_requirelogin',
        (object) array(
            'registrationsupport' => get_config('block_eduvidual', 'registrationsupport'),
            'userid' => $USER->id,
            'wwwroot' => $CFG->wwwroot
        )
    );
}

block_eduvidual::print_app_footer();
