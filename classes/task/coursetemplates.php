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
 * @copyright  2021 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates a backup when a template course has been modified.
*/

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

class coursetemplates extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('task:coursetemplates:title', 'local_eduvidual');
    }

    public function execute() {
        global $CFG, $DB;

        $scheduled = explode(',', get_config('local_eduvidual', 'coursebasement-scheduled'));
        if (count($scheduled) == 0) return;

        $admin = \get_admin();
        if (!$admin) {
            \mtrace("Error: No admin account was found");
            die;
        }

        $dir = \local_eduvidual\locallib::get_tempdir();
        if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
            \mtrace("Destination directory does not exists or not writable.");
            die;
        }

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');

        foreach ($scheduled as $courseid) {
            if (empty($courseid)) continue;
            $course = \get_course($courseid);
            if (empty($course->id)) {
                echo "ERROR: COURSE #$courseid DOES NOT EXIST<br />";
                continue;
            }
            $targetfilename = "coursebackup.mbz";
            echo "Backing up #$course->id ($course->fullname) to $targetfilename<br />\n";

            $bc = new \backup_controller(
                        \backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                        \backup::INTERACTIVE_YES, \backup::MODE_GENERAL, $admin->id
                    );
            $format = $bc->get_format();
            $type = $bc->get_type();
            $id = $bc->get_id();

            $bc->get_plan()->get_setting('users')->set_value(0);
            $users = $bc->get_plan()->get_setting('users')->get_value();
            $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
            //$filename = \backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
            //echo "Filename $filename";die();
            //$bc->get_plan()->get_setting('filename')->set_value($filename);
            $bc->get_plan()->get_setting('filename')->set_value($targetfilename);

            $bc->finish_ui();
            $bc->execute_plan();
            $results = $bc->get_results();
            $file = $results['backup_destination'];

            $ctx = \context_course::instance($course->id);
            $fs = \get_file_storage();
            $fr = array('contextid'=>$ctx->id, 'component'=>'local_eduvidual', 'filearea'=>'coursebackup',
                'itemid'=>0, 'filepath'=>'/', 'filename'=> $targetfilename,
                'timecreated'=>time(), 'timemodified'=>time()
            );

            $testfile = $fs->get_file($fr['contextid'], $fr['component'], $fr['filearea'], $fr['itemid'], $fr['filepath'], $fr['filename']);
            if ($testfile) {
                $testfile->delete();
            }
            $fs->create_file_from_storedfile($fr, $file);

            $file->delete();
            echo "Stored file successfully<br />\n";
        }
        set_config('coursebasement-scheduled', '', 'local_eduvidual');
    }
}
