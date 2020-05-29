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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

$id = optional_param('id', 0, PARAM_INT);
block_eduvidual::set_org_by_courseid($id);
block_eduvidual::set_context_auto($id);

$PAGE->set_pagelayout('mydashboard');
$PAGE->set_url('/blocks/eduvidual/pages/courses.php', array());
$PAGE->set_title(get_string('Courses', 'block_eduvidual'));
$PAGE->set_heading(get_string('Courses', 'block_eduvidual'));
//$PAGE->set_cacheable(false);

if ($id == 0) {
    block_eduvidual::print_app_header();
    // Show overview of courses
    $courses = enrol_get_all_users_courses($USER->id, true);
    // Create a course_in_list object to use the get_course_overviewfiles() method.
    require_once($CFG->libdir . '/coursecatlib.php');
    ?>
    <ul id="block_eduvidual_user_courselist" data-role="listview" data-inset="true" data-split-icon="eye">
        <?php
        foreach($courses AS $course) {
            $shown = $DB->get_record('block_eduvidual_courseshow', array('userid' => $USER->id, 'courseid' => $course->id));
            $url = $CFG->wwwroot . "/course/view.php?id=" . $course->id;
            $context = context_course::instance($course->id);
            $canviewinvisible = has_capability('moodle/course:update', $context) || is_siteadmin() || block_eduvidual::get('orgrole') == 'Manager';
            if (true || block_eduvidual::$isapp) {
                $url = $CFG->wwwroot . "/blocks/eduvidual/pages/courses.php?id=" . $course->id;
            }
            // List course only if visible or we can edit
            if ($course->visible == 1 || $canviewinvisible) {
                $_course = new course_in_list($course);

                $course->image = '/pix/i/course.svg';
                foreach ($_course->get_course_overviewfiles() as $file) {
                    if ($file->is_valid_image()) {
                        $imagepath = '/' . $file->get_contextid() .
                                '/' . $file->get_component() .
                                '/' . $file->get_filearea() .
                                $file->get_filepath() .
                                $file->get_filename();
                                $course->image = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath, false);
                        // Use the first image found.
                        break;
                    }
                }
                ?>
                <li<?php if ($course->visible == 0 || (isset($shown->courseid) && $shown->courseid == $course->id)) { echo ' class="inactive ishidden"'; } ?> data-courseid="<?php echo $course->id; ?>">
                    <a href="<?php echo $url; ?>">
                        <img src="<?php echo $course->image; ?>" alt="course image" />
                        <h3><?php echo $course->fullname; ?></h3>
                        <p><?php echo strip_tags($course->summary); ?></p>
                    </a>
                    <a href="#" onclick="var a = this; require(['block_eduvidual/user'], function(USER) { USER.setHidden(a); });"><img src="/pix/i/hide.svg" alt="trigger" /></a>
                </li>
                <?php
            }
        }
        ?>
    </ul>
    <fieldset>
        <label for="block_eduvidual_user_courselist_trigger">
            <?php echo get_string('user:courselist:showhidden', 'block_eduvidual'); ?>
        </label>
        <select id="block_eduvidual_user_courselist_trigger" data-role="slider" onchange="var sel = this; require(['block_eduvidual/user'], function(USER) { USER.triggerShowHidden(+sel.value); });">
            <option value="0"><?php echo get_string('hide'); ?></option>
            <option value="1"><?php echo get_string('show'); ?></option>
        </select>
    </fieldset>
    <?php
} else {
    // Show course contents
    //$PAGE->set_pagelayout('course');
    $course = $DB->get_record('course', array('id' => $id));

    $context = context_course::instance($course->id);
    $isenrolled = is_enrolled($context, $USER->id, '', true);
    if (!$isenrolled && file_exists($CFG->dirroot . '/enrol/autoenrol/lib.php')) {
        // Check for possible auto enrolments.
        $enrolinstances = enrol_get_instances($course->id, false);
        foreach($enrolinstances AS $enrolinstance) {
            if ($enrolinstance->enrol == 'autoenrol' && $enrolinstance->status == 0) {
                require_once($CFG->dirroot . '/enrol/autoenrol/lib.php');
                $aep = new enrol_autoenrol_plugin();
                $aep->try_autoenrol($enrolinstance);
            }
        }
    }

    require_login($course);
    $modinfo = get_fast_modinfo($course);
    $cms = $modinfo->get_cms();
    $PAGE->set_context($context);
    block_eduvidual::print_app_header();

    $isenrolled = is_enrolled($context, $USER->id, '', true);
    $canedit = has_capability('moodle/course:update', $context) || is_siteadmin();

    if ($canedit && optional_param('act', '', PARAM_TEXT) == 'enrol') {
        require_once($CFG->dirroot . '/blocks/eduvidual/pages/sub/courses_enrol.php');
    } else {
        ?>
        <div class="grid-eq-2">
            <a href="#" class="btn ui-btn" onclick="history.go(-1);"><?php echo get_string('back'); ?></a>
            <?php
            // @TODO if can edit show buttons to 'remove course', 'hide/show course', 'enrol_users to course'
            if ($canedit) {
                ?>
                <select onchange="var sel = this; require(['block_eduvidual/user'], function(USER) { USER.courseAction(<?php echo $course->id; ?>,sel); });">
                    <option value=""><?php echo get_string('action'); ?></option>
                    <option value="enrol"><?php echo get_string('teacher:course:enrol', 'block_eduvidual'); ?></option>
                    <option value="gradings"><?php echo get_string('teacher:course:gradings', 'block_eduvidual'); ?></option>
                    <option value="hideshow"><?php echo get_string('course') . ' ' . get_string(($course->visible==1)?'hide':'show'); ?></option>
                    <option value="remove"><?php echo get_string('course') . ' ' . get_string('remove'); ?></option>
                </select>
                <?php
            }
            ?>
        </div>
        <?php
        if ($isenrolled || is_siteadmin()) {
            ?>
            <ul data-role="listview" data-inset="true" class="ui-listview ui-listview-inset ui-corner-all ui-shadow fix-images" data-icon="false" data-split-icon="action">
            <?php
            $sections = $DB->get_records('course_sections', array('course' => $course->id));
            $sectionno = 0;
            foreach($sections AS $section) {
                ?>
                <li data-role="list-divider" style="display: block !important;"><?php
                    if ($canedit) {
                        ?><a href="<?php echo $CFG->wwwroot . '/blocks/eduvidual/pages/teacher.php?act=createmodule&courseid=' . $course->id . '&sectionid=' . $sectionno++; ?>">
                            <img src="/pix/t/add.svg" alt="<?php echo get_string('add'); ?>"/>
                        </a>
                            <?php
                    }
                    echo '<h3>' . (($section->name != "")?$section->name:get_string('section') . ' ' . $sectionno++) . '</h3>';
                ?></li>
                <?php
                //$sequence = $DB->get_record('course_sections', array('id' => $section->id), 'sequence');
                $sequence = explode(',', $section->sequence);

                foreach($sequence AS $cmid) {
                    if ($cmid == 0) continue;
                    if (!isset($cms[$cmid])) continue;
                    $cm = $cms[$cmid];
                    if ($cm->visible == 1 || $canedit) {
                        ?>
                        <li class="<?php echo ($cm->visible == 0)?' block_eduvidual_inactive':''; ?>">
                            <a href="#" onclick="var a = this; require(['block_eduvidual/user'], function(USER) { USER.showModuleInfo(a); });" data-ajax="false" data-url="<?php echo $cm->url; ?>">
                                <img src="<?php echo $cm->get_icon_url(); ?>" alt="<?php echo $cm->modname; ?>" />
                                <h3><?php echo $cm->name; ?></h3>
                                <p><?php echo strip_tags($cm->content); ?></p>
                            </a>
                            <?php
                            if (!empty($cm->url)) {
                                ?>
                                <a href="<?php echo $cm->url; ?>" class="openurl">
                                    <img src="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pix/action-black.svg" alt="open" />
                                </a>
                                <?php
                            }
                            ?>
                        </li>
                        <?php
                    }
                }
            }
            ?>
            </ul>
        <?php
        } else { ?>
            <p class="alert alert-warning"><?php echo get_string('courses:noaccess', 'block_eduvidual'); ?></p>
            <?php
        } // endif isenrolled
    } // endif enrol else
}
block_eduvidual::print_app_footer();
