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
 * Mock class for get_content.
 *
 * @package local_eduvidual
 * @copyright 2020 Robert Schrenk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_eduvidual\output;
defined('MOODLE_INTERNAL') || die;
/**
 * Mock class for get_content.
 *
 * @package tool_mobile
 * @copyright 2018 Juan Leyva
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Returns a create course page.
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array       HTML, javascript and otherdata
     */
    public static function course_create($args) {
        global $CFG, $DB, $OUTPUT;

        $orgs = \local_eduvidual\locallib::get_organisations('Teacher');

        $subcats1 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats1');
        $subcats2 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats2');
        $subcats3 = \local_eduvidual\locallib::get_orgsubcats($orgid, 'subcats3');

        $_orgs = array();
        foreach($orgs AS $_org) { $_orgs[] = $_org; }
        $schoolyears = array('SJ 19/20', 'SJ 20/21');

        $favorgid = \local_eduvidual\locallib::get_favorgid();
        foreach ($_orgs AS &$_org) {
            $_org->isselected = ((empty($orgid) && $favorgid == $_org->orgid) || (!empty($orgid) && $orgid == $_org->orgid)) ? 1 : 0;
        }

        $html = $OUTPUT->render_from_template('local_eduvidual/teacher_createcourse', array(
            'coursebasementempty' => get_config('local_eduvidual', 'coursebasementempty'),
            'coursebasementrestore' => get_config('local_eduvidual', 'coursebasementrestore'),
            'coursebasementtemplate' => get_config('local_eduvidual', 'coursebasementtemplate'),
            'has_subcats1' => empty($subcats1) ? 0 : 1,
            'has_subcats2' => empty($subcats2) ? 0 : 1,
            'has_subcats3' => empty($subcats3) ? 0 : 1,
            'ismanager' => (\local_eduvidual\locallib::get_highest_role() == 'Manager') ? 1 : 0,
            'multipleorgs' => (count($_orgs) > 1),
            'orgs' => $_orgs,
            'orgfirst' => $_orgs[0]->orgid,
            'subcats1' => $subcats1,
            'subcats2' => $subcats2,
            'subcats3' => $subcats3,
            'subcats1lbl' => get_string('loading'),
            'subcats2lbl' => get_string('loading'),
            'subcats3lbl' => get_string('loading'),
            'subcats4lbl' => get_string('loading'),
            'subcats1org' => '',
            'subcats2org' => '',
            'subcats3org' => '',
            'subcats4org' => '',
            'wwwroot' => $CFG->wwwroot,
        ));

        $args = (object) $args;
        return array(
            'templates' => array(
                array(
                    'id' => 'local_eduvidual_createcourse',
                    'html' => $html,
                ),
            ),
            'javascript' => 'alert();',
            'otherdata' => '',
            'restrict' => array('users' => array(1, 2), 'courses' => array(3, 4)),
            'files' => array()
        );
    }
    public static function course_create_init() {
        return array(
            'javascript' => 'return true;';
        );
    }
}
