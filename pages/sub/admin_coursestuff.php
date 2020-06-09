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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

$orgcoursebasement = get_config('local_eduvidual', 'orgcoursebasement');
$basements = \local_eduvidual\lib_enrol::get_course_basements('system');
$keys = array_keys($basements);
$orgcoursebasements = array();

foreach($keys AS $key) {
    $_basements = array();
    foreach($basements[$key] AS $basement) {
        if ($orgcoursebasement == $basement->id) {
            $basement->selected = 1;
        }
        $_basements = $basement;
    }
    $orgcoursebasements[] = (object)array(
        'key' => $key,
        'basements' => $basements[$key]
    );
}

echo $OUTPUT->render_from_template(
    'local_eduvidual/admin_coursestuff',
    (object) array(
        'allmanagerscourses' => get_config('local_eduvidual', 'allmanagerscourses'),
        'coursebasements' => get_config('local_eduvidual', 'coursebasements'),
        'dropzonepath' => get_config('local_eduvidual', 'dropzonepath'),
        'ltiresourcekey' => get_config('local_eduvidual', 'ltiresourcekey'),
        'orgcoursebasements' => $orgcoursebasements,
        'protectedorgs' => get_config('local_eduvidual', 'protectedorgs'),
        'registrationcc' => get_config('local_eduvidual', 'registrationcc'),
        'registrationsupport' => get_config('local_eduvidual', 'registrationsupport'),
        'trashcategory' => get_config('local_eduvidual', 'trashcategory'),

    )
);
