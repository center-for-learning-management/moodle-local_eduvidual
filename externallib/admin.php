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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 *             2020 onwards Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

class local_eduvidual_external_admin extends external_api {
    public static function org_gps_parameters() {
        return new external_function_parameters(array(
            'lon1' => new external_value(PARAM_FLOAT, 'longitude to start rectangle'),
            'lon2' => new external_value(PARAM_FLOAT, 'longitude to end rectangle'),
            'lat1' => new external_value(PARAM_FLOAT, 'latitude to start rectangle'),
            'lat2' => new external_value(PARAM_FLOAT, 'latitude to end rectangle'),
            'includenonegroup' => new external_value(PARAM_INT, 'if true we will include orgs that have an unknown status.'),
            'advanceddata' => new external_value(PARAM_INT, 'if 1 we will include postal information.'),
        ));
    }
    public static function org_gps($lon1, $lon2, $lat1, $lat2, $includenonegroup, $advanceddata = 0) {
        global $CFG, $DB, $PAGE;

        
        if (!is_siteadmin()) {
            return json_encode(array());
        }
        $params = self::validate_parameters(self::org_gps_parameters(), array('lon1' => $lon1, 'lon2' => $lon2, 'lat1' => $lat1, 'lat2' => $lat2, 'includenonegroup' => $includenonegroup, 'advanceddata' => $advanceddata));
        $advanceddata = "";
        if ($params['advanceddata'] == 1) {
            $advanceddata = ",o.street,o.zip,o.city,o.district,o.country";
        }
        $sql = "SELECT og.orgid,og.lon,og.lat,o.name,o.authenticated,o.lpf,o.lpfgroup
                    $advanceddata
                    FROM {local_eduvidual_org_gps} og,
                        {local_eduvidual_org} o
                    WHERE o.orgid=og.orgid
                        AND lon>? AND lon<?
                        AND lat>? AND lat<?";
        if ($params['includenonegroup'] != 1) {
            $sql .= " AND (authenticated = 1 OR lpf IS NOT NULL)";
        }
        $orgsinbounds = $DB->get_records_sql($sql, array($params['lon1'],$params['lon2'],$params['lat1'],$params['lat2']));
        return json_encode($orgsinbounds, JSON_NUMERIC_CHECK);
    }
    public static function org_gps_returns() {
        return new external_value(PARAM_RAW, 'Returns orgs as json encoded array.');
    }

}
