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
require_once($CFG->dirroot . "/local/eduvidual/classes/locallib.php");

class local_eduvidual_external_teacher extends external_api {
    public static function createcourse_selections_parameters() {
        return new external_function_parameters(array(
            'orgid' => new external_value(PARAM_INT, 'orgid'),
            'subcat1' => new external_value(PARAM_RAW, 'subcat1 - value'),
            'subcat2' => new external_value(PARAM_RAW, 'subcat2 - value'),
            'subcat3' => new external_value(PARAM_RAW, 'subcat3 - value'),
        ));
    }
    public static function createcourse_selections($orgid, $subcat1, $subcat2, $subcat3) {
        global $CFG, $DB;
        $params = self::validate_parameters(self::createcourse_selections_parameters(), array('orgid' => $orgid, 'subcat1' => $subcat1, 'subcat2' => $subcat2, 'subcat3' => $subcat3));

        $orgas = \local_eduvidual\locallib::get_organisations('Teacher');
        $org = \local_eduvidual\locallib::get_organisations_check($orgas, $params['orgid']);

        $seltree = array(
            'org' => $org,
            'orgid' => $org->orgid,
            'orgids' => $orgas,
            'subcats1' => \local_eduvidual\locallib::get_orgsubcats($org->orgid, 'subcats1'),
            'subcats1lbl' => $org->subcats1lbl,
            'subcats2lbl' => $org->subcats2lbl,
            'subcats3lbl' => $org->subcats3lbl,
            'subcats4lbl' => $org->subcats4lbl,
        );
        if (!empty($params['subcat1'])) {
            $seltree['subcats2'] = \local_eduvidual\locallib::get_orgsubcats($org->orgid, 'subcats2', $params['subcat1']);
        }
        if (!empty($params['subcat2'])) {
            $seltree['subcats3'] = \local_eduvidual\locallib::get_orgsubcats($org->orgid, 'subcats3', $params['subcat2']);
        }

        for ($a = 1; $a <= 3; $a++) {
            $seltree['subcat' . $a] = $params['subcat' . $a];
            if ($a > 1 && empty($seltree['subcat' . ($a-1)])) $seltree['subcat' . $a] = '';
            if (is_array($seltree['subcats' . $a]) && !empty($params['subcat' . $a]) && !in_array($params['subcat' . $a], $seltree['subcats' . $a])) $seltree['subcat' . $a] = '';
        }

        return json_encode($seltree, JSON_NUMERIC_CHECK);
    }
    public static function createcourse_selections_returns() {
        return new external_value(PARAM_RAW, 'Returns information about possible values at every subcat.');
    }
}
