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
 * @copyright  2017 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_eduvidual_module_compiler {
    public static function get_payload($data) {
        global $CFG;
        if ($data->type == 'lti') {
            $payload = file_get_contents($CFG->dirroot . '/blocks/eduvidual/templates/module_lti.mustache');
            $vars = array(
                'lang:title' => get_string('name'),
                'lang:description' => get_string('description'),
                'name' => $data->name,
                'description' => $data->description,
                'toolurl' => ((substr($data->ltilaunch, 0, 5) == 'http:')?$data->ltilaunch:''),
                'securetoolurl' => ((substr($data->ltilaunch, 0, 5) == 'https')?$data->ltilaunch:''),
                'iconurl' => ((substr($data->imageurl, 0, 5) == 'http:')?$data->imageurl:''),
                'secureiconurl' => ((substr($data->imageurl, 0, 5) == 'https')?$data->imageurl:''),
                'resourcekey' => $data->ltiresourcekey,
                'secret' => $data->ltisecret,
            );
            $ks = array_keys($vars);
            foreach($ks AS $k) {
                $vars[$k] = str_replace(array("\n","\r", "\""), array("\\n", "", "\\\""), $vars[$k]);
                $payload = str_replace('{{' . $k . '}}', $vars[$k], $payload);
            }
            $data->payload = $payload;
        }
        /*
        if (isset($data->payload->defaults)) {
            $ks = array_keys($data->payload->defaults);
            foreach($ks AS $k) {
                $data->payload->defaults->{$k} = base64_encode($data->payload->defaults->{$k});
            }
        }
        */

        return $data;
    }
    public static function compile($type, $data, $defaults) {
        $item = new stdClass();
        // mandatory items according to https://github.com/moodle/moodle/blob/master/course/tests/courselib_test.php line 199
        $item->modulename = $type;
        $item->section = 0;
        $item->course = 0;
        $item->groupingid = 0;
        $item->visible = true;
        $item->visibleoncoursepage = true;

        $item->name = '';
        $item->showdescription = 1;
        $item->timemodified = time();
        $item->timecreated = time();
        $item->course = "";
        $item->courseid = "";
        $item->intro = "";

        // Allows the following parameters to be overwritten
        $item->introformat = 2;
        $item->completion = 0;

        $item->memofields = array('intro');

        switch($type) {
            case "assign":
                $item->alwaysshowdescription = 0;
                $item->submissiondrafts = 0;
                $item->requiresubmissionstatement = 0;
                $item->sendnotifications = 0;
                $item->sendlatenotifications = 0;
                $item->sendstudentnotifications = 0;
                $item->duedate = 0;
                $item->cutoffdate = 0;
                $item->gradingduedate = 0;
                $item->allowsubmissionsfromdate = 0;
                $item->grade = 10;
                $item->gradecat = 0;
                $item->groupmode = 0; // 2 = VISIBLEGROUPS
                $item->completionsubmit = 1;
                $item->teamsubmission = 0;
                $item->requireallteammemberssubmit = 0;
                $item->blindmarking = 0;
                $item->attemtreopenmethod = "none";
                $item->maxattempts = -1;
                $item->preventsubmissionnotingroup = 0;
                $item->markingworkflow = 0;
                $item->markingallocation = 0;

                // Assign Submission Plugin Settings
                $item->assignsubmission_onlinetext_enabled = 0;
                $item->assignsubmission_file_enabled = 0;
                $item->assignsubmission_file_filetypes = '';
                $item->assignsubmission_file_maxfiles = 10;
                // Means site maximum
                $item->assignsubmission_file_maxsizebytes = 0;
            break;
            case "choice":
                $item->display = 1; // 0 ... horizontal, 1 ... vertical
                $item->allowupdate = 1; // change choices afterwards
                $item->allowmultiple = 1; // allow multiple selections
                $item->limitanswers = 0; // don't limit answers
                for ($a = 0; $a < 20; $a++) {
                    $item->option[$a] = $data->{'option_' . $a};
                    $item->optionid[$a] = '';
                    $item->limit[$a] = 0;
                }
                $item->completion = 2; // auto completion
                $item->completionview = 0; // auto completion on view (0 ... off, 1 ... on)
                $item->completionsubmit = 1; // auto completion on select (0... off, 1 ... on)
            break;
            case "forum":
                $item->maxattachments = 0;
                $item->assessed = 0;
                $item->assignsubmission_file_enabled = 1;
                $item->maxbytes = 0;
                $item->displaywordcount = 0;
                $item->forcesubscribe = 2;
                $item->lockdiscussionafter = 0;
                $item->blockperiod = 0;
                $item->blockafter = 0;
                $item->warnafter = 0;
            break;
            case "lti":
                $item->showdescription = 0;
                $item->showtitlelaunch = 1;
                $item->showdescriptionlaunch = 1;
                $item->typeid = 0;
                $item->launchcontainer = 4;
                $item->resourcekey = get_config('block_eduvidual', 'ltiresourcekey');
                $item->instructorcustomparameters = '';
                $item->instructorchoiceacceptgrades = 1;
                $item->instructorchoicesendname = 1;
                $item->instructorchoicesendemailaddr = 1;
                /*
                $item->grade = array(
                    'modgrade_type' => "point",
                    'modgrade_point' => 100,
                );
                */
                $item->modgrade_type = "point";
                $item->modgrade_point = 100;
                $item->completion =  1;
                $item->completionusegrade = 1;

                $item->toolurl = '';
                $item->securetoolurl = '';
                $item->icon = '';
                $item->secureicon = '';
                $item->password = '';
            break;
            case "page":
                $item->memofields[] = 'page';
                $item->page = '';
                $item->pageformat = 2;
                $item->printheading = 1;
                $item->printintro = 1;
                $item->display = 5;
                $item->completion =  2;
                $item->completionview =  1;
            break;
            case "url":
                $item->externalurl = "about:blank";
                $item->display = 3;
                $item->displayoptions = array();
                $item->parameters = array();
                $item->completion =  2;
                $item->completionview =  1;
            break;
        }

        // First we set the defaults given by payload
        $keys = array_keys((array)$item);
        foreach($keys AS $key) {
            if (isset($defaults->$key))
                $item->$key = $defaults->$key;
        }

        // Secondly we set the values given by the user
        foreach($keys AS $key) {
            if (isset($data->$key))
                $item->$key = $data->$key;
        }

        for ($a = 0; $a < count($item->memofields); $a++) {
            $memofield = $item->memofields[$a];
            $memofieldformat = $memofield . 'format';
            $memofieldeditor = $memofield . 'editor';
            $format = isset($item->{$memofieldformat})?$item->{$memofieldformat}:2;
            $item->$memofieldeditor = array('text' => $item->$memofield, 'format' => $format, 'itemid' => $a);
        }
        $item->cmidnumber = 0;

        return $item;
    }
    public static function create($item){
        global $CFG, $USER;

        $context = context_course::instance($item->course);
        $roletoassign = 1; // Manager
        $revokerole = true;
        $roles = get_user_roles($context, $USER->id, false);
        foreach($roles AS $role) {
            if ($role->roleid == $roletoassign) {
                // User had this role before - we do not revoke!
                $revokerole = false;
            }
        }
        role_assign($roletoassign, $USER->id, $context->id);
        require_once($CFG->dirroot . '/course/lib.php');
        $mod = create_module($item);
        if ($revokerole) {
            role_unassign($roletoassign, $USER->id, $context->id);
        }
        return $mod;
    }
}
