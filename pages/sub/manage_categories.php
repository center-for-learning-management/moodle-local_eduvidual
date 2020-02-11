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

?>
<!-- <link rel="stylesheet" href="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/css/jquery.mobile-1.4.5.min.css"></link> -->

<h5><?php echo get_string('manage:coursecategories', 'block_eduvidual'); ?></h5>
<ul class="ul-eduvidual-courses" data-orgid="<?php echo $org->orgid; ?>" data-role="treeview">
<?php
$tree = $DB->get_record('course_categories', array('id' => $org->categoryid));
block_eduvidual_manage_category_tree($tree);
block_eduvidual_manage_category_printtree($tree);
?>
</ul>

<?php

/**
 * Recursively retrieves all children of a specific category
 * @return array of sub categories to specificied categoryid
**/
function block_eduvidual_manage_category_tree($subtree) {
	global $DB;
	$subtree->categories = $DB->get_records('course_categories', array('parent' => $subtree->id));
    $coursecount = $DB->count_records('course', array('category' => $subtree->id));
	$keys = array_keys($subtree->categories);
	for ($a = 0; $a < count($keys); $a++) {
		$subtree->categories[$keys[$a]] = block_eduvidual_manage_category_tree($subtree->categories[$keys[$a]]);
	}
	return $subtree;
}

/**
 * Recursively prints all children of a category tree
**/
function block_eduvidual_manage_category_printtree($subtree) {
    global $org;
	$path = block_eduvidual_manage_category_printPath($subtree->path);
	?>
	<li class="<?php echo $path . (($org->categoryid == $subtree->id)?' shown':''); ?>" data-categoryid="<?php echo $subtree->id; ?>">
		<label>
            <?php
                if (count($subtree->categories) > 0) {
                    ?>
                    <a href="#" class="btn-show ui-mini btn-open" onclick="eduvidual_toggle_shown('<?php echo $path; ?>'); return false;"><img src="/pix/t/collapsed.svg" alt=">" /></a>
                    <?php
                }
            ?>
	        <span><?php echo $subtree->name; ?></span> (<?php echo $subtree->coursecount; ?>)
            <a href="#" class="btn-check ui-mini" onclick="var a = this; require(['block_eduvidual/manager'], function(MANAGER) { MANAGER.categoryAdd(a); });">
                <img src="/pix/t/add.svg" alt="<?php echo get_string('add'); ?>" />
            </a>
            <a href="#" class="btn-check ui-mini" onclick="var a = this; require(['block_eduvidual/manager'], function(MANAGER) { MANAGER.categoryEdit(a, undefined, undefined, '<?php echo $subtree->name; ?>'); });">
                <img src="/pix/t/edit.svg" alt="<?php echo get_string('edit'); ?>" />
            </a>
            <a href="#" class="btn-check ui-mini" onclick="var a = this; require(['block_eduvidual/manager'], function(MANAGER) { MANAGER.categoryRemove(a); });">
                <img src="/pix/t/delete.svg" alt="<?php echo get_string('delete'); ?>" />
            </a>
        </label>
            <?php
			if (count($subtree->categories) > 0) {
				echo "\t\t\t<ul class=\"categories\">";
				foreach($subtree->categories AS $cat) {
					block_eduvidual_manage_category_printtree($cat);
				}
				echo "\t\t\t</ul>";
			}
		?>
	</li>
	<?php

}

function block_eduvidual_manage_category_printPath($path) {
	return 'block_eduvidual_manage_categories_' . str_replace("/", "-", $path);
}
