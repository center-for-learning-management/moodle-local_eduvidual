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

class clean_old_webservice_tokens extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return 'Alte Webservice Tokens löschen';
    }

    public function execute() {
        global $DB;

        $months = 3;

        // Abgelaufene Tokens (prüfung anhand der Gültigkeit) werden gelöscht
        // X Monate nicht verwendete Tokens werden ebenfalls gelöscht
        $DB->execute(
            "DELETE FROM {external_tokens} WHERE
            (validuntil > 0 AND validuntil < ?) OR
            (GREATEST(COALESCE(timecreated,0),COALESCE(lastaccess,0)) < ?)",
            [time(), time() - 60 * 60 * 24 * 30 * $months]);

        echo "Log cleaned successfully\n";
    }
}
