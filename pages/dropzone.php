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
 * Links and settings
 * @package    block_eduvidual
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

block_eduvidual::set_context_auto();
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/eduvidual/pages/dropzone.php', array());
$PAGE->set_title('eduvidual Dropzone');
$PAGE->set_heading('eduvidual Dropzone');
//$PAGE->set_cacheable(false);

$PAGE->requires->css('/blocks/eduvidual/style/dropzone.css');
$PAGE->requires->js('/blocks/eduvidual/js/dropzone.js');

block_eduvidual::print_app_header();

$roles = array('Administrator', 'Manager');

if (!in_array(block_eduvidual::get('role'), $roles)) {
	?>
		<p class="alert alert-danger"><?php echo get_string('access_denied', 'block_eduvidual'); ?></p>
	<?php
	block_eduvidual::print_app_footer();
	die();
}

$reponame = 'u' . $USER->id;
$repolabel = fullname($USER);
// From now on we will use a user-specific dropzone-Path
$dropzonepath = $CFG->dataroot . '/repository/' . $reponame;
//$dropzonepath = get_config('block_eduvidual', 'dropzonepath');
if (empty($dropzonepath)) {
    ?>
        <p class="alert alert-danger"><?php echo get_string('admin:dropzone:notset', 'block_eduvidual'); ?></p>
    <?php
    block_eduvidual::print_app_footer();
    die();
}
// Just to be sure it is used as directory.
$dropzonepath .= '/';

if (!is_dir($dropzonepath)) {
    mkdir($dropzonepath);
}

if (isset($_FILES["dropfile"])) {
    file_put_contents($dropzonepath . $_FILES["dropfile"]["name"], file_get_contents($_FILES["dropfile"]["tmp_name"]), FILE_APPEND);
    // This is the old line that uses another filename.
    //file_put_contents($dropzonepath . $USER->id . "_" . str_replace("@", "_", $USER->email) . "_" . date("Y-m-d") . "_" . @$_POST["stamp"] . ".mbz", file_get_contents($_FILES["dropfile"]["tmp_name"]), FILE_APPEND);

    // Assign user the "repository filesystem"-role
    /* This is the old style.
    $context = context_system::instance();
    role_assign(15, $USER->id, $context->id);
    */
    $context = \context_user::instance($USER->id);
    $repo = $DB->get_record('repository', array('type' => 'filesystem'));
    $check = $DB->get_record('repository_instances', array('typeid' => $repo->id, 'contextid' => $context->id));
    if (empty($check->id)) {
        $params = (object) array(
            'name' => $repolabel,
            'typeid' => $repo->id,
            'userid' => 0,
            'contextid' => $context->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'readonly' => 0,
        );
        $params->id = $DB->insert_record('repository_instances', $params);
        if (!empty($params->id)) {
            $config = (object) array(
                'instanceid' => $params->id,
                'name' => 'fs_path',
                'value' => $reponame,
            );
            $DB->insert_record('repository_instance_config', $config);
            cache::make('core', 'repositories')->purge();
        }
    } else {
        // Maybe our name has changed - so we will change that too....
        $DB->set_field('repository_instances', 'name', $repolabel, array('id' => $check->id));
    }

	die();
}
?>
<h3>Dropzone</h3>
<p>
	Hier können große Kurssicherungen hochgeladen werden, sofern diese das Maximum der Webseite überschreiten.
	Die Vorgehensweise ist wie folgt:
</p>
<ol>
	<li>Laden Sie die Datei im folgenden Feld hoch</li>
	<li>Erstellen Sie einen leeren Kurs und navigieren Sie in diesen Kurs</li>
	<li>Starten Sie die Wiederherstellung</li>
	<li>Im Repository "Dateisystem" finden Sie Ihre hochgeladene Datei, wählen Sie sie aus</li>
	<li>Führen Sie die Wiederherstellung wie gewohnt aus</li>
	<li>Löschen Sie Ihre Sicherungsdatei auf dieser Seite wieder (wird jeden Tag auch automatisch gemacht)</li>
</ol>
<form action="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/dropzone.php" method="post" id="eduvidual-dropzone">
	<input type="hidden" name="stamp" value="<?php echo substr(md5(date("i:s")), 0, 4); ?>" />
</form>
<p>
	Hinweis: Bitte immer nur 1 Datei hochladen, danach <a href="#" onclick="top.location.reload();">neu laden</a>, bevor man die nächste Datei hochlädt.
</p>
<script type="text/javascript">
window.onload = function(){
	//Dropzone.autoDiscover = false;
	var options = {
		acceptedFiles: '.mbz',
		chunking: true,
		chunkSize: 2*1024*1024,
		paramName: 'dropfile',
		maxFiles: 1,
	}
	var uploader = new Dropzone('#eduvidual-dropzone', options);
	uploader.on("complete", function(){ top.location.reload(); });
    document.getElementById('eduvidual-dropzone').className = 'dropzone';
}
</script>

<h3>Ihre Dateien</h3>
<ul>
<?php

$filter = '';
//$filter = $USER->id . "_";
if (isset($_GET["remove"]) && (empty($filter) || substr($_GET["remove"], 0, strlen($filter)) == $filter)) {
	if(unlink($dropzonepath . $_GET["remove"]))
		echo "<p class=\"alert alert-success\">Datei '" . $_GET["remove"] . "' gelöscht</p>";
	else
		echo "<p class=\"alert alert-warning\">Datei '" . $_GET["remove"] . "' konnte nicht gelöscht werden</p>";
}

$d = opendir($dropzonepath);
while($f=readdir($d)) {
	if (str_replace(".", "", $f) == "") continue;
	if (!empty($filter) && substr($f, 0, strlen($filter)) != $filter) continue;
	echo "<li>" . $f . " [<a href=\"?remove=" . $f . "\">löschen</a>]</li>";
}

?>
</ul>
<?php
echo $OUTPUT->footer();
