<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
// Spiegelt BIP-Userdaten (Delta) in die Tabelle local_eduvidual_bip_user und trägt
// gelöschte Schüler:innen aus. Org-Sync und Schüler-Anlage macht danach cli/bip_update_users.php.
// Es werden alle ausstehenden Seiten verarbeitet; der next_cursor wird nach jeder Seite persistiert.
// Standardmäßig wird ein Dry-Run gemacht (nichts wird geschrieben); --execute schreibt tatsächlich.
// --resync ignoriert den gespeicherten Cursor und startet einen Voll-Sweep von vorne.
// Usage:
//   php local/eduvidual/cli/bip_import_users.php                      (dry-run: nur Ausgabe)
//   php local/eduvidual/cli/bip_import_users.php --execute            (tatsächlich importieren)
//   php local/eduvidual/cli/bip_import_users.php --execute --resync   (kompletter Neuaufbau)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(['execute' => false, 'resync' => false]);

$execute = (bool)$options['execute'];
$resync = (bool)$options['resync'];

if (!$execute) {
    cli_writeln('DRY-RUN mode — no data will be written. Use --execute to actually import.');
} else {
    cli_writeln('About to mirror BIP user data into local_eduvidual_bip_user.');
}

\local_eduvidual\bip_helper::import_users($execute, $resync);
