<?php
// Importiert BIP-Userdaten (vorerst nur Schüler:innen, role=std) in local_eduvidual_bip_user.
// Pro Aufruf wird genau eine Seite verarbeitet; der next_cursor wird in der Config persistiert.
// Standardmäßig wird ein Dry-Run gemacht (nichts wird geschrieben); --execute schreibt tatsächlich.
// Usage:
//   php local/eduvidual/cli/bip_import_users.php             (dry-run: nur Ausgabe)
//   php local/eduvidual/cli/bip_import_users.php --execute   (tatsächlich importieren)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(['execute' => false]);

$execute = (bool)$options['execute'];

if (!$execute) {
    cli_writeln('DRY-RUN mode — no data will be written. Use --execute to actually import.');
} else {
    cli_writeln('About to import BIP user data into local_eduvidual_bip_user.');
}

\local_eduvidual\bip_helper::import_users($execute);
