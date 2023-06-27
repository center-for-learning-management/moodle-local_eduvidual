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
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (!is_siteadmin()) {
    $reply['error'] = get_string('access_denied', 'local_eduvidual');
} else {
    $act = optional_param('act', '', PARAM_TEXT);
    switch ($act) {
        case 'blockfooter':
            $blockfooter = optional_param('blockfooter', '', PARAM_TEXT);
            if (set_config('blockfooter', $blockfooter, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
            break;
        case 'manageorgs_search':
            $search = optional_param('search', '', PARAM_TEXT);
            if (!empty($search))
                $search = '%' . $search . '%';
            $fields = array('id', 'categoryid', 'city', 'country', 'orgid', 'name', 'mail', 'phone', 'street', 'zip');
            $sql = "SELECT o." . implode(',o.', $fields) . ",og.lon,og.lat
                        FROM {local_eduvidual_org} o
                            LEFT JOIN {local_eduvidual_org_gps} og ON o.orgid=og.orgid
                        WHERE o." . implode(' LIKE ? OR o.', $fields);
            $params = array();
            foreach ($fields as $field) {
                $params[] = $search;
            }
            $sql .= " LIKE ? ORDER BY name ASC";

            $reply['sql'] = $sql;
            $reply['orgs'] = $DB->get_records_sql($sql, $params);
            $reply['status'] = 'ok';
            break;
        case 'manageorgs_store':
            $params = optional_param_array('fields', '', PARAM_TEXT);
            $fields = array_keys($params);
            // Validate fields
            $valid = true;
            $reply['errors'] = array();
            $reply['errors_reasons'] = array();
            // Check if required fields are set.
            $required = array('orgid', 'mail', 'name');
            foreach ($required as $_required) {
                if (empty($params[$_required])) {
                    $valid = false;
                    $reply['errors'][] = $_required;
                    $reply['errors_reasons'][] = 'required';
                }
            }
            if (!filter_var($params['mail'], FILTER_VALIDATE_EMAIL)) {
                $valid = false;
                $reply['errors'][] = 'mail';
                $reply['errors_reasons'][] = 'no valid mail';
            }

            $checkorg = $DB->get_record('local_eduvidual_org', array('orgid' => $params['orgid']));
            if (empty($params['id']) && !empty($checkorg->orgid) && $checkorg->id != $params['id']) {
                $valid = false;
                $reply['errors'][] = 'orgid';
                $reply['errors_reasons'][] = 'orgid already given to another org';
            }
            if ($valid) {
                if (!empty($params['id'])) {
                    $org = $DB->get_record('local_eduvidual_org', array('id' => $params['id']));
                    foreach ($fields as $field) {
                        $org->{$field} = $params[$field];
                    }
                    $DB->update_record('local_eduvidual_org', $org);
                    $reply['status'] = 'ok';
                } else {
                    $org = \local_eduvidual\lib_register::create_org((object)$params);
                    if ($org->id > 0) {
                        $reply['status'] = 'ok';
                    }
                }
                if (!empty($params['lat']) && !empty($params['lon'])) {
                    $gps = $DB->get_record('local_eduvidual_org_gps', array('orgid' => $org->orgid));
                    if (!empty($gps->id)) {
                        $gps->lat = $params['lat'];
                        $gps->lon = $params['lon'];
                        $gps->modified = time();
                        $gps->failed = 0;
                        $DB->update_record('local_eduvidual_org_gps', $gps);
                    } else {
                        $gps = (object)array(
                            'orgid' => $org->orgid,
                            'lat' => $params['lat'],
                            'lon' => $params['lon'],
                            'modified' => time(),
                            'failed' => 0,
                        );
                        $DB->insert_record('local_eduvidual_org_gps', $gps);
                    }
                } else {
                    $DB->delete_records('local_eduvidual_org_gps', array('orgid' => $params['orgid']));
                }
            } else {
                $reply['status'] = 'error';
                $reply['error'] = 'Invalid data';
            }
            break;
        case 'navbar':
            $navbar = optional_param('navbar', '', PARAM_TEXT);
            if (set_config('navbar', $navbar, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
            break;
        case 'questioncategories':
            $qc = optional_param_array('questioncategories', NULL, PARAM_INT);
            $sc = optional_param_array('supportcourses', NULL, PARAM_INT);
            // Set to 0 if you require at least one!
            $setrole = get_config('local_eduvidual', 'defaultrolestudent');
            if (count($qc) > -1) {
                set_config('questioncategories', implode(",", $qc), 'local_eduvidual');
                for ($a = 0; $a < count($qc); $a++) {
                    $osc = get_config('local_eduvidual', 'questioncategory_' . $qc[$a] . '_supportcourse');
                    $nsc = $sc[$a];
                    if (!empty($nsc) && $nsc != $osc) {
                        $ctx = \context_course::instance($nsc, IGNORE_MISSING);
                        if (!empty($ctx)) {
                            set_config('questioncategory_' . $qc[$a] . '_supportcourse', $nsc, 'local_eduvidual');
                            $sql = "SELECT userid FROM {local_eduvidual_userqcats} WHERE categoryid = :categoryid";
                            $userids = array_keys($DB->get_records_sql($sql, array('categoryid' => $qc[$a])));
                            \local_eduvidual\lib_enrol::course_manual_enrolments([$nsc], $userids, $setrole);
                            $reply['enrolled_to_' . $nsc] = count($userids);
                        }
                    }
                    if (empty($nsc)) {
                        set_config('questioncategory_' . $qc[$a] . '_supportcourse', '', 'local_eduvidual');
                    }
                }
                $reply['status'] = 'ok';
            } else {
                $reply['error'] = 'config_not_set';
            }
            break;
        case 'requirecapability':
            $requirecapability = optional_param('requirecapability', 0, PARAM_INT);
            if (set_config('requirecapability', $requirecapability, 'local_eduvidual')) {
                $reply['status'] = 'ok';
            }
            break;
        default:
            $reply['error'] = 'Unknown action';
    }
}
