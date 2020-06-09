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
 * @copyright  2020 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class lib_wshelper {
    /**
     * Recognizes the result of a certain script and registers an output buffer for it.
     */
    public static function buffer() {
        global $CFG;
        $func = str_replace('__', '_', 'buffer_' . str_replace('/', '_', str_replace('.php', '', str_replace($CFG->dirroot, '', $_SERVER["SCRIPT_FILENAME"]))));
        if (method_exists(__CLASS__, $func)) {
            error_log('Buffer function ' . $func . ' called');
            ob_start();
            register_shutdown_function('\local_eduvidual\lib_wshelper::buffer_modify');
        } else {
            error_log('Buffer function ' . $func . ' not found');
            return false;
        }
    }
    /**
     * Determines the appropriate handler-method for this output buffer.
     */
    public static function buffer_modify() {
        global $CFG;
        $buffer = ob_get_clean();
        $func = str_replace('__', '_', 'buffer_' . str_replace('/', '_', str_replace('.php', '', str_replace($CFG->dirroot, '', $_SERVER["SCRIPT_FILENAME"]))));
        call_user_func('self::' . $func, $buffer);
    }
    /**
     * Modifies the output of particular webservice calls.
     * @param classname Classname of the ws.
     * @param methodname The wsfunction-name.
     * @param params The params for this wsfunction.
    **/
    public static function override($classname, $methodname, $params) {
        $func = 'override_' . $classname . '_' . $methodname;
        if (method_exists(__CLASS__, $func)) {
            error_log('Overide function ' . $func . ' called');
            $result = call_user_func_array(array($classname, $methodname), $params);
            return call_user_func('self::' . $func, $result);
        } else {
            error_log('Overide function ' . $func . ' not found');
            return false;
        }
    }

    /**
     * These are the buffer-functions, that should OUTPUT something using echo.
     */
    private static function buffer_user_selector_search($buffer) {
        $result = json_decode($buffer);
        if (!empty($result->results)) {
            if (!empty($result->results[0]->users)) {
                $result->results[0]->users = \local_eduvidual\locallib::filter_userlist($result->results[0]->users, 'id', 'name');
            }
        }
        echo json_encode($result, JSON_NUMERIC_CHECK);
    }
    private static function buffer_web_lib_ajax_getnavbranch($buffer) {
        $result = json_decode($buffer);
        $orgs = local_eduvidual::get_organisations('*');
        $categories = array();
        foreach($orgs AS $org) {
            $categories[] = $org->categoryid;
        }
        $result->categories = $categories;
        if (isset($result->children)) {
            foreach (array_keys($result->children) AS $key) {
                if (!in_array($result->children[$key]->key, $categories)) {
                    unset($result->children[$key]);
                }
            }
            $result->children = array_values($result->children);
        }
        echo json_encode($result, JSON_NUMERIC_CHECK);
    }

    /**
     * These are the override-functions, that should RETURN something like the result of ws requests.
     */
    private static function override_block_exacomp_diggr_get_students_of_cohort($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_cohort_add_cohort_members($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_cohort_search_cohorts($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_course_external_get_enrolled_courses_by_timeline_classification($result) {
        if (!empty($result['courses'])) {
            foreach ($result['courses'] AS $id => &$course) {
                if ($id == 0) {
                    // We attempted to inject some code that modifies the layout and functionality of the course cards.
                    // Integration of the course news turned out to be impossible since Moodle 3.7 (refer to https://github.com/moodleuulm/moodle-block_course_overview_campus/issues/35)
                    // But we may keep this for other implementations, like the "upload course image popup" or similar.
                    //$course->fullname .= "<script> require(['local_eduvidual/jsinjector'], function(jsi) { jsi.dashboardCourseLoaded(); } ); </script>";
                }
                $course->showshortname = false;
                // We do not want to show the progress bar.
                $course->hasprogress = false;
            }
        }
        return $result;
    }
    private static function override_core_enrol_external_get_potential_users($result) {
        return \local_eduvidual\locallib::filter_userlist($result, 'id', 'fullname');
    }
    private static function override_core_get_fragment($result) {
        if (!empty($result[0]->args->component) && $result[0]->args->component == 'mod_quiz') {
            error_log('WE WILL NEED TO PROGRAM THIS IN ANOTHER WAY - override_core_get_fragment for mod_quiz');
        }
    }

    /* THIS WSFUNCTION IS MARKED AS OBSOLETE. WE KEEP IT IF OLDER PLUGINS STILL USE IT */
    private static function override_core_message_data_for_messagearea_search_users($result) {
        if (!empty($result->data[0]->noncontacts)) {
            $result->data[0]->noncontacts = \local_eduvidual\locallib::filter_userlist($result->data[0]->noncontacts, 'userid', 'fullname');
        }
        return $result;
    }

    private static function override_core_message_message_search_users($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_message_search_contacts($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_search_get_relevant_users($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_core_user_get_users($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_tool_lp_search_cohorts($result) {
        error_log(print_r($result, 1));
        return $result;
    }
    private static function override_tool_lp_search_users($result) {
        error_log(print_r($result, 1));
        return $result;
    }
}
