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

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

$orgcoursebasement = get_config('block_eduvidual', 'orgcoursebasement');
$basements = \block_eduvidual\lib_enrol::get_course_basements('system');
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

$configs = array(
    'manager_lists', 'parent_lists', 'teacher_lists', 'student_lists',
    'oauth_issuers_google', 'oauth_issuers_microsoft',
    'oauth_google_lists', 'oauth_microsoft_lists', 'mnet_lists',
    'dbhost', 'dbname', 'dbpass', 'dbuser', 'ignore_patterns',
);
$params = array('configs' => array(), 'wwwroot' => $CFG->wwwroot);
foreach($configs AS $config) {
    $params['configs'][] = array(
        'field' => $config,
        'type' => ($config == 'dbpass') ? 'password' : 'text',
        'content' => get_config('block_eduvidual', 'phplist_' . $config),
    );
}

if (optional_param('sync', 0, PARAM_INT) == 1) {
    require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_phplist.php');
    $params['syncmessages'] = block_eduvidual_lib_phplist::sync();
    /*
    $params['syncmessages'] = array(
        'type' => 'success',
        'content' => 'Sync completed',
    );
    */
}


echo $OUTPUT->render_from_template('block_eduvidual/admin_phplist', $params);
