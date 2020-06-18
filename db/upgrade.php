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

    /*

        ATTENTION!!!!!!!!!!!
        We have to change the block_eduvidual to local_eduvidual.
        The current tables of the old plugin will be renamed, before the old
        plugin gets uninstalled. Then we will install the local-Plugin,
        delete its tables and rename the old tables for the new plugin.

        Therefore some database changes will not haven taken effect.

        For that reason, AFTER installing the local_plugin, we have to enable
        this piece of code, so that all database changes will take effect!

        ENSURE WE HAVE RUN /local/eduvidual/pages/tools/admin_block2local.php
        BEFORE THIS IS ENABLED!

    if ($oldversion < 2020061800) {
        $table = new xmldb_table('local_eduvidual_userextra');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('supportcourseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_local_savepoint(true, 2020060400, 'eduvidual');
        upgrade_plugin_savepoint(true, 2020061800, 'local', 'eduvidual');
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('orgmenu', XMLDB_TYPE_TEXT, null, null, null, null, null, 'customcss');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2020061800, 'local', 'eduvidual');
    }
    */


    return true;
}
