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
// Voll-Pass über die Spiegeltabelle local_eduvidual_bip_user: synct die Schulzuordnungen
// verlinkter Schüler:innen und legt Accounts für unverlinkte Schüler:innen an.
// Standardmäßig wird ein Dry-Run gemacht (nichts wird geschrieben); --execute schreibt tatsächlich.
// Usage:
//   php local/eduvidual/cli/bip_update_users.php             (dry-run: nur Ausgabe)
//   php local/eduvidual/cli/bip_update_users.php --execute   (tatsächlich syncen/anlegen)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(['execute' => false]);

$execute = (bool)$options['execute'];

if (!$execute) {
    cli_writeln('DRY-RUN mode — no data will be written. Use --execute to actually sync.');
} else {
    cli_writeln('About to sync org assignments and create accounts for BIP students.');
}

\local_eduvidual\bip_helper::update_users($execute);
