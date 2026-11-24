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

$act = optional_param('act', '', PARAM_TEXT);
switch ($act) {
    case 'accesscode':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $orgid = optional_param('orgid', 0, PARAM_INT);
            $code = optional_param('code', '', PARAM_TEXT);
            $entries = $DB->get_records('local_eduvidual_org_codes', ['orgid' => $orgid, 'code' => $code]);
            if (count($entries) > 0) {
                $role = '';
                foreach ($entries as $entry) {
                    if ($entry->maturity > time()) {
                        $role = $entry->role;
                    }
                }
                if (!empty($role)) {
                    // Code is ok - enrol user
                    $reply['enrolment'] = \local_eduvidual\lib_enrol::role_set($USER->id, $orgid, $role);
                    $reply['orgid'] = $orgid;
                    $reply['status'] = 'ok';
                } else {
                    $reply['error'] = 'accesscard:code_obsolete';
                }
            } else {
                $reply['error'] = 'accesscard:code_invalid';
            }
        }
        break;
    case 'seteditor':
        if (isguestuser($USER)) {
            $reply['error'] = 'guestuser:nopermission';
        } else {
            $editor = optional_param('editor', '', PARAM_TEXT);
            $valid = ['', 'atto', 'tinymce', 'textarea'];
            if (!in_array($editor, $valid)) {
                $reply['error'] = 'invalid choice';
            } else {
                if (set_user_preference('htmleditor', $editor)) {
                    $reply['status'] = 'ok';
                }
            }
        }
        break;
    case 'whoami':
        $reply['status'] = 'ok';
        $reply['userid'] = $USER->id;
        break;
}
