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
 * @copyright  2021 Center for Learning Management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_licence {
    public static function add_licence($orgid, $comment, $timeexpires) {
        if (!is_siteadmin()) return;

        global $DB, $USER;
        $lic = (object) [
            'orgid' => $orgid,
            'comment' => $comment,
            'createdby' => $USER->id,
            'timecreated' => time(),
            'timeexpires' => $timeexpires,
        ];

        $lic->id = $DB->insert_record('local_eduvidual_org_lic', $lic);
        return $lic;
    }
    public static function check_licence() {
        global $CONTEXT, $DB;

        if (self::system_status()) {
            $org = \local_eduvidual\locallib::get_org_by_context($CONTEXT);
            if (!empty($org->orgid)) {
                $licence = \local_eduvidual\locallib::cache('application', "licence_{$org->orgid}");
                if (empty($licence)) {
                    $sql = "SELECT MAX(timeexpires) AS maxtimeexpires
                                FROM {local_eduvidual_org_lic} ol
                                WHERE timerevoked IS NULL
                                    AND orgid = :orgid";
                    $dbparams = [ 'orgid' => $org->orgid ];
                    $licence = $DB->get_record_sql($sql, $dbparams);
                    if (!empty($licence->maxtimeexpires) && $licence->maxtimeexpires > 0) {
                        $licence = \local_eduvidual\locallib::cache('application', "licence_{$org->orgid}", $licence->maxtimeexpires);
                    } else {
                        $licence = \local_eduvidual\locallib::cache('application', "licence_{$org->orgid}", -1);
                    }
                }
                if (empty($licence) || $licence == -1) {
                    redirect(new \moodle_url('/local/eduvidual/pages/nolicence.php'));
                }
            }

        }

    }

    /**
     * Enables or disables whole system.
     * @param trigger 1 to enable, -1 to disable.
     */
    public static function system_status($trigger = 0) {
        if (is_siteadmin() && in_array($trigger, [1,-1])) {
            set_config('licencesystem_enabled', $trigger, 'local_eduvidual');
        }
        return (get_config('local_eduvidual', 'licencesystem_enabled') == 1);
    }

}
