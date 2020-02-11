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
if (!block_eduvidual::get('role') == "Administrator") die;

$bufferedmode = optional_param('bufferedmode', 0, PARAM_INT);
if (!empty($bufferedmode)) {
    set_config('bufferedmode', ($bufferedmode == 1) ? true : false, 'block_eduvidual');
    //changeBufferedMode(($bufferedmode==1)?'enable':'disable');
}

echo $OUTPUT->render_from_template(
    'block_eduvidual/admin_bufferedmode',
    (object) array(
        'bufferedmodeon' => get_config('block_eduvidual', 'bufferedmode'), // defined('EDUVIDUAL_BUFFERED_MODE') && !defined('EDUVIDUAL_BUFFERED_MODE_UNSET'),
        'modifylogin' => get_config('block_eduvidual', 'modifylogin'),
        'requiredcapability' => get_config('block_eduvidual', 'requirecapability'),
        'wwwroot' => $CFG->wwwroot,
    )
);

// Below this line we collect functions

function changeBufferedMode($action) {
	global $CFG, $OUTPUT, $PAGE;
    $madebackup = false;

    $code_start = "/* start of eduvidual buffered mode */\n";
	$code = "if (file_exists(\$CFG->dirroot . '/blocks/eduvidual/buffered_mode.php')) {\n    require_once(\$CFG->dirroot . '/blocks/eduvidual/buffered_mode.php');\n}\n";
	$code_end = "/* end of eduvidual buffered mode */\n";

    $codecomplete = $code_start . $code . $code_end;

	if (is_writable($CFG->dirroot . '/config.php')) {
        // Create backup of working config
        $bfn = 'config-' . date("Y-m-d_h-i-s") . '.php';
        $bf = $CFG->dirroot . '/' . $bfn;
        copy($CFG->dirroot . '/config.php', $bf);
        $content = '';
        if (file_exists($bf)) {
            $madebackup = true;
            echo $OUTPUT->render_from_template(
                'block_eduvidual/alert',
                (object) array(
                    'type' => 'success',
                    'content' => get_string('bufferedmode:configcopy:success', 'block_eduvidual', array('bf' => $bfn )),
                )
            );
        } else {
            $anywayurl = $PAGE->url . '?act=bufferedmode&bufferedmode=' . optional_param('bufferedmode', 0, PARAM_INT) . '&force=1';
            echo $OUTPUT->render_from_template(
                'block_eduvidual/alert',
                (object) array(
                    'type' => 'warning',
                    'content' => get_string('bufferedmode:configcopy:failed', 'block_eduvidual', array('anywayurl' => $anywayurl)),
                )
            );
        }
    } else {
        echo $OUTPUT->render_from_template(
            'block_eduvidual/alert',
            (object) array(
                'type' => 'warning',
                'content' => get_string('bufferedmode:notwritable', 'block_eduvidual', array('codecomplete' => $codecomplete)),
            )
        );
        // If it is not writable it does not make sense to offer the force-mode
        return;
    }
    if (!$madebackup && optional_param('force', 0, PARAM_INT) != 1) {
        // We do not pass this line
        return false;
    }

	$buf = file_get_contents($CFG->dirroot . '/config.php');
	//echo "BUF1: " . $buf;
	// Try to remove any code from this
	$tmp = explode($code_start, $buf);
	// Every occurance after the first one contained a code_start
	for ($a = 1; $a < count($tmp); $a++) {
		$ln = explode($code_end, $tmp[$a]);
		// We preserve any code that has been after the code_end
		if (count($ln) > 1) {
			$tmp[$a] = $ln[1];
		} else {
			$tmp[$a] = "";
		}
	}
	$buf = implode("", $tmp);
	//echo "BUF2: " . $buf;
	// If action is enable append it
	if ($action == "enable") {
		$buf .= $codecomplete;
	}
	//echo "BUF3: " . $buf;
	$chk = file_put_contents($CFG->dirroot . '/config.php', $buf);
	if ($chk && $action == "enable") {
		// We set this to true to let UI show the current state
		if (!defined('EDUVIDUAL_BUFFERED_MODE')) define('EDUVIDUAL_BUFFERED_MODE', true);
	} elseif ($chk) {
		define('EDUVIDUAL_BUFFERED_MODE_UNSET', true);
	}
	return $chk;
}
