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

global $CFG, $COURSE, $DB, $PAGE, $USER;

require_once($CFG->dirroot . "/blocks/eduvidual/block_eduvidual.php");
$obj = json_decode($buffer);
$request = explode("/", $_SERVER["SCRIPT_FILENAME"]);
$requestscript = $request[count($request) - 1];
$org = block_eduvidual::$org;

switch ($requestscript) {
    case 'getnavbranch.php':
        $orgs = block_eduvidual::get_organisations('*');
        $categories = array();
        foreach($orgs AS $org) {
            $categories[] = $org->categoryid;
        }
        $obj->categories = $categories;
        if (isset($obj->children)) {
            foreach (array_keys($obj->children) AS $key) {
                if (!in_array($obj->children[$key]->key, $categories)) {
                    unset($obj->children[$key]);
                }
            }
            $obj->children = array_values($obj->children);
        }
    break;
    case 'search.php': // Assignment of Roles (at least in course category context)
        $orgids = block_eduvidual::is_connected_orglist();
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        $parameters = array();
        $courseid = 0;
        parse_str($referer, $parameters);
        if (array_key_exists('contextid', $parameters)) {
            $contextid = $parameters['contextid'];
            $context = $DB->get_record('context', array('id' => $contextid));
            if (!empty($context->level) && $context->level == CONTEXT_COURSE) {
                $courseid = $context->instance;
            }
        } elseif(array_key_exists('enrolid', $parameters)) {
            $enrolid = $parameters['enrolid'];
            $enrolment = $DB->get_record('enrol', array('id' => $enrolid));
            if (!empty($enrolment->courseid)) {
                $courseid = $enrolment->courseid;
            }
        }
        $orgids = array();
        $org = block_eduvidual::get_org_by_courseid($courseid);
        if (!empty($org->orgid)) {
            $orgids[] = $org->orgid;
        }
        // If there is no org for now use all the user is member of.
        if (count($orgids) == 0) {
            $orgids = block_eduvidual::is_connected_orglist($USER->id);
        }
        $obj->results[0]->users = block_eduvidual::is_connected_filter($obj->results[0]->users, $orgids);
    break;
    case 'service.php':
        switch(optional_param('info', '', PARAM_TEXT)) {
            case 'core_get_fragment':
                $request_body = json_decode(file_get_contents('php://input'));
                switch($request_body[0]->args->component) {
                    case 'mod_quiz':
                        // Return question bank via ajax
                        $questioncategories = explode(",", get_config('block_eduvidual', 'questioncategories'));
                        $userqcats = array();
                        $userwants = $DB->get_records('block_eduvidual_userqcats', array('userid' => $USER->id));
                        foreach($userwants AS $qcat) {
                            $userqcats[] = $qcat->categoryid;
                        }
                        // @TODO skip not wanted categories
                        $cont = $obj[0]->data->html;
                        require_once($CFG->dirroot . '/blocks/eduvidual/vendor/somesh/php-query/phpQuery/phpQuery.php');
                        $doc = phpQuery::newDocumentHTML($cont);

                        $localized_coresystem = get_string('coresystem');
                        $optgroup = pq('optgroup[label="' . $localized_coresystem . '"]');
                        $options = pq($optgroup)->find('option');

                        $skiptrigger = false;
                        foreach($options AS $option) {
                            // Here we should only have question-categories from the core system
                            $cat = explode(',', pq($option)->attr('value'));
                            $cat = $cat[0];
                            if (in_array($cat, $questioncategories) && !in_array($cat, $userqcats)) {
                                $skiptrigger = true;
                            } elseif(in_array($cat, $questioncategories)) {
                                $skiptrigger = false;
                            }
                            if ($skiptrigger) {
                                pq($option)->html('! ' . pq($option)->html())->addClass('REMOVE_ME');
                                //pq($option)->html('REMOVED ' . pq($option)->html());
                            }
                        }
                        if(block_eduvidual::get('role') != 'Administrator') {
                            pq('optgroup[label="' . $localized_coresystem . '"] .REMOVE_ME')->remove();
                        }
                        $obj[0]->data->html = $doc->htmlOuter();
                    break;
                }
            break;
            case 'core_message_data_for_messagearea_search_users':
                // This returns all orgs we are member of without protected orgs!
                $orgids = block_eduvidual::is_connected_orglist($USER->id);
                /*
                $orgids = array();
                $orgs = $DB->get_records('block_eduvidual_orgid_userid', array('userid' => $USER->id));
                $protectedorgs = explode(',', get_config('block_eduvidual', 'protectedorgs'));
                foreach($orgs AS $org) {
                    if (!in_array($org->orgid, $protectedorgs) || block_eduvidual::get('role') == 'Administrator') {
                        $orgids[] = $org->orgid;
                    }
                }
                */
                $obj[0]->data->orgids = $orgids;

                $noncontacts = $obj[0]->data->noncontacts;
                $replacedcontacts = array();
                $removedcontacts = array();
                foreach($noncontacts AS $noncontact) {
                    if (block_eduvidual::is_connected($noncontact->userid, $orgids)) {
                        $replacedcontacts[] = $noncontact;
                    } elseif (block_eduvidual::get('role') == 'Administrator') {
                        $noncontact->fullname = '! ' . $noncontact->fullname;
                        $replacedcontacts[] = $noncontact;
                    } else {
                        if ($USER->id == 6) {
                            $removedcontacts[] = $noncontact;
                        }
                    }
                }
                if ($USER->id == 6) {
                    $obj[0]->data->removedcontacts = $removedcontacts;
                }
                $obj[0]->data->noncontacts = $replacedcontacts;

                if (!empty($obj[0]->data->courses)) {
                    $courses = array();
                    foreach($obj[0]->data->courses AS $course) {
                        $courseorg = block_eduvidual::get_org_by_courseid($course->id);

                        if (in_array($courseorg->orgid, $orgids)) {
                            $courses[] = $course;
                        }
                    }
                    $obj[0]->data->courses = $courses;
                }
            break;
            case 'core_enrol_get_potential_users':
                $orgids = array();
                $request = json_decode(file_get_contents('php://input'));
                if (isset($request[0]->args) && isset($request[0]->args->courseid)) {
                    $courseid = $request[0]->args->courseid;
                    $course = $DB->get_record('course', array('id' => $courseid), 'id,category');
                    if ($course->id == $courseid) {
                        $category = $DB->get_record('course_categories', array('id' => $course->category));
                        $path = explode('/', $category->path);
                        $orgcategory = $path[1];
                        $org = $DB->get_record('block_eduvidual_org', array('categoryid' => $orgcategory));
                        if (!empty($org->orgid)) {
                            $orgids[] = $org->orgid;
                        }
                    }
                }
                // If orgid could not be determined by course we allow all orgs the user is member of.
                if (count($orgids) == 0) {
                    $orgids = block_eduvidual::is_connected_orglist($USER->id);
                }

                // Loading a list of users via ajax
                $orgpattern = implode(',', $orgids);
                $unfiltered = $obj[0]->data;
                $filtered = array();
                for ($a = 0; $a < count($unfiltered); $a++) {
                    $user = $unfiltered[$a];
                    $inorg = false;
                    if (block_eduvidual::is_connected($user->id, $orgids)) {
                        $user->fullname = $user->fullname;
                        $filtered[] = $user;
                    } elseif(block_eduvidual::get('role') == 'Administrator') {
                        $user->fullname = '! ' . $user->fullname;
                        $filtered[] = $user;
                    }
                }
                $obj[0]->data = $filtered;
            break;
        }
    break;
    case 'server.php': // Webservice functions (/webservice/rest/server.php)
        /*
        Unfortunately this does not work as we access this function with edumessenger bot and not the user himself.
        $wsfunction = optional_param('wsfunction', '', PARAM_TEXT);
        switch($wsfunction) {
            case 'core_user_get_users':
                if (isset($obj->users)) {
                    $users = array();
                    $orgids = block_eduvidual::is_connected_orglist();
                    foreach($obj->users AS $user) {
                        if (block_eduvidual::is_connected($user->id, $orgids)) {
                            $users[] = $user;
                        } elseif(block_eduvidual::get('role') == 'Administrator') {
                            $user->fullname = '! ' . $user->fullname;
                            $users[] = $user;
                        }
                    }
                    $obj->users = $users;
                }
            break;
        }
        */
    break;
}

$buffer = json_encode($obj, JSON_NUMERIC_CHECK);
