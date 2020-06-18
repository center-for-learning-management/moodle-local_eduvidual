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

    if ($oldversion < 2020052502) {
        $sql = "SELECT userid,background,backgroundcard
                    FROM {local_eduvidual_userextra}";
        $extras = $DB->get_records_sql($sql, array());
        foreach ($extras AS $extra) {
            error_log("Setting $extra->background / $extra->backgroundcard for User #$extra->userid");
            if (!empty($extra->background)) {
                set_user_preference('local_eduvidual_background', $extra->background, $extra->userid);
            }
            if (!empty($extra->backgroundcard)) {
                set_user_preference('local_eduvidual_backgroundcard', $extra->backgroundcard, $extra->userid);
            }
        }
        $table = new xmldb_table('local_eduvidual_userextra');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_block_savepoint(true, 2020052502, 'eduvidual');
    }
    if ($oldversion < 2020060400) {
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('supportcourseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2020060400, 'eduvidual');
    }
    if ($oldversion < 2020060401) {
        $table = new xmldb_table('local_eduvidual_org');
        $field = new xmldb_field('orgmenu', XMLDB_TYPE_TEXT, null, null, null, null, null, 'customcss');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2020060401, 'eduvidual');
    }
    if ($oldversion < 2020061800) {
        // Ensure that all userbunches are synced to cohorts.
        $bunches = $DB->get_records_sql("SELECT * FROM {local_eduvidual_userbunches} ORDER BY orgid ASC", array());
        $org = "";
        foreach ($bunches as $bunch) {
            if (empty($org) || $org->orgid != $bunch->orgid) {
                $org = $DB->get_record('local_eduvidual_org', array('orgid' => $bunch->orgid));
            }
            if (!empty($org->categoryid)) {
                \local_eduvidual\lib_enrol::cohorts_add($bunch->userid, $org, $bunch->bunch);
            }
        }
        upgrade_block_savepoint(true, 2020061800, 'eduvidual');
    }


    return true;
}
