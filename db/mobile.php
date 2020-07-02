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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$addons = [
    'local_eduvidual' => [ // Plugin identifier
        'handlers' => [ // Different places where the plugin will display content.
            'createcourse' => [ // Handler unique name (alphanumeric).
                'displaydata' => [
                    'title' => get_string('createcourse:here', 'local_eduvidual'),
                    'icon' => 't/cohort',
                    'class' => '',
                ],

                'delegate' => 'CoreMainMenuDelegate', // Delegate (where to display the link to the plugin)
                'init' => 'course_create_init',
                'method' => 'course_create',
                'offlinefunctions' => [
                ], // Function that needs to be downloaded for offline.
            ],
        ],
        'lang' => [ // Language strings that are used in all the handlers.
        ],
    ],
];
