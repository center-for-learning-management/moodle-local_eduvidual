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
if (!is_siteadmin()) {
    ?>
    <p class="alert alert-warning"><?php echo get_string('js:missing_permission', 'local_eduvidual'); ?></p>
    <?php
}

require_once($CFG->dirroot . '/local/eduvidual/classes/lib_import.php');
$helper = new local_eduvidual_lib_import();
$compiler = new local_eduvidual_lib_import_compiler_module();
$helper->set_compiler($compiler);

if (optional_param('datavalidated', 0, PARAM_INT) == 1) {
    $helper->load_post();
    $modules = $helper->get_rowobjects();
    foreach($modules AS &$module) {
        if ($module->payload->processed) {
            // Pack the object before database-query
            $module->payload = json_encode($module->payload, JSON_NUMERIC_CHECK);
            if (isset($module->moduleid) && $module->moduleid > 0) {
                $module->id = $module->moduleid;
                $DB->update_record('local_eduvidual_modules', $module);
            } elseif(isset($module->categoryid) && $module->categoryid > 0) {
                $module->moduleid = $DB->insert_record('local_eduvidual_modules', $module, true);
            }
            // Unpack afterwards to restore previous state
            $module->payload = json_decode($module->payload);
        }
    }
    $helper->set_rowobjects($modules);
    ?>
    <form action="<?php echo $CFG->wwwroot; ?>/local/eduvidual/pages/sub/admin_modulesdownload.php" method="post" enctype="multipart/form-data">
        <p class="alert alert-info"><?php echo get_string('admin:modulesimport:downloadfile', 'local_eduvidual'); ?></p>
        <?php echo $helper->print_hidden_form(); ?>
        <input type="submit" value="<?php echo get_string('download'); ?>" />
    </form>
    <?php
} elseif (isset($_FILES['local_eduvidual_admin_modulesimport'])) {
    $helper->set_fields(array('moduleid', 'categoryid', 'name', 'description', 'url', 'imageurl'));
    $helper->load_file($_FILES['local_eduvidual_admin_modulesimport']['tmp_name']);
    $objs = $helper->get_rowobjects();
    $fields = $helper->get_fields();
    ?>
    <div class="t">
        <div class="t-row">
            <div class="t-cell">approved</div>
            <div class="t-cell">name</div>
            <div class="t-cell">description</div>
            <div class="t-cell">imageurl</div>
        </div>
        <?php
        foreach($objs AS $obj) {
            ?>
        <div class="t-row">
            <div class="t-cell"><img src="/pix/i/<?php echo ((isset($obj->payload->processed) && $obj->payload->processed)?'completion-auto-pass':'completion-auto-fail'); ?>.svg" /></div>
            <div class="t-cell"><?php echo $obj->name; ?></div>
            <div class="t-cell"><?php echo @$obj->description; ?></div>
            <div class="t-cell"><?php echo @$obj->imageurl; ?></div>
        </div>
            <?php
        }
        ?>
    </div>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="act" value="<?php echo $act; ?>" />
        <input type="hidden" name="import" value="1" />
        <input type="hidden" name="datavalidated" value="1" />
        <?php echo $helper->print_hidden_form(); ?>
        <input type="submit" value="<?php echo get_string('admin:modulesimport:datavalidated'); ?>" />
    </form>

    <?php
} else {
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="act" value="<?php echo $act; ?>" />
        <input type="hidden" name="import" value="1" />
        <input type="file" name="local_eduvidual_admin_modulesimport" />
        <input type="submit" value="<?php echo get_string('upload'); ?>" />
    </form>
    <?php
}
