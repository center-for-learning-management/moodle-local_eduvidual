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

// We define the web service functions to install.
$functions = array(
    'local_eduvidual_admin_org_gps' => array(
        'classname'   => 'local_eduvidual_external_admin',
        'methodname'  => 'org_gps',
        'classpath'   => 'local/eduvidual/externallib/admin.php',
        'description' => 'Retrieve orgs in a specific rectangle or gps coordinates.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'local_eduvidual_manager_user_exportform' => array(
        'classname'   => 'local_eduvidual_external_manager',
        'methodname'  => 'user_exportform',
        'classpath'   => 'local/eduvidual/externallib/manager.php',
        'description' => 'Retrieve a HTML form to export a list of users.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'local_eduvidual_manager_user_form' => array(
        'classname'   => 'local_eduvidual_external_manager',
        'methodname'  => 'user_form',
        'classpath'   => 'local/eduvidual/externallib/manager.php',
        'description' => 'Retrieve a HTML form to update user profile data.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'local_eduvidual_manager_user_update' => array(
        'classname'   => 'local_eduvidual_external_manager',
        'methodname'  => 'user_update',
        'classpath'   => 'local/eduvidual/externallib/manager.php',
        'description' => 'Update user profile data.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'local_eduvidual_teacher_createcourse_selections' => array(
        'classname'   => 'local_eduvidual_external_teacher',
        'methodname'  => 'createcourse_selections',
        'classpath'   => 'local/eduvidual/externallib/teacher.php',
        'description' => 'Get possible options for a layer in create course',
        'type'        => 'read',
        'ajax'        => 1,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ),
    'local_eduvidual_user_course_news' => array(
        'classname'   => 'local_eduvidual_external_user',
        'methodname'  => 'course_news',
        'classpath'   => 'local/eduvidual/externallib/user.php',
        'description' => 'Retrieve news from course.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
);
