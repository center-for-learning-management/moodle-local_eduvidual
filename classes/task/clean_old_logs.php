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
 * @copyright  2021 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

class clean_old_logs extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return 'Alte Logs löschen';
    }

    public function execute() {
        $months = get_config('local_eduvidual', 'clean_old_logs_months');
        if (!$months) {
            throw new \moodle_exception('clean_old_logs_months not set');
        }

        $manager = new \tool_log\log\manager();
        $store = new \logstore_database\log\store($manager);
        $extdb = $store->get_extdb();

        if (!$extdb) {
            echo "Log Database not configured\n";
            return false;
        }

        $table = $store->get_config_value('dbtable');

        // delete in batches of 10000 rows
        $num_rows_to_delete_each = 10000;
        $timestamp = time() - 60 * 60 * 24 * 30 * $months;
        $num_rows_to_delete = $extdb->get_field_sql("SELECT COUNT(*) FROM {$table} WHERE timecreated < ?", [$timestamp]);

        for ($i = 0; $i < $num_rows_to_delete; $i+=$num_rows_to_delete_each) {
            $sql = "DELETE FROM {$table} WHERE timecreated < ? LIMIT {$num_rows_to_delete_each}";
            echo $sql."\n";
            $extdb->execute($sql, [$timestamp]);
        }

        echo "Log cleaned successfully\n";
    }
}
