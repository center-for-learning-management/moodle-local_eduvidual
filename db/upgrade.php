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
 * @copyright  2019 Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_block_eduvidual_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    /// Add a new column newcol to the mdl_myqtype_options
    if ($oldversion < 2019101000) {
        // Define field lpf to be added to block_eduvidual_org.
        $table = new xmldb_table('block_eduvidual_org');
        $field = new xmldb_field('lpf', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'subcats4lbl');

        // Conditionally launch add field lpf.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field lpfgroup to be added to block_eduvidual_org.
        $field = new xmldb_field('lpfgroup', XMLDB_TYPE_CHAR, '1', null, null, null, null, 'lpf');
        // Conditionally launch add field lpfgroup.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eduvidual savepoint reached.
        upgrade_block_savepoint(true, 2019101000, 'eduvidual');
    }
    if ($oldversion < 2019102800) {
        $orgs = $DB->get_records('block_eduvidual_org', array('authenticated' => 1));
        foreach ($orgs AS $org) {
            $org->subcats1 = implode("\n", explode(',', $org->subcats1));
            $org->subcats2 = implode("\n", explode(',', $org->subcats2));
            $org->subcats3 = implode("\n", explode(',', $org->subcats3));
            $DB->update_record('block_eduvidual_org', $org);
        }
        upgrade_block_savepoint(true, 2019102800, 'eduvidual');
    }
    if ($oldversion < 2019102803) {
        $table = new xmldb_table('block_eduvidual_org_gps');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('orgid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lat', XMLDB_TYPE_NUMBER, '10, 4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lon', XMLDB_TYPE_NUMBER, '10, 4', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2019102803, 'eduvidual');
    }
    if ($oldversion < 2019110201) {
        $table = new xmldb_table('block_eduvidual_org_gps');
        $field = new xmldb_field('modified', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'lon');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2019110201, 'eduvidual');
    }
    if ($oldversion < 2019110205) {
        $table = new xmldb_table('block_eduvidual_org_gps');
        $field = new xmldb_field('failed', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'modified');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2019110205, 'eduvidual');
    }
    if ($oldversion < 2020021100) {
        $table = new xmldb_table('block_eduvidual_org');
        $field = new xmldb_field('orgsize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0', 'lpfgroup');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2020021100, 'eduvidual');
    }
    if ($oldversion < 2020022500) {
        // We need to check all experience-levels. Only keep the highest one!
        $moolevels = explode(',', get_config('block_eduvidual', 'moolevels'));
        rsort($moolevels);
        // We now loop through moolevels, which are in reversed order.
        // Get all userids that have that level and remove any moolevels that are below.
        for ($a = 0; $a < count($moolevels);$a++) {
            $userids = $DB->get_records('role_assignments', array('contextid' => 1, 'roleid' => $moolevels[$a]));
            foreach ($userids AS $userid) {
                for ($b = $a+1; $b < count($moolevels); $b++) {
                    role_unassign($moolevels[$b], $userid->userid, 1);
                }
            }
        }

        upgrade_block_savepoint(true, 2020022500, 'eduvidual');
    }
    if ($oldversion < 2020052502) {
        $sql = "SELECT userid,background,backgroundcard
                    FROM {block_eduvidual_userextra}";
        $extras = $DB->get_records_sql($sql, array());
        foreach ($extras AS $extra) {
            error_log("Setting $extra->background / $extra->backgroundcard for User #$extra->userid");
            if (!empty($extra->background)) {
                set_user_preference('block_eduvidual_background', $extra->background, $extra->userid);
            }
            if (!empty($extra->backgroundcard)) {
                set_user_preference('block_eduvidual_backgroundcard', $extra->backgroundcard, $extra->userid);
            }
        }
        $table = new xmldb_table('block_eduvidual_userextra');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_block_savepoint(true, 2020052502, 'eduvidual');
    }
    if ($oldversion < 2020060400) {
        $table = new xmldb_table('block_eduvidual_org');
        $field = new xmldb_field('supportcourseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2020060400, 'eduvidual');
    }


    return true;
}
