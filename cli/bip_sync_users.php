<?php
// Voll-Pass über die Spiegeltabelle local_eduvidual_bip_user: synct die Schulzuordnungen
// verlinkter Schüler:innen und legt Accounts für unverlinkte Schüler:innen an.
// Standardmäßig wird ein Dry-Run gemacht (nichts wird geschrieben); --execute schreibt tatsächlich.
// Usage:
//   php local/eduvidual/cli/bip_sync_users.php             (dry-run: nur Ausgabe)
//   php local/eduvidual/cli/bip_sync_users.php --execute   (tatsächlich syncen/anlegen)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(['execute' => false]);

$execute = (bool)$options['execute'];

if (!$execute) {
    cli_writeln('DRY-RUN mode — no data will be written. Use --execute to actually sync.');
} else {
    cli_writeln('About to sync org assignments and create accounts for BIP students.');
}

\local_eduvidual\bip_helper::sync_users($execute);
