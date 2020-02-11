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
define('EDUVIDUAL_BUFFERED_MODE', true);

global $CFG, $USER;
if (class_exists('context_system')) {
    $sysctx = context_system::instance();
    $isdownload = strpos($_SERVER["SCRIPT_FILENAME"], '/pluginfile.php') > 0;
    if (empty($isdownload) && ($USER->id == 0 || get_config('block_eduvidual', 'requirecapability') == 0 || has_capability('block/eduvidual:useinstance', $sysctx, null, false) == 1)) {
        define('EDUVIDUAL_BUFFERED_MODE_ALLOW', true);
    } else {
        define('EDUVIDUAL_BUFFERED_MODE_ALLOW', false);
    }

    if ($USER->id != -1 && EDUVIDUAL_BUFFERED_MODE_ALLOW) {
        require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');
        ob_start();
        register_shutdown_function("block_eduvidual_ob_callback");
    }
    /*
    echo "block_eduvidual";
    echo $USER->id . " || " . get_config('block_eduvidual', 'requirecapability') . "(= " . ((get_config('block_eduvidual', 'requirecapability') == 0)?'yes':'no') . ")" . " || " . has_capability('block/eduvidual:useinstance', $sysctx, null, false);
    */
} else {
    define('EDUVIDUAL_BUFFERED_MODE_ALLOW', false);
}


function block_eduvidual_ob_callback() {
    $buffer = ob_get_clean();
    global $CFG, $DB, $PAGE;

    require_once($CFG->dirroot . '/blocks/eduvidual/block_eduvidual.php');

    $ajaxs = array("{", "[");
    if (in_array(substr(trim($buffer), 0, 1), $ajaxs)) {
        // AJAX-Request - Attention!
        //echo $buffer;
        require_once($CFG->dirroot . '/blocks/eduvidual/buffer/ajax.php');
        echo $buffer;
    } elseif (substr(trim($buffer), 0, strlen("<!DOCTYPE html>")) == "<!DOCTYPE html>"){
        require_once($CFG->dirroot . '/blocks/eduvidual/buffer/html.php');
        echo $buffer;
    } else {
        // Something else
        echo $buffer;
    }
    //try { ob_end_flush(); } catch(Exception $e) {}
}
