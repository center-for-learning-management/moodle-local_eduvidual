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

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

class local_eduvidual_trashbin extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('cron:trashbin:title', 'local_eduvidual');
    }

    public function execute() {
        global $CFG, $DB, $PAGE;
        $PAGE->set_context(\context_system::instance());

        // Empty trashbin
        $trashcategory = get_config('local_eduvidual', 'trashcategory');
        if ($trashcategory > 0) {
            $categories = $DB->get_records('course_categories', array('parent' => $trashcategory));
            foreach($categories AS $category) {
                echo "Removing category $category->id\n";
                try {
                    $cat = \core_course_category::instance($category->id);
                    $cat->delete_full();
                } catch(\Exception $e) {
                     echo "Exception " . $e->getMessage();
                }
            }
            require_once($CFG->dirroot . '/course/lib.php');
            $courses = $DB->get_records('course', array('category' => $trashcategory));
            foreach($courses AS $course) {
                echo "Removing course $course->id\n";
                try {
                    \delete_course($course);
                } catch(\Exception $e) {
                    echo "Exception " . $e->getMessage();
                }
            }
            \rebuild_course_cache();
        }
    }
}
