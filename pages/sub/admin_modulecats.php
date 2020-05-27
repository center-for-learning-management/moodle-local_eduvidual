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

defined('MOODLE_INTERNAL') || die;
if (!is_siteadmin()) die;

// IF THE CATEGORYFORM WAS SENT STORE RESULT
require_once($CFG->dirroot . "/blocks/eduvidual/classes/admin_modulecat_form.php");

$categoryform = new block_eduvidual_admin_modulecat_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
$context = context_system::instance();
if ($data = $categoryform->get_data()) {
    $categoryid = optional_param('id', 0, PARAM_INT);
    if ($categoryid == -1) {
        // Create record to retrieve a categoryid BEFORE we store the image
        $categoryid = $DB->insert_record('block_eduvidual_modulescat', $data, true);
        $data->categoryid = $categoryid;
    }
    file_save_draft_area_files(
        $data->modulecat, $context->id, 'block_eduvidual', 'modulecat', $categoryid,
        array(
            'subdirs' => $categoryform->subdirs, 'maxbytes' => $categoryform->maxbytes,
            'maxfiles' => $categoryform->maxfiles
        )
    );
    $files = block_eduvidual::list_area_files('modulecat', $categoryid, $context);

    if (count($files) > 0) {
        $data->imageurl = $files[0]->url;
    } else {
        $data->imageurl = '';
    }

    // Now update with the imageurl included
    $DB->update_record('block_eduvidual_modulescat', $data);
    echo "<p class=\"alert alert-success\">" . get_string('store:success', 'block_eduvidual') . "</p>";
}

$categoryid = optional_param('categoryid', -1, PARAM_INT);
$parentid = optional_param('parentid', -1, PARAM_INT);
$moduleid = optional_param('moduleid', 0, PARAM_INT);

if ($parentid > -1) {
    // Ensure that we create a new category
    $categoryid = -1;
}

?>

<div class="block_eduvidual_admin_modulescat-wrapper">
    <div class="__block_eduvidual_admin_modulescat_cat">
        <h4><?php echo get_string('admin:modulecats:title', 'block_eduvidual'); ?></h4>
        <ul>
            <li>
                Root
                <a href="<?php echo $PAGE->url; ?>?act=modulecats&parentid=0" class="btn-check ui-mini">
                    <img src="/pix/t/add.svg" alt="<?php echo get_string('add'); ?>" />
                </a>
                <?php block_eduvidual_admin_modulecats_printtree(0, $categoryid); ?>
            </li>
        </ul>
        <a href="<?php echo $PAGE->url . '?act=' . $act . '&import=1'; ?>"><?php echo get_string('import'); ?></a>
    </div>
    <div class="__block_eduvidual_admin_modulescat_module">
<?php

if ($categoryid > -1) {
    include($CFG->dirroot . '/blocks/eduvidual/pages/sub/admin_module.php');
}
?>
    </div>
    <div id="block_eduvidual_admin_modulecat_form" data-categoryid="<?php echo $categoryid; ?>" data-parentid="<?php echo $parentid; ?>">
<?php
if ($categoryid > -1 || $parentid > -1) {
    ?>
    <?php
    include($CFG->dirroot . '/blocks/eduvidual/pages/sub/admin_modulecat.php');
}
?>
    </div>
    <div>
<?php

if (optional_param('import', 0, PARAM_INT) == 1) {
    require_once($CFG->dirroot . '/blocks/eduvidual/pages/sub/admin_modulesimport.php');
} elseif($moduleid > 0 || $moduleid == -1) {
    require_once($CFG->dirroot . "/blocks/eduvidual/classes/admin_module_form.php");

    $moduleform = new block_eduvidual_admin_module_form(null, null, 'post', '_self', array('data-ajax' => 'false'), true);
    $context = context_system::instance();
    if ($data = $moduleform->get_data()) {
        if ($data->moduleid == -1) {
            $moduleid = $DB->insert_record('block_eduvidual_modules', $data, true);
            $data->moduleid = $moduleid;
            $data->id = $data->moduleid;
        }
        file_save_draft_area_files(
            $data->module, $context->id, 'block_eduvidual', 'module', $data->moduleid,
            array('subdirs' => $moduleform->subdirs, 'maxbytes' => $moduleform->maxbytes, 'maxfiles' => $moduleform->maxfiles)
        );
        $files = block_eduvidual::list_area_files('module', $data->moduleid, $context);
        if (count($files) > 0) {
            $data->imageurl = $files[0]->url;
        } else {
            $data->imageurl = '';
        }
        require_once($CFG->dirroot . '/blocks/eduvidual/classes/module_compiler.php');
        $data = block_eduvidual_module_compiler::get_payload($data);
        $DB->update_record('block_eduvidual_modules', $data);
        echo "<p class=\"alert alert-success\">" . get_string('store:success', 'block_eduvidual') . "</p>";
    }

    $context = context_system::instance();
    $draftitemid = file_get_submitted_draft_itemid('module');
    file_prepare_draft_area($draftitemid, $context->id, 'block_eduvidual', 'module', $moduleid,
        array('subdirs' => $moduleform->subdirs, 'maxbytes' => $moduleform->maxbytes, 'maxfiles' => $moduleform->maxfiles));
    $entry = $DB->get_record('block_eduvidual_modules', array('id' => $moduleid));
    $entry->act = $act;
    $entry->categoryid = $categoryid;
    $entry->modulecat = $draftitemid;
    $entry->id = $moduleid;
    $entry->moduleid = $moduleid;
    $entry->module = $draftitemid;
    if (empty($entry->ltiresourcekey)) {
        $entry->ltiresourcekey = get_config('block_eduvidual', 'ltiresourcekey');
    }
    //$entry->payload = base64_decode($entry->payload);
    $moduleform->set_data($entry);
    $moduleform->display();
}

?>
    </div>
</div>
<?php

// BELOW THIS LINE WE ONLY COLLECT FUNCTIONS

/**
 * Recursively print module Categories
 * @param categoryid categoryid to print (changes with recursions)
 * @param _categoryid categoryid that was received with optional_param (stays the same)
**/
function block_eduvidual_admin_modulecats_printtree($categoryid, $_categoryid) {
    global $DB, $PAGE;
    $cats = $DB->get_records_sql('SELECT id,name,active FROM {block_eduvidual_modulescat} WHERE parentid=? ORDER BY name ASC', array($categoryid));
    if (count($cats) > 0) echo "<ul>";
    foreach($cats AS $cat) {
        echo "\t<li data-categoryid=\"" . $cat->id . "\" class=\"" . (($cat->active)?'active':'inactive') . (($cat->id == $_categoryid)?' current':'') . "\">\n";
        echo "\t\t<a href=\"" . $PAGE->url . "?act=modulecats&categoryid=" . $cat->id . "\">" . $cat->name . "</a>\n";
        echo "<a href=\"" . $PAGE->url . "?act=modulecats&parentid=" . $cat->id . "\" class=\"btn-check ui-mini\"><img src=\"/pix/t/add.svg\" alt=\"" . get_string('add') . "\" /></a>\n";
        block_eduvidual_admin_modulecats_printtree($cat->id, $_categoryid);
        echo "\t</li>\n";
    }
    if (count($cats) > 0) echo "</ul>";
}
