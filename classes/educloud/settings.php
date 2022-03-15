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
 * @copyright  2022 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\educloud;

defined('MOODLE_INTERNAL') || die;

class settings {
    /**
     * Add required settings to admin settings page.
     * @param settings the node settings are attached to.
    **/
    public static function admin_settings_page($settings) {
        global $ADMIN;
        if (empty($ADMIN) || !$ADMIN->fulltree) {
            return;
        }

        $heading = get_string('educloud:settings', 'local_eduvidual');
        $text    = get_string('educloud:settings:description', 'local_eduvidual');
        $settings->add(
            new \admin_setting_heading(
                'local_eduvidual_educloud',
                '',
                "<h3>$heading</h3><p>$text</p>"
            )
        );

        $settings->add(
            new \admin_setting_configtext(
                'local_eduvidual/educloud_apipath',
                get_string('educloud:settings:apipath', 'local_eduvidual'),
                '',
                'https://<urlofunivention>',
                PARAM_URL
            )
        );

        $settings->add(
            new \admin_setting_configtext(
                'local_eduvidual/educloud_apiuser',
                get_string('educloud:settings:apiuser', 'local_eduvidual'),
                '',
                '',
                PARAM_TEXT
            )
        );
        $settings->add(
            new \admin_setting_configpasswordunmask(
                'local_eduvidual/educloud_apipass',
                get_string('educloud:settings:apipass', 'local_eduvidual'),
                '',
                '',
                PARAM_TEXT
            )
        );
        global $CFG;
        if (empty(\get_config('local_eduvidual', 'educloud_sourceid'))) {
            \set_config('educloud_sourceid', substr(md5($CFG->wwwroot), 0, 5), 'local_eduvidual');
        }
        $settings->add(
            new \admin_setting_configtext_with_maxlength(
                'local_eduvidual/educloud_sourceid',
                get_string('educloud:settings:sourceid', 'local_eduvidual'),
                get_string('educloud:settings:sourceid:desc', 'local_eduvidual'),
                substr(md5($CFG->wwwroot), 0, 5),
                PARAM_ALPHANUM,
                5,
                5
            )
        );
    }
}
