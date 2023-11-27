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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_eduvidual_install() {
    global $DB;

    $obj = new \stdClass;
    $obj->name = 'eduvidual internal';
    $obj->sortorder = 1;
    $chk = $DB->get_record('user_info_category', array('name' => $obj->name));
    if (empty($chk->id)) {
        $catid = $DB->insert_record('user_info_category', $obj, true);
    } else {
        $catid = $chk->id;
    }

    $obj = new \stdClass;
    $obj->categoryid = $catid;
    $obj->datatype = 'text';
    $obj->shortname = 'secret';
    $obj->name = 'Users Secret';
    $obj->description = '<p>"Users Secret" is used to enrol a user without name search
        and also works in conjunction with QR Codes.</p>';
    $obj->descriptionformat = 1;
    $obj->sortorder = 1;
    $obj->required = 1;
    $obj->locked = 1;
    $obj->visible = 0;
    $obj->forceunique = 0;
    $obj->signup = 0;
    $obj->defaultdata = '';
    $obj->defaultdataformat = 0;
    $obj->param1 = 5;
    $obj->param2 = 5;
    $obj->param3 = 0;
    $obj->param4 = '';
    $obj->param5 = '';

    $chk = $DB->get_record('user_info_field', array('shortname' => $obj->shortname));
    if (empty($chk->id)) {
        $id = $DB->insert_record('user_info_field', $obj, true);
    } else {
        $id = $chk->id;
    }
    set_config('fieldid_secret', $id, 'local_eduvidual');

    $obj = new \stdClass;
    $obj->categoryid = $catid;
    $obj->datatype = 'text';
    $obj->shortname = 'supportflag';
    $obj->name = 'Supportflag';
    $obj->description = '<p>Use this flag in conjunction with plugin "Auto Group"
        to automatically group users in support forums.</p>';
    $obj->descriptionformat = 1;
    $obj->sortorder = 1;
    $obj->required = 1;
    $obj->locked = 1;
    $obj->visible = 0;
    $obj->forceunique = 0;
    $obj->signup = 0;
    $obj->defaultdata = '';
    $obj->defaultdataformat = 0;
    $obj->param1 = 20;
    $obj->param2 = 20;
    $obj->param3 = 0;
    $obj->param4 = '';
    $obj->param5 = '';

    $chk = $DB->get_record('user_info_field', array('shortname' => $obj->shortname));
    if (empty($chk->id)) {
        $id = $DB->insert_record('user_info_field', $obj, true);
    } else {
        $id = $chk->id;
    }

    set_config('fieldid_supportflag', $id, 'local_eduvidual');
}
