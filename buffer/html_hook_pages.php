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

global $CFG, $DB, $PAGE, $USER;

$request = explode("/", $_SERVER["SCRIPT_FILENAME"]);
$requestscript = $request[count($request) - 1];

/**
 * If we edit a CATEGORY do not show all form elements
**/
if (block_eduvidual::get('role') !== 'Administrator' && strpos($_SERVER["SCRIPT_FILENAME"], '/course/editcategory.php') > 0) {
    $categoryid = optional_param('id', 0, PARAM_INT);
    $parentid = optional_param('parent', 0, PARAM_INT);
    $isorg = $DB->get_record('block_eduvidual_org', array('categoryid' => $categoryid));

    // Catch values
    $idnumber = pq('#page-content form #id_idnumber')->val();
    pq('#page-content form')->append(pq('<input id="id_idnumber" name="idnumber" type="hidden" value="' . $idnumber . '" />'));

    if ($parentid > 0 ||  !isset($isorg->orgid) || $isorg->categoryid != $categoryid) {
        pq('#page-content form>div.fitem:eq(2)')->remove();
    } else {
        $name = pq('#page-content form #id_name')->val();
        $parent = pq('#page-content form #id_parent')->val();
        pq('#page-content form')->append(pq('<input id="id_name" name="name" type="hidden" value="' . $name . '" />'));
        pq('#page-content form')->append(pq('<input id="id_parent" name="parent" type="hidden" value="' . $parent . '" />'));
        pq('#page-content form>div.fitem:eq(2)')->remove();
        pq('#page-content form>div.fitem:eq(1)')->remove();
        pq('#page-content form>div.fitem:eq(0)')->remove();
    }

    // Remove the first three divs
    //pq('#page-content form>div.fitem:eq(0)')->remove();
    // Create hidden elements instead
}

// If we enter a course we are not enrolled in
if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/index.php') > 0) {
    $courseid = optional_param('id', 0, PARAM_INT);
    $org = block_eduvidual::set_org_by_courseid($courseid);
    if (!empty($org->orgid) && (block_eduvidual::get('orgrole') == 'Manager' || block_eduvidual::get('role') == 'Administrator')) {
        $box = pq('div[role="main"]');
        $btn = pq('<a>')->html(get_string('manage:enrolmeasteacher', 'block_eduvidual'))
                        ->addClass('btn ui-btn btn-primary')
                        ->attr('href', '#')->attr('onclick', 'require(["block_eduvidual/manager"], function(MANAGER) { MANAGER.forceEnrol(' . $courseid . '); });');
        $box->prepend(pq('<p>')->html('&nbsp;'));
        $box->prepend($btn);
        $box->prepend(pq('<h3>')->html('eduvidual-' . get_string('Manager', 'block_eduvidual')));
    }
}
// If we manage manual enrolments not via ajax.
if (strpos($_SERVER["SCRIPT_FILENAME"], '/enrol/manual/manage.php') > 0) {
    $courseid = 0;
    $enrolid = optional_param('enrolid', 0, PARAM_INT);
    $enrolment = $DB->get_record('enrol', array('id' => $enrolid));
    if (!empty($enrolment->courseid)) {
        $courseid = $enrolment->courseid;
    }
    $orgids = array();
    $org = block_eduvidual::get_org_by_courseid($courseid);
    if (!empty($org->orgid)) {
        $orgids[] = $org->orgid;
    }
    // If there is no org for now use all the user is member of.
    if (count($orgids) == 0) {
        $orgids = \block_eduvidual\locallib::is_connected_orglist($USER->id);
    }
    $options = pq('#addselect option');
    foreach ($options AS $option) {
        $userid = pq($option)->attr('value');
        if (!\block_eduvidual\locallib::is_connected($userid, $orgids)) {
            if (!block_eduvidual::get('role') == 'Administrator') {
                pq($option)->addClass('REMOVE_ME');
            } else {
                pq($option)->html('! ' . pq($option)->html());
            }
        }
    }
    pq('#addselect option.REMOVE_ME')->remove();
}

/**
 * Check on view of PROFILE for REMOTE USERS
**/
if (strpos($_SERVER["SCRIPT_FILENAME"], '/user/profile.php') > 0) {
    if (pq('div.profile_tree li.remoteuserinfo')->length() > 0) {
        $userid = optional_param('id', $USER->id, PARAM_INT);
        $entry = $DB->get_record('user_preferences', array('userid' => $userid, 'name' => 'htmleditor'));
        $select = pq('<select>')->attr('onchange', "var sel = this; require(['block_eduvidual/user'], function(USER) { USER.setEditor(sel); });");
        $editors = array('', 'atto', 'tinymce', 'textarea');

        foreach($editors AS $editor) {
            $option = pq('<option>')->attr('value', $editor)->html(get_string('user:preference:editor:' . $editor, 'block_eduvidual'));
            if (isset($entry->value) && $entry->value == $editor) {
                $option->attr('selected', 'selected');
            }
            $select->append($option);
        }
        $ul = pq('div.profile_tree section.node_category>ul');
        $li = pq('<li class="contentnode">'); $ul->append($li);
        $dl = pq('<dl>'); $li->append($dl);
        $dt = pq('<dt>')->html(get_string('user:preference:editor:title', 'block_eduvidual')); $dl->append($dt);
        $dd = pq('<dd>'); $dl->append($dd);
        $dd->append($select);
    }
}

// On boost theme we need an extra button to export student created questions
if ($PAGE->theme->name == 'boost' && strpos($_SERVER["SCRIPT_FILENAME"], '/mod/qcreate/edit.php') > 0) {
    $cmid = optional_param('cmid', 0, PARAM_INT);

    if ($cmid > 0) {
        $box = pq('input[name="fastg"]')->addClass('btn btn-secondary')->parent();
        $btn = pq('<a>')->html(get_string('exportgood', 'qcreate'))
                        ->addClass('btn ui-btn btn-primary')
                        ->attr('href', $CFG->wwwroot . '/mod/qcreate/exportgood.php?cmid=' . $cmid);
        $box->append($btn);
    }
}

// Report Completion - set links to h5p.
if (strpos($_SERVER["SCRIPT_FILENAME"], '/report/completion/index.php') > 0) {
    $courseid = optional_param('course', 0, PARAM_INT);

    $criteriaicons = pq('table#completion-progress>thead>tr:last-child')->children('.criteriaicon');
    $mods = array();
    foreach ($criteriaicons AS $key => $criteria) {
        $modurl = pq($criteria)->children('a')->attr('href');
        //echo $modurl . ' (' . strpos($modurl, '/mod/hvp/view.php') . ')<br />';
        if (strpos($modurl, '/mod/hvp/view.php') > 0) {
            $tmp = explode('view.php?id=', $modurl);
            // Ensure we have an id.
            if (count($tmp) == 2) {
                $modid = $tmp[1];
                $mod = $DB->get_record('course_modules', array('id' => $modid));
                if (!empty($mod->id)) {
                    $reporturl = $CFG->wwwroot . '/mod/hvp/review.php?id=' . $mod->instance . '&course=' . $courseid;
                    $mods[$key] = $reporturl;
                } else {
                    $mods[$key] = "";
                }
            } else {
                $mods[$key] = "";
            }
        } else {
            $mods[$key] = "";
        }
    }

    $ths = pq('table#completion-progress>tbody')->children('tr');

    foreach ($ths AS $th)  {
        $tmp = explode('-', pq($th)->attr('id'));
        if (count($tmp) == 2) {
            $userid = $tmp[1];

            $tds = pq($th)->children('.completion-progresscell');
            foreach ($tds AS $key => $td) {
                if (!empty($mods[$key])) {
                    pq($td)->html('<a href="' . $mods[$key] . '&user=' . $userid . '">' . pq($td)->html() . '</a>');
                }
            }
        }
    }
}
