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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
    if (optional_param('section', '', PARAM_TEXT) == 'blocksettingeduvidual') {
        $PAGE->requires->css('/blocks/eduvidual/style/main.css');
    }
    $actions = block_eduvidual::get_actions('admin');
    $links = "<div class=\"grid-eq-3\">";
    foreach($actions AS $action => $name) {
        $links .= '<a class="btn" href="' . $CFG->wwwroot . '/blocks/eduvidual/pages/admin.php?act=' . $action . '">' . get_string($name, 'block_eduvidual') . '</a>';
    }
    $links .= "</div>";
    $settings->add(new admin_setting_heading('block_eduvidual_actions', get_string('action', 'block_eduvidual'), $links));

    $settings->add(new admin_setting_heading('block_eduvidual_others', get_string('other'), ''));
    $settings->add(
        new admin_setting_configtext(
            'block_eduvidual/supportcourse_template',
            get_string('admin:supportcourse_template', 'block_eduvidual'),
            get_string('admin:supportcourse_template:description', 'block_eduvidual'),
            '',
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_eduvidual/mapquest_apikey',
            get_string('admin:map:mapquest:apikey', 'block_eduvidual'),
            get_string('admin:map:mapquest:apikey:description', 'block_eduvidual'),
            '',
            PARAM_TEXT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'block_eduvidual/google_apikey',
            get_string('admin:map:google:apikey', 'block_eduvidual'),
            get_string('admin:map:google:apikey:description', 'block_eduvidual'),
            '',
            PARAM_TEXT
        )
    );
}
