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

require_once('../../../../config.php');


$PAGE->set_context(context_system::instance());
require_login();
// This is used for standard-Logout to prevent mnet redirect
// It can be used for app too, but is not tested
// App currently logs out using login_app.php?dologout=1

if ($USER->auth == 'shibboleth') {
    redirect($CFG->wwwroot . '/Shibboleth.sso/Logout?return=' . urlencode($CFG->wwwroot));
}

$url = $CFG->wwwroot;
require_logout();
redirect($url);

?><!DOCTYPE html>
<html>
<head>
    <title><?php echo get_string('logout'); ?></title>
    <script>
        //localStorage.removeItem('local_eduvidual_originallocation');
        top.location.href = "<?php echo $url; ?>";
    </script>
</head>
<body></body>
</html>
