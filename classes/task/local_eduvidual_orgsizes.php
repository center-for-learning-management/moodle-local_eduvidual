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
 * @copyright  2020 Center for Learning Management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

/**
 * für statistiken: in jedem Kursbereich (=Schule) wird der verwendet Speicherplatz angezeigt (für Admins)
 */
class local_eduvidual_orgsizes extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('orgsizes:title', 'local_eduvidual');
    }

    public function execute() {
        global $CFG, $DB;
        $sql = "SELECT id,orgid,categoryid
                    FROM {local_eduvidual_org}
                    WHERE categoryid>0";
        $orgs = $DB->get_records_sql($sql, array());
        require_once($CFG->dirroot . '/local/eduvidual/classes/lib_manage.php');
        foreach ($orgs AS $org) {
            $filesize = \local_eduvidual\lib_manage::get_category_filesize($org->categoryid);
            echo "Read $org->orgid for category $org->categoryid with size $filesize<br />\n";
            if (!empty($filesize)) {
                $DB->set_field('local_eduvidual_org', 'orgsize', $filesize, array('categoryid' => $org->categoryid));
            }
        }
    }
}
