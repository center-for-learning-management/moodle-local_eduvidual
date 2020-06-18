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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage( 'local_eduvidual_settings', ''); // We ommit the label, so that it does not show the heading.
    $ADMIN->add('localplugins', new admin_category('local_eduvidual', get_string('pluginname', 'local_eduvidual')));
    $ADMIN->add('local_eduvidual', $settings);
    if (optional_param('category', '', PARAM_TEXT) == 'local_eduvidual') {
        $PAGE->requires->css('/local/eduvidual/style/main.css');
    }
    $actions = \local_eduvidual\locallib::get_actions('admin');
    $links = "<div class=\"grid-eq-3\">";
    foreach($actions AS $action => $name) {
        $links .= '<a class="btn" href="' . $CFG->wwwroot . '/local/eduvidual/pages/admin.php?act=' . $action . '">' . get_string($name, 'local_eduvidual') . '</a>';
    }
    $links .= "</div>";
    $settings->add(new admin_setting_heading('local_eduvidual_actions', get_string('action', 'local_eduvidual'), $links));

    $settings->add(new admin_setting_heading('local_eduvidual_others', get_string('other'), ''));
    $settings->add(
        new admin_setting_configtext(
            'local_eduvidual/manage_importusers_spreadsheettemplate',
            get_string('manage:createuserspreadsheet:templateurl', 'local_eduvidual'),
            get_string('manage:createuserspreadsheet:templateurl:description', 'local_eduvidual'),
            '',
            PARAM_URL
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'local_eduvidual/supportcourse_template',
            get_string('admin:supportcourse_template', 'local_eduvidual'),
            get_string('admin:supportcourse_template:description', 'local_eduvidual'),
            '',
            PARAM_INT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'local_eduvidual/mapquest_apikey',
            get_string('admin:map:mapquest:apikey', 'local_eduvidual'),
            get_string('admin:map:mapquest:apikey:description', 'local_eduvidual'),
            '',
            PARAM_TEXT
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'local_eduvidual/google_apikey',
            get_string('admin:map:google:apikey', 'local_eduvidual'),
            get_string('admin:map:google:apikey:description', 'local_eduvidual'),
            '',
            PARAM_TEXT
        )
    );
}
