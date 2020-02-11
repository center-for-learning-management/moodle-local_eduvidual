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
 * @package    local_edumessenger
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

class block_eduvidual_cron extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('cron:title', 'block_eduvidual');
    }

    public function execute() {
        global $DB, $PAGE;
        $PAGE->set_context(\context_system::instance());

        // All Users having role "repofilesystem"
        $roleid = 15;
        $sql = "SELECT userid, contextid FROM {role_assignments} WHERE roleid=?";
        $results = $DB->get_records_sql($sql, array($roleid));
        foreach($results AS $row) {
            \role_unassign($roleid, $row->userid, $row->contextid);
        }
    }
}
