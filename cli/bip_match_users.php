<?php
// Match BIP-User mit Moodle-Accounts und lege fehlende auth_shibboleth_link-Einträge an.
// Standardmäßig wird ein Dry-Run gemacht (nichts wird geschrieben); --execute schreibt tatsächlich.
// Usage:
//   php local/eduvidual/cli/bip_match_users.php             (dry-run: nur Ausgabe)
//   php local/eduvidual/cli/bip_match_users.php --execute   (tatsächlich verlinken)

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(['execute' => false]);

$execute = (bool)$options['execute'];

if (!$execute) {
    cli_writeln('DRY-RUN mode — no data will be written. Use --execute to actually perform the match.');
} else {
    cli_writeln('About to create auth_shibboleth_link entries for matched BIP <-> Moodle users.');
}

\local_eduvidual\bip_helper::match_users($execute);
