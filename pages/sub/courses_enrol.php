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

$org = \local_eduvidual\locallib::$org;
?>

<div class="card">
    <div><a href="#" class="ui-btn" onclick="history.go(-1);"><?php echo get_string('back'); ?></a></div>
</div>
<div class="grid-eq-3" id="local_eduvidual_courses" data-orgid="<?php echo $org->orgid; ?>" data-courseid="<?php echo $course->id; ?>">
    <div class="card">
        <h3><?php echo get_string('courses:enrol:courseusers', 'local_eduvidual', array('name' => $course->fullname)); ?></h3>
        <input type="text" id="local_eduvidual_courses_courseusers_search" onkeyup="local_eduvidual_TEACHER.user_search(this, 'courseusers');" />
    	<select id="local_eduvidual_courses_courseusers" style="width: 100%;" size="10" data-role="none" multiple="multiple">
    		<option value=""><?php echo get_string('courses:enrol:searchforuser', 'local_eduvidual'); ?></option>
    	</select>
    </div>
    <div class="card">
        <h3 style="text-align: center;"><?php echo get_string('courses:enrol:enrol', 'local_eduvidual'); ?></h3>
    	<select id="local_eduvidual_courses_setrole" style="width: 100%;">
            <option value="Parent"><?php echo get_string('role:Parent', 'local_eduvidual'); ?></option>
    		<option value="Student"><?php echo get_string('role:Student', 'local_eduvidual'); ?></option>
    		<option value="Teacher"><?php echo get_string('role:Teacher', 'local_eduvidual'); ?></option>
    	</select>
        <a href="#" onclick="var a = this; require(['local_eduvidual/teacher'], function(TEACHER) { TEACHER.user_set(a, 'enrol'); });" class="btn ui-btn">&lt;&lt;</a>
        <h3 style="text-align: center;"><?php echo get_string('courses:enrol:unenrol', 'local_eduvidual'); ?></h3>
        <a href="#" onclick="var a = this; require(['local_eduvidual/teacher'], function(TEACHER) { TEACHER.user_set(a, 'unenrol'); });" class="btn ui-btn">&gt;&gt;</a>
    </div>
    <div class="card">
        <h3><?php echo get_string('courses:enrol:orgusers', 'local_eduvidual', array('name' => $org->name)); ?></h3>
        <input type="text" id="local_eduvidual_courses_orgusers_search" onkeyup="var inp = this; require(['local_eduvidual/teacher'], function(TEACHER) { TEACHER.user_search(inp, 'orgusers'); });" />
    	<select id="local_eduvidual_courses_orgusers" style="width: 100%;" size="10" data-role="none" multiple="multiple">
    		<option value=""><?php echo get_string('courses:enrol:searchforuser', 'local_eduvidual'); ?></option>
    	</select>
    </div>
</div>
<?php

\local_eduvidual\locallib::add_script_on_load('require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.user_search("#local_eduvidual_courses_courseusers_search", "courseusers", 1); });');
\local_eduvidual\locallib::add_script_on_load('require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.user_search("#local_eduvidual_courses_orgusers_search", "orgusers", 1); })');
