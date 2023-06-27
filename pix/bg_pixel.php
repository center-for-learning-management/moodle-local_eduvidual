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
 * @copyright  2022 onwards Zentrum für Lernmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// livetime of cache in seconds -> 7 days
$exp = 60 * 60 * 24 * 7;
$exp_gmt = gmdate("D, d M Y H:i:s", time() + $exp) . " GMT";
$mod_gmt = gmdate("D, d M Y H:i:s", getlastmod()) . " GMT";
header("Expires: " . $exp_gmt);
header("Last-Modified: " . $mod_gmt);
header("Cache-Control: public, max-age=" . $exp);
// For MS Internet Explorer
header("Cache-Control: pre-check=" . $exp, FALSE);

$color = explode('x', $_GET['color']);

// If the color code is incorrect, we use black.
if (count($color) != 3) {
    $color = [0, 0, 0];
}

$gd = @imagecreatetruecolor(1, 1)
or die('Cannot initialize image stream');
$col = imagecolorallocate(
    $gd,
    intval($color[0]),
    intval($color[1]),
    intval($color[2]),
);
imagefill($gd, 0, 0, $col);

header('Content-Type: image/png');
imagepng($gd);
imagedestroy($gd);
