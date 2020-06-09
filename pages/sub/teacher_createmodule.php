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

local_eduvidual::print_app_header();


if ($courseid == 0) {
    $courses = enrol_get_all_users_courses($USER->id, true);
    ?>
    <h3><?php echo get_string('teacher:createmodule:selectcourse', 'local_eduvidual'); ?></h3>
    <div class="grid-eq-2" style="text-align: center;">
        <div>
            <a href="#" onclick="history.go(-1);" class="ui-btn">
                <img src="/pix/t/left.svg" alt="">
                <?php echo get_string('back'); ?></a>
        </div>
    </div>
    <ul id="local_eduvidual_teacher_createmodule_course" data-role="listview" data-inset="true">
    <?php
    foreach($courses AS $course) {
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:update', $context)) {
            ?>
            <li>
                <a href="<?php echo $PAGE->url . '?act=createmodule&courseid=' . $course->id; ?>"><?php
                echo $course->fullname;
                ?></a>
            </li>
            <?php
        }
    }
    ?>
    </ul>
    <?php
} elseif ($sectionid == -1) {
    require_once($CFG->dirroot . '/course/format/lib.php');
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_pagelayout('incourse');
    $course->format = course_get_format($course)->get_format();
    $PAGE->set_pagetype('course-view-' . $course->format);

    context_helper::preload_course($course->id);
    $context = context_course::instance($course->id, MUST_EXIST);
    $PAGE->set_context($context);

    if (!has_capability('moodle/course:update', $context)){
        ?>
        <p class="alert alert-error"><?php echo get_string('teacher:createmodule:missing_capability', 'local_eduvidual'); ?></p>
        <?php
    }
    ?>
    <h3><?php echo get_string('teacher:createmodule:selectsection', 'local_eduvidual'); ?></h3>
    <div class="grid-eq-2" style="text-align: center;">
        <div>
            <a href="#" onclick="history.go(-1);" class="ui-btn btn">
                <img src="/pix/t/left.svg" alt="">
                <?php echo get_string('back'); ?></a>
        </div>
    </div>
    <ul id="local_eduvidual_teacher_createmodule_section" data-role="listview" data-inset="true">
    <?php
    //$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    require_once($CFG->dirroot . '/course/format/lib.php');
    $format = course_get_format($courseid);
    $sections = $format->get_sections();
    for($a = 0; $a < count($sections); $a++) {
        $section = $sections[$a];
        ?>
        <li>
            <a href="<?php echo $PAGE->url . '?act=createmodule&courseid=' . $courseid . '&sectionid=' . $a; ?>"><?php
            echo $format->get_section_name($section);
            ?></a>
        </li>
        <?php
    }
    ?>
    </ul>
    <?php
} else {
    $publisherexists = file_exists($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
    if ($publisherexists) {
        require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
        $publishercanuse = block_edupublisher::check_requirements(false);
    }
    echo $OUTPUT->render_from_template(
        'local_eduvidual/teacher_createmodule_modules',
        (object) array(
            'courseid' => $courseid,
            'orgid' => $org->orgid,
            'sectionid' => $sectionid,
            'publisherexists' => $publisherexists,
            'publishercanuse' => $publishercanuse,
        )
    );
    //local_eduvidual::add_script_on_load('require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadCategory(0); });');
    //if ($showpublisher) {
        //local_eduvidual::add_script_on_load('require(["local_eduvidual/teacher"], function(TEACHER) { TEACHER.loadPublisher(0); });');
    //}
}
