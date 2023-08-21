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
 *             2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (\local_eduvidual\locallib::get_orgrole($org->orgid) != "Manager" && !is_siteadmin()) {
    throw new \moodle_exception('js:missing_permission', 'local_eduvidual');
}

require_once($CFG->dirroot . '/local/eduvidual/classes/lib_import.php');
$helper = new local_eduvidual_lib_import();
$compiler = new local_eduvidual_lib_import_compiler_user();
$helper->set_compiler($compiler);
$helper->set_fields(array('id', 'username', 'email', 'firstname', 'lastname', 'password', 'forcechangepassword', 'cohorts_add', 'cohorts_remove', 'bunch'));

if (isset($_FILES['local_eduvidual_manage_usersimport'])) {
    $filetype = strtolower(substr($_FILES['local_eduvidual_manage_usersimport']['name'], strpos($_FILES['local_eduvidual_manage_usersimport']['name'], '.')));
    if ($filetype != '.xlsx') {
        $url = new \moodle_url('/local/eduvidual/pages/manage.php', ['orgid' => $orgid, 'act' => 'users']);
        throw new \moodle_exception('manage:createuserspreadsheet:import:filetypeerror', 'local_eduvidual', $url, ['filetype' => $filetype]);
    }

    $helper->load_file($_FILES['local_eduvidual_manage_usersimport']['tmp_name']);
    $objs = $helper->get_rowobjects();
    $fields = $helper->get_fields();

    echo $OUTPUT->render_from_template('local_eduvidual/manage_usersimport', ['orgid' => $orgid, 'users' => $objs]);
}
