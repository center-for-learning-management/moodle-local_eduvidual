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
 * @copyright  2026 Center for Learning Management (www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\task;

defined('MOODLE_INTERNAL') || die;

/**
 * Kompletter BIP-Sync in einem Lauf, bewusst in dieser Reihenfolge:
 * 1. Schulen importieren (pflegt u.a. das biporg-Flag, siehe is_syncable_org)
 * 2. User-Delta in die Spiegeltabelle importieren (inkl. Austragen gelöschter Schüler:innen)
 * 3. Voll-Pass über die Spiegeltabelle: Schulzuordnungen verknüpfter Schüler:innen
 *    syncen und Accounts für neue Schüler:innen anlegen
 */
class bip_import extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('bip_import:title', 'local_eduvidual');
    }

    public function execute() {
        \local_eduvidual\bip_helper::import_orgs(true);
        \local_eduvidual\bip_helper::import_users(true);
        \local_eduvidual\bip_helper::update_users(true);
    }
}
