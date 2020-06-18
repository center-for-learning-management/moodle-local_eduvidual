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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;



if (optional_param('import', 0, PARAM_INT) > 0) {
    require_once($CFG->dirroot . '/local/eduvidual/pages/sub/manage_usersimport.php');
    return;
}

$_codes = $DB->get_records_sql('SELECT * FROM {local_eduvidual_org_codes} WHERE orgid=? ORDER BY maturity DESC', array($org->orgid));
$codes = array();
foreach($_codes AS $code) {
    $code->isvalid = ($code->maturity > time());
    $code->role_localized = get_string('role:' . $code->role, 'local_eduvidual');

    $issuer = $DB->get_record('user', array('id' => $code->userid));
    if (!empty($issuer->id)) {
        $code->issuerid = $issuer->id;
        $code->issuerpicture = $OUTPUT->user_picture($issuer, array('size' => 30));
    }
    //$code->maturityreadable = date('Y-m-d H:i:s', $code->maturity);
    $codes[] = $code;
}

echo $OUTPUT->render_from_template(
    'local_eduvidual/manage_users',
    (object) array(
        'codes' => $codes,
        'codes_amount' => count($codes),
        'yyyymmddhhiiss' => date('Y-m-d H:i:s', time() + 60*60*24*30),
        'orgid' => $org->orgid,
        'urlspreadsheet' => get_config('manage_importusers_spreadsheettemplate', 'local_eduvidual'),
        'wwwroot' => $CFG->wwwroot,
    )
);
