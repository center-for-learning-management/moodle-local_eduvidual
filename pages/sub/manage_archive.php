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

$trashcategory = get_config('block_eduvidual', 'trashcategory');

$step = 0;
$source = optional_param_array("source", array(), PARAM_INT);
$target = optional_param("target", 0, PARAM_INT);
$confirmation = optional_param("confirmation", 0, PARAM_INT);
if (count($source) > 0) $step = 1;
if (count($source) > 0 && $target > 0) $step = 2;
if (count($source) > 0 && $target > 0 && $confirmation > 0) $step = 3;

// @TODO translations !!!!

?>

<div class="card eduvidual-courses-progress">
	<div><a href="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/manage.php?act=<?php echo $act; ?>&orgid=<?php echo $org->orgid; ?>">
        <?php echo get_string('manage:archive:restart', 'block_eduvidual'); ?>
    </a></div>
	<div<?php echo (($step==0)?" class=\"active\"":""); ?>>
        <?php echo get_string('manage:archive:source', 'block_eduvidual'); ?>
    </div>
	<div<?php echo (($step==1)?" class=\"active\"":""); ?>>
        <?php echo get_string('manage:archive:target', 'block_eduvidual'); ?>
    </div>
	<div<?php echo (($step==2)?" class=\"active\"":""); ?>>
        <?php echo get_string('manage:archive:confirmation', 'block_eduvidual'); ?>
    </div>
	<div<?php echo (($step==3)?" class=\"active\"":""); ?>>
        <?php echo get_string('manage:archive:action', 'block_eduvidual'); ?>
    </div>
</div>
<div class="card" style="width: 100%; overflow-x: auto;"><div>
<?php

$MANAGECATS = array();

/*
// This is used if we can archive for various organizations at once
$orgas = block_eduvidual::get_organisations('Manager');
foreach($orgas AS $orga) {
    $MANAGECATS[] = $orga->categoryid;
}
*/
$MANAGECATS[] = $org->categoryid;

if ($step == 0) { ?>
	<h3><?php echo get_string('manage:archive:source:title', 'block_eduvidual'); ?></h3>
	<p class="alert alert-info">
        <?php echo get_string('manage:archive:source:description', 'block_eduvidual'); ?>
		<!--
        Nutzen Sie diese Funktion um eine große Menge an Kursen zu verschieben. So können Sie bspw. nach Abschluss
		eines Schuljahres alle Kurse in ein Archiv schieben (die Kurskategorien des aktuellen Jahres bleiben erhalten),
		oder in den "Papierkorb" stecken.
        -->
	</p>
	<form action="?" method="post" enctype="multipart/form-data">
	<input type="hidden" name="act" value="<?php echo $act; ?>" />
	<input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
	<input type="submit" value="<?php echo get_string('proceed', 'block_eduvidual'); ?>" class="btn btn-primary" />
	<ul class="ul-eduvidual-courses" data-role="treeview">
	<?php
	$schools = $DB->get_records_sql("SELECT id,name,path FROM {course_categories} WHERE id='" . implode("' OR id='", $MANAGECATS) . "' ORDER BY name ASC",array());
	foreach($schools AS $school) {
		$children = fetchChildren($school->id);
		listChildren($children);
	}
	?>
	</ul>
	<input type="submit" value="<?php echo get_string('proceed', 'block_eduvidual'); ?>" class="btn btn-primary" />
	</form>
<?php }
if ($step == 1) { ?>
	<h3><?php echo get_string('manage:archive:target:title', 'block_eduvidual'); ?></h3>
	<p><?php echo get_string('manage:archive:target:description', 'block_eduvidual', array('count' => count($source))); ?></p>
	<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="act" value="<?php echo $act; ?>" />
	<input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
	<input type="submit" value="<?php echo get_string('proceed', 'block_eduvidual'); ?>" class="ui-btn" />
	<?php
	$courseids = array_keys($source);
	foreach($courseids AS $courseid) {
		echo "\t\t<input type=\"hidden\" name=\"source[" . $courseid . "]\" value=\"" . $source[$courseid] . "\" />\n";
	}
	?>
	<ul class="ul-eduvidual-courses" data-role="treeview">
		<?php
		if ($trashcategory > 0) {
			?>
			<li style="margin-left: 18px;"><label><input type="radio" name="target" value="<?php echo $trashcategory; ?>" data-role="none" />&nbsp;<?php echo get_string('manage:archive:trashbin', 'block_eduvidual'); ?></label></li>
			<?php
		}

	$schools = $DB->get_records_sql("SELECT id,name,path FROM {course_categories} WHERE id='" . implode("' OR id='", $MANAGECATS) . "' ORDER BY name ASC",array());
	foreach($schools AS $school) {
		$children = fetchChildren($school->id);
		listChildren($children, false);
	}
	?>
	</ul>
	<input type="submit" value="<?php echo get_string('proceed', 'block_eduvidual'); ?>" class="ui-btn" />
	</form>
	<?php
	if ($trashcategory > 0) {
		?>
	<p class="alert alert-info">
        <?php echo get_string('manage:archive:trashbin:description', 'block_eduvidual'); ?>
        <!--
		Kurse können in den Papierkorb hineingeschoben werden. Bis dahin bleibt der Kurs erhalten (inkl. Einschreibungen),
		und kann von jeder im Kurs eingeschriebenen Lehrperson über die Kurseinstellungen wiederhergestellt werden.<br />
		<br />
		Jeden Sonntag wird der Papierkorb automatisch geleert. Ab dann ist eine Wiederherstellung <strong>nicht</strong>
		mehr möglich.
        -->
	</p>
		<?php
	}
}
if ($step == 2) {
?>
	<h3><?php echo get_string('confirm'); ?></h3>
	<p><?php echo get_string('manage:archive:target:description', 'block_eduvidual', array('count' => count($source))); ?></p>
	<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="act" value="<?php echo $act; ?>" />
	<input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
	<input type="hidden" name="confirmation" value="1" />
	<?php
	$courseids = array_keys($source);
	foreach($courseids AS $courseid) {
		echo "\t\t<input type=\"hidden\" name=\"source[" . $courseid . "]\" value=\"" . $source[$courseid] . "\" />\n";
	}
	echo "\t\t<input type=\"hidden\" name=\"target\" value=\"" . $target . "\" />\n";

	$t = $DB->get_records_sql("SELECT id,name FROM {course_categories} WHERE id=?",array($target));
	$category = $t[$target];
	?>
	<input type="submit" value="<?php echo get_string('confirm'); ?>" class="ui-btn" />
	</form>
	<p>
        <?php echo get_string('manage:archive:confirmation:description', 'block_eduvidual', array('name' => $category->name)); ?>
	</p>
	<ul data-role="treeview"><?php
		$courseids = array_keys($source);
		$courses = $DB->get_records_sql("SELECT id,fullname FROM {course} WHERE id='" . implode("' OR id='", $courseids) . "' ORDER BY fullname ASC",array());
		foreach($courses AS $course) {
			echo "\t\t<li>" . $course->fullname . "</li>\n";
		}
	?></ul>
<?php
}
if ($step == 3) {
	$schools = $DB->get_records_sql("SELECT id,name,path FROM {course_categories} WHERE id='" . implode("' OR id='", $MANAGECATS) . "' ORDER BY name ASC",array());
	$children = array();
	foreach($schools AS $school) {
		$children[] = fetchChildren($school->id);
	}
	// Use children-object to determine if all actions are allowed
	?>
	<h3><?php echo get_string('manage:archive:action:title', 'block_eduvidual'); ?></h3>
	<?php
	$lines = array(); $failed = 0; $succeed = 0;
	$lines[] = "\t<ul data-role=\"treeview\">";
	if ($target == $trashcategory || searchChild($children, "category", $target)) {
		$lines[] = "\t\t<li>" . get_string('manage:archive:action:targetok', 'block_eduvidual') . "</li>\n";

		$courseids = array_keys($source);
        require_once($CFG->dirroot . '/course/lib.php');
		$courses = $DB->get_records_sql("SELECT id,fullname FROM {course} WHERE id='" . implode("' OR id='", $courseids) . "' ORDER BY fullname ASC",array());
		foreach($courses AS $course) {
			if (searchChild($children, "course", $course->id)) {
                // @TODO this is not good - use API!!!!
                //$_course = get_course($course->id);
                $chk = move_courses(array($course->id), $target);
				//$chk = $DB->execute("UPDATE {course} SET category=? WHERE id=?", array($target, $course->id));
				if ($chk) {
					$succeed++;
					$lines[] = "\t\t<li>" . get_string('manage:archive:action:coursemoved', 'block_eduvidual', array('name' => $course->fullname)) . "</li>\n";
				} else {
					$failed++;
					$line[] = "\t\t<li><strong>" . get_string('manage:archive:action:courseNOTmoved', 'block_eduvidual', array('name' => $course->fullname)) . "</strong></li>\n";
				}
			} else {
				$failed++;
				$lines[] = "\t\t<li>" . get_string('manage:archive:action:coursecannotmanage', 'block_eduvidual', array('name' => $course->fullname)) . "</li>\n";
			}
		}
	} else {
		$failed++;
		$lines[] = "\t\t<li>" . get_string('manage:archive:action:targetinvalid', 'block_eduvidual') . "</li>\n";
	}
	$lines[] = "\t</ul>";

	if ($failed > 0) {
		echo "<p class=\"alert alert-error\">" . get_string('manage:archive:action:failures', 'block_eduvidual', array('failures' => $failed)) . "</p>";
	}
	if ($succeed > 0) {
		echo "<p class=\"alert alert-success\">" . get_string('manage:archive:action:successes', 'block_eduvidual', array('successes' => $succeed)) . "</p>";
	}
	echo implode("", $lines);
}
?>
</div></div>
<?php
// BELOW THIS LINE WE ONLY COLLECT FUNCTIONS

function fetchChildren($category) {
	global $DB;
	$t = $DB->get_records_sql("SELECT id,name,path FROM {course_categories} WHERE id=?", array($category));
	$cat = $t[$category];
    $cat->coursecount = $DB->count_records('course', array('category' => $category));
	$cat->courses = array();
	$t = $DB->get_records_sql("SELECT id,fullname FROM {course} WHERE category=? ORDER BY fullname ASC",array($category));
	foreach($t AS $r) {
		$cat->courses[] = $r;
	}
	$cat->categories = array();
	$t = $DB->get_records_sql("SELECT id,name,path FROM {course_categories} WHERE parent=? ORDER BY name ASC",array($category));
	foreach($t AS $r) {
		$cat->categories[] = fetchChildren($r->id);
	}
	return $cat;
}
function searchChild($subtree, $type = "course", $id = 0) {
	if (is_array($subtree)) { // Top-Level
		foreach($subtree AS $school) {
			if (searchChild($school, $type, $id)) return true;
		}
		return false;
	} else {
		if ($type == "course") {
			foreach($subtree->courses AS $course) {
				if ($course->id == $id) return true;
			}
		}
		if ($type == "category") {
			if ($subtree->id == $id) return true;
		}
		foreach ($subtree->categories AS $category) {
			if (searchChild($category, $type, $id)) return true;
		}
		return false;
	}
}
function listChildren($subtree, $assource = true) {
    global $org;
	$path = printPath($subtree->path, $assource);
	?>
	<li class="<?php echo $path . (($subtree->id == $org->categoryid)?' shown':''); ?>" data-enhance="false" data-role="none">
		<?php
			if ($assource) { ?>
                <a href="#" class="btn-show ui-mini" onclick="eduvidual_toggle_shown('<?php echo $path; ?>'); return false;"><img src="/pix/t/collapsed.svg" alt=">" /></a>
                <a href="#" class="btn-check ui-mini" onclick="eduvidual_toggle_checked('<?php echo $path; ?>'); return false;">alles wählen</a>
                <label data-enhance="false" data-role="none">
    				&nbsp;<?php echo $subtree->name . ' (' . $subtree->coursecount . ')'; ?>
                </label>
			<?php } else { ?>
                <a href="#" class="btn-show ui-mini" onclick="eduvidual_toggle_shown('<?php echo $path; ?>'); return false;"><img src="/pix/t/collapsed.svg" alt=">" /></a>
                <label data-enhance="false" data-role="none">
    				<input type="radio" data-role="none" name="target" value="<?php echo $subtree->id; ?>" />&nbsp;<?php echo $subtree->name; ?>
                </label>
			<?php }
			if (count($subtree->categories) > 0) {
				echo "\t\t\t<ul class=\"categories\">";
				foreach($subtree->categories AS $cat) {
					listChildren($cat, $assource);
				}
				echo "\t\t\t</ul>";
			}
			if ($assource && count($subtree->courses) > 0) {
				echo "\t\t\t<ul class=\"courses\">";
				foreach($subtree->courses AS $co) {
                    ?>
					<li>
                        <label data-enhance="false" data-role="none">
                            <input type="checkbox" name="source[<?php echo $co->id; ?>]" data-enhance="false" data-role="none" />&nbsp;<?php echo $co->fullname; ?>
                        </label>
                    </li>
                    <?php
				}
				echo "\t\t\t</ul>";
			}
		?>
	</li>
	<?php
}

function printPath($path, $assource = true) {
	return (($assource)?'source':'target').str_replace("/", "-", $path);
}
