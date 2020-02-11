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
 * @package    block_eduvidual
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG, $COURSE, $OUTPUT;
if (empty($COURSE->id)) return;

$section = 0;
$optional_section = optional_param('section', -1, PARAM_INT);
$publisherexists = file_exists($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
if ($publisherexists) {
    require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
    $publishercanuse = block_edupublisher::check_requirements(false);
}

$module_allow = array("Administrator", "Manager", "Teacher");

$addresource_o = (object) array(
    'courseid' => $COURSE->id,
    'orgid' => $org->orgid,
    'modulecanuse' => in_array(block_eduvidual::get('role'), $module_allow),
    'publishercanuse' => $publishercanuse,
    'publisherexists' => $publisherexists,
    'section' => 0,
    'wwwroot' => $CFG->wwwroot,
);

// If the optional_section is given we may see section 0 and optional_section, or optional_section only!
if (count(pq('.section-modchooser')) == 1 && $optional_section > -1) {
    // We only see optional_section
    $addresource_o->section = $optional_section;
    pq('.section-modchooser:eq(0)')->append($OUTPUT->render_from_template('block_eduvidual/module_create',$addresource_o));
} elseif (count(pq('.section-modchooser')) == 2 && $optional_section > -1) {
    // We see section 0 and optional_section
    pq('.section-modchooser:eq(0)')->append($OUTPUT->render_from_template('block_eduvidual/module_create',$addresource_o));
    $addresource_o->section = $optional_section;
    pq('.section-modchooser:eq(1)')->append($OUTPUT->render_from_template('block_eduvidual/module_create',$addresource_o));
} else {
    // We see all sections
    foreach(pq('.section-modchooser') AS $chooser) {
        pq($chooser)->append($OUTPUT->render_from_template('block_eduvidual/module_create',$addresource_o));
        $addresource_o->section++;
    }
}
