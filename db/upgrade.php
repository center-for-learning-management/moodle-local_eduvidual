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
 * @copyright  2019 Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_eduvidual_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020072200) {
        $table = new xmldb_table('local_eduvidual_userextra');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('supportcourseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('orgmenu', XMLDB_TYPE_TEXT, null, null, null, null, null, 'customcss');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $users = $DB->get_records_sql("SELECT id,email FROM {user} WHERE email LIKE '%@doesnotexist.eduvidual.org' OR email LIKE '%@doesnotexist.eduvidual.at'", array());
        foreach ($users AS $user) {
            $DB->set_field('user', 'email', 'a' . $user->id . '@a.eduvidual.at', array('id' => $user->id));
        }

        upgrade_plugin_savepoint(true, 2020072200, 'local', 'eduvidual');
    }

    if ($oldversion < 2020111000) {
        // Define field orgclass to be added to local_eduvidual_org.
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('orgclass', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'orgid');

        // Conditionally launch add field orgclass.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Eduvidual savepoint reached.
        upgrade_plugin_savepoint(true, 2020111000, 'local', 'eduvidual');
    }
    if ($oldversion < 2021011100) {
        // Schedule a course backup for all template courses.
        $courseids = array(
            get_config('local_eduvidual', 'coursebasementempty'),
            get_config('local_eduvidual', 'coursebasementrestore'),
            get_config('local_eduvidual', 'coursebasementtemplate')
        );
        set_config('coursebasement-scheduled', implode(',', $courseids), 'local_eduvidual');
        upgrade_plugin_savepoint(true, 2021011100, 'local', 'eduvidual');
    }

    return true;
}
