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
 * @copyright  2020 Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Activate to be a betatester.
 */

require_once('../../../../config.php');

$setto = optional_param('setto', '', PARAM_ALPHANUM);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/local/eduvidual/pages/tools/betatester.php', array('setto' => $setto));
$PAGE->set_title('Beta test');
$PAGE->set_heading('Beta test');

if (!empty($setto)) {
    switch ($setto) {
        case 'tester':
            if (is_siteadmin()) {
                $host = optional_param('host', '', PARAM_ALPHANUM);
                setcookie('X-orgclass', 'tester', 0, '/');
                setcookie('X-usehost', $host, 0, '/');
                $_COOKIE['X-orgclass'] = 'tester';
                $_COOKIE['X-usehost'] = $host;
            }
        break;
        case 9:
            setcookie('X-orgclass', $setto, 0, '/');
            $_COOKIE['X-orgclass'] = 9;
        break;
        default:
            setcookie('X-userid', 0, 0, '/');
            $_COOKIE['X-orgclass'] = \local_eduvidual\locallib::set_xorg_data();
    }
}


$url_seton = $CFG->wwwroot . '/local/eduvidual/pages/tools/betatester.php?setto=9';
$url_setoff = $CFG->wwwroot . '/local/eduvidual/pages/tools/betatester.php?setto=-1';

require_login();

echo $OUTPUT->header();

?>
<h3>Liebe/r Nutzer/in,</h3>
<p>
    wir testen unsere neue Infrastruktur und bedanken uns sehr für dein Interesse,
    uns dabei zu helfen. Wenn der Testmodus aktiv ist, kannst du alle Funktionen
    der Seite ganz normal verwenden. Möglicherweise aber treten Fehler auf.
    Diese Fehler helfen uns die Probleme zu beheben, bevor wir das System für
    alle aktivieren. Bitte nutze in diesem Fall die Funktion "Problem melden", die
    du rechts oben unter dem Arztkoffer-Symbol vorfindest!
</p>
<p>
    Selbstverständlich kannst du hier auch jederzeit auf das normale Produktivsystem
    zurückschalten!
</p>
<?php

if ($_COOKIE['X-orgclass'] != 9) {
    ?>
    <a href="<?php echo $url_seton; ?>" class="btn btn-primary btn-block">
        Aktivieren
    </a>
    <?php
} else {
    ?>
    <a href="<?php echo $url_setoff; ?>" class="btn btn-primary btn-block">
        Deaktivieren
    </a>
    <?php
}

if (is_siteadmin()) {
    ?>
    <hr />
    <p>Bestimmten Server einstellen:</p>
    <a href="<?php echo $url_setoff; ?>" class="btn btn-primary btn-block">
        Deaktivieren
    </a>
    <?php
    $servers = array("mdcommunity","evweb01","evweb02","evweb03","evweb04","evweb05","evweb06","evcron01");
    foreach ($servers as $server) {
        $url_seton = $CFG->wwwroot . '/local/eduvidual/pages/tools/betatester.php?setto=tester&host=' . $server;
        ?>
        <a href="<?php echo $url_seton; ?>" class="btn btn-secondary btn-block">
            <?php
            if ($_COOKIE['X-orgclass'] == 'tester' && $_COOKIE['X-usehost'] == $server) {
                echo "Aktiv: $server";
            } else {
                echo "Aktiviere $server";
            }
            ?>
        </a>
        <?php
    }
}


echo $OUTPUT->footer();
