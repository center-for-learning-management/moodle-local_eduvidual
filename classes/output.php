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
 * @copyright  2026 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

class output {
    /**
     * mtrace nur im CLI-/Cron-Kontext - im Web-Kontext (z.B. Login-Hook) würde
     * mtrace den Output direkt echoen.
     */
    public static function mtrace(string $message): void {
        if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
            mtrace($message);
        }
    }

    public static function print_manage_menu(int $orgid, string $active_act = ''): void {
        global $OUTPUT;
        $actions = locallib::get_actions('manage');
        $oactions = [];
        foreach ($actions as $key => $action) {
            if ($key === 'users') {
                $url = new \moodle_url('/local/eduvidual/pages/manage_userlists.php', ['orgid' => $orgid]);
            } else {
                $url = new \moodle_url('/local/eduvidual/pages/manage.php', ['orgid' => $orgid, 'act' => $key]);
            }
            $oactions[] = [
                'action' => $action,
                'key' => $key,
                'localized' => get_string($action, 'local_eduvidual'),
                'selected' => ($key === $active_act),
                'url' => $url,
            ];
        }
        echo $OUTPUT->render_from_template('local_eduvidual/manage_overview', ['actions' => $oactions]);
    }

    public static function print_manage_users_tabs(int $orgid, string $active_tab = ''): void {
        global $CFG, $OUTPUT;
        echo $OUTPUT->render_from_template('local_eduvidual/manage_users_tabs', [
            'orgid' => $orgid,
            'wwwroot' => $CFG->wwwroot,
            'tab_' . $active_tab . '_active' => true,
        ]);
    }
}
