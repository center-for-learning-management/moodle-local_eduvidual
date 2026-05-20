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
 * Listet alle Moodle-User einer Org (vorerst nur role=std) und matched per
 * auth_shibboleth_link.idpusername gegen die importierten BIP-Userdaten.
 *
 * @package    local_eduvidual
 * @copyright  2026 Center for Learning Management (www.lernmanagement.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$orgid = required_param('orgid', PARAM_INT);

$org = $DB->get_record('local_eduvidual_org', ['orgid' => $orgid], '*', MUST_EXIST);
$context = \context_coursecat::instance($org->categoryid);
$PAGE->set_context($context);

$PAGE->set_url(new \moodle_url('/local/eduvidual/pages/manage_bip_matching.php', ['orgid' => $orgid]));
$PAGE->set_title(get_string('manage:bipmatching', 'local_eduvidual'));
$PAGE->set_heading(get_string('manage:bipmatching', 'local_eduvidual'));

require_capability('local/eduvidual:canmanage', $context);

$PAGE->navbar->add(get_string('Management', 'local_eduvidual'), new moodle_url('/local/eduvidual/pages/manage.php', ['orgid' => $orgid]));
$PAGE->navbar->add(get_string('manage:bipmatching', 'local_eduvidual'), $PAGE->url);

// Default-IDP für neue Verknüpfungen: ersten Eintrag aus der organization_selection-Config nehmen
// (siehe auth/shibboleth_link/auth.php::loginpage_idp_list()).
$idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
$defaultidp = $idps ? trim(explode(',', $idps[0])[0]) : '';

$table = new class($org, $defaultidp) extends local_table_sql\table_sql_form {
    function __construct(private object $org, public string $defaultidp) {
        parent::__construct([$org->orgid]);
    }

    /**
     * bpkbfs, die bereits an einen Moodle-User dieser Org verknüpft sind.
     * Verknüpfungen in anderen Orgs schließen einen User hier nicht aus.
     * Lazy + statisch gecached, damit die Query nicht mehrfach pro Request läuft.
     */
    public function linked_bpkbfs(): array {
        global $DB;
        static $cache = null;
        if ($cache === null) {
            $cache = array_flip($DB->get_fieldset_sql("
                SELECT ash.idpusername
                FROM {auth_shibboleth_link} ash
                JOIN {local_eduvidual_orgid_userid} ou ON ou.userid = ash.userid AND ou.orgid = ?
            ", [$this->org->orgid]));
        }
        return $cache;
    }

    /**
     * Liefert pro bpkbf einen aggregierten bip_user-Datensatz für die Org, indiziert nach bpkbf.
     * firstname/lastname/email/dateofbirth sind über die Rollen hinweg gleich, daher MAX().
     * Statisch gecached, damit wiederholte Aufrufe nicht erneut die DB anfragen.
     */
    public function bipusers_for_org(): array {
        global $DB;
        static $cache = null;
        if ($cache === null) {
            $cache = $DB->get_records_sql("
                SELECT bpkbf,
                    MIN(id) AS id,
                    MAX(firstname) AS firstname,
                    MAX(middlename) AS middlename,
                    MAX(lastname) AS lastname,
                    MAX(email) AS email,
                    MAX(dateofbirth) AS dateofbirth
                FROM {local_eduvidual_bip_user}
                WHERE orgid = ?
                GROUP BY bpkbf
            ", [$this->org->orgid]);
            $cache = array_column($cache, null, 'bpkbf');
        }
        return $cache;
    }

    /**
     * Bauen der Dropdown-Optionen pro Zeile. $currentid ist die bip_user.id der aktuell
     * verknüpften Zeile (NULL falls keine Verknüpfung oder Orphan-Link ohne bip_user-Record).
     * Im Frontend wird nur die bip_user.id verwendet - der bpkbf bleibt server-seitig.
     */
    function bipuserid_options_for_row(?int $currentid): array {
        $bipusers = $this->bipusers_for_org();
        $byid = array_column($bipusers, null, 'id');
        // Erste Option mit leerem Label, damit Moodles autocomplete-JS keine echte
        // Option als Default selektiert (siehe lib/amd/src/form-autocomplete.js:
        // "the first option added will be selected by the browser. This causes a bug!").
        // Schlüssel 0, weil setType(PARAM_INT) den Formularwert auf 0 normalisiert -
        // so matched diese leere Option und das Widget zeigt placeholder/noselectionstring.
        $opts = [0 => ''];

        if ($currentid && isset($byid[$currentid])) {
            $opts[$currentid] = static::format_bipuser_label($byid[$currentid]);
        }

        $linked = $this->linked_bpkbfs();
        foreach ($bipusers as $bu) {
            if ((int)$bu->id === $currentid || isset($linked[$bu->bpkbf])) {
                continue;
            }
            $opts[$bu->id] = static::format_bipuser_label($bu);
        }
        return $opts;
    }

    static function format_bipuser_label(object $bu): string {
        $name = trim(join(' ', array_filter([$bu->lastname, $bu->firstname])));
        return $name . ($bu->dateofbirth ? " ({$bu->dateofbirth})" : '') . ($bu->email ? " <{$bu->email}>" : '');
    }

    function define_table_configs() {
        // userpic-Felder mit Prefix "up_" aliased, damit sie nicht mit u.id/firstname/... kollidieren.
        $userpicfields = \core_user\fields::for_userpic()->get_sql('u', false, 'up_', '', false)->selects;

        global $DB;

        // ou-Subquery: pro (userid, orgid) eine Zeile - sonst würden User mit mehreren Rollen
        // in der Org (z.B. Student + Teacher) doppelt in der Tabelle erscheinen, sobald der
        // role-Filter später entfernt wird. GROUP_CONCAT(role) zeigt dann alle Rollen in einer
        // Zelle (aktuell immer "Student" wegen des Filters).
        //
        // bu-Subquery: bip_user kann pro (bpkbf, orgid) mehrere Rollen haben (std, tch, lgn, ...).
        // firstname/lastname/email/dateofbirth sind user-level und über die Rollen hinweg gleich,
        // daher reicht MAX() für die Anzeige. bipuserid=MIN(id) ist konsistent mit der Aggregation
        // in bipusers_for_org() - das Dropdown indiziert nach bpkbf, eine ID pro bpkbf reicht.
        // WHERE orgid=? im Subquery nutzt den Index und macht das GROUP BY billig.
        $rolegc = $DB->sql_group_concat('role', ', ', 'role');
        $this->set_sql_query("
            SELECT u.id, u.firstname, u.lastname, u.email,
                {$userpicfields},
                ou.role,
                bu.bipuserid,
                bu.bip_firstname,
                bu.bip_middlename,
                bu.bip_lastname,
                bu.bip_email,
                bu.bip_dateofbirth,
                ash.idpusername AS bpkbf,
                ash.source
            FROM {user} u
            JOIN (
                SELECT userid, orgid, {$rolegc} AS role
                FROM {local_eduvidual_orgid_userid}
                WHERE orgid = ? AND role = ?
                GROUP BY userid, orgid
            ) ou ON ou.userid = u.id
            LEFT JOIN {auth_shibboleth_link} ash ON ash.userid=u.id
            LEFT JOIN (
                SELECT bpkbf,
                    MIN(id) AS bipuserid,
                    MAX(firstname) AS bip_firstname,
                    MAX(middlename) AS bip_middlename,
                    MAX(lastname) AS bip_lastname,
                    MAX(email) AS bip_email,
                    MAX(dateofbirth) AS bip_dateofbirth
                FROM {local_eduvidual_bip_user}
                WHERE orgid = ?
                GROUP BY bpkbf
            ) bu ON bu.bpkbf=ash.idpusername
            WHERE u.deleted=0
        ", [$this->org->orgid, \local_eduvidual\locallib::ROLE_STUDENT, $this->org->orgid]);

        $cols = [
            // 'userpic' => get_string('pictureofuser'),
            'lastname' => get_string('lastname'),
            'firstname' => get_string('firstname'),
            'email' => get_string('email'),
            'role' => get_string('role'),
            'bip_lastname' => get_string('bip_matching:bip_lastname', 'local_eduvidual'),
            'bip_firstname' => get_string('bip_matching:bip_firstname', 'local_eduvidual'),
            'bip_email' => get_string('bip_matching:bip_email', 'local_eduvidual'),
            'bip_dateofbirth' => get_string('bip_matching:bip_dateofbirth', 'local_eduvidual'),
            'source' => get_string('bip_matching:source', 'local_eduvidual'),
        ];

        // Im Developer-Mode den bpkbf als Debug-Spalte anzeigen.
        if (!empty($CFG->developermode)) {
            $cols['bpkbf'] = 'DEV: bpkbf';
        }

        $this->set_table_columns($cols);
        $this->sortable(true, 'lastname', SORT_ASC);

        if (isset($cols['userpic'])) {
            $this->set_column_options('userpic', no_sorting: true, no_filter: true);
        }

        $this->is_downloadable(true, 'bip_matching_' . $this->org->orgid . '_' . date('Ymd-His'));

        // Custom-Confirm "Von der Schule entfernen: {firstname} {lastname}?" statt der generischen
        // Meldung. Platzhalter werden client-seitig in deleteRow aus row.original ersetzt.
        // lib_enrol::role_set mit ROLE_REMOVE räumt Enrolments, Cohorts und orgid_userid auf
        // (siehe delete_row() unten).
        $this->add_row_action(
            type: 'delete',
            id: 'delete',
            confirm_message: get_string('manage:userlist:remove:confirm', 'local_eduvidual'),
        );

        $table = $this;
        $this->add_form_action(new class($table) extends \local_table_sql\table_sql_subform {
            public function __construct(protected $parenttable) {
                parent::__construct();
            }

            function definition() {
                $mform = $this->_form;
                $mform->addElement('autocomplete', 'bipuserid', get_string('bip_matching:bipuserid', 'local_eduvidual'), [], [
                    'placeholder' => get_string('bip_matching:choose', 'local_eduvidual'),
                    'noselectionstring' => get_string('bip_matching:choose', 'local_eduvidual'),
                ]);
                $mform->setType('bipuserid', PARAM_INT);
            }

            function set_data($default_values) {
                $current = is_object($default_values) ? ($default_values->bipuserid ?? null) : ($default_values['bipuserid'] ?? null);
                $opts = $this->parenttable->bipuserid_options_for_row($current ? (int)$current : null);
                $this->_form->getElement('bipuserid')->loadArray($opts);
                parent::set_data($default_values);
            }
        });
    }

    /**
     * Disable den Delete-Button für Zeilen, in denen schon ein BIP-User verknüpft ist
     * (echter Match ODER orphan ash-Link). Nur wirklich "leere" Zeilen sind löschbar.
     */
    function get_row_actions(object $row): array {
        $row_actions = parent::get_row_actions($row);
        $row_actions['delete']->disabled = $row->bpkbf;
        return $row_actions;
    }

    /**
     * Entfernt den User aus dieser Org (kein hartes Löschen des Moodle-Accounts):
     * Kursenrollments + Rollenzuweisungen + Cohorts + local_eduvidual_orgid_userid werden
     * via lib_enrol::role_set($userid, $org, 'remove') aufgeräumt.
     */
    function delete_row(object $row) {
        \local_eduvidual\lib_enrol::role_set($row->id, $this->org, \local_eduvidual\locallib::ROLE_REMOVE);
    }

    function col_source($row) {
        if ($row->source === null || $row->bpkbf === null) {
            return '';
        }
        return match ((int)$row->source) {
            \auth_shibboleth_link\lib::SOURCE_USER => get_string('bip_matching:source_user', 'local_eduvidual'),
            \auth_shibboleth_link\lib::SOURCE_AUTOMATCH => get_string('bip_matching:source_automatch', 'local_eduvidual'),
            \auth_shibboleth_link\lib::SOURCE_MANAGER => get_string('bip_matching:source_manager', 'local_eduvidual'),
            default => (string)$row->source,
        };
    }

    function col_userpic($row) {
        global $OUTPUT;

        // up_-Prefix entfernen, damit user_picture() die Felder findet (id, picture, firstname, ...).
        $user = (object)[];
        foreach ((array)$row as $key => $value) {
            if (str_starts_with($key, 'up_')) {
                $user->{substr($key, 3)} = $value;
            }
        }
        return $OUTPUT->user_picture($user, ['size' => 50]);
    }

    /**
     * Speichert die Auswahl aus dem bipuserid-Dropdown: alten Link für diesen User
     * löschen, neuen anlegen (falls eine bip_user-ID gewählt wurde).
     */
    function store_row(object $data): void {
        global $DB, $USER;

        // Keine Auswahl -> alle Verknüpfungen dieses Moodle-Users entfernen.
        if (!$data->bipuserid) {
            $DB->delete_records('auth_shibboleth_link', ['userid' => $data->id]);
            return;
        }

        $bu = array_column($this->bipusers_for_org(), null, 'id')[$data->bipuserid] ?? null;
        if (!$bu) {
            return;
        }

        // Wenn bereits exakt dieser userid+bpkbf-Eintrag existiert, ist nichts zu tun.
        if ($DB->record_exists('auth_shibboleth_link', ['userid' => $data->id, 'idpusername' => $bu->bpkbf])) {
            return;
        }

        // Sonst: bisherige Links dieses Users und kollidierende Einträge für diesen bpkbf
        // (z.B. an einen anderen Moodle-User) löschen, dann neu anlegen.
        $DB->delete_records('auth_shibboleth_link', ['userid' => $data->id]);
        $DB->delete_records('auth_shibboleth_link', ['idpusername' => $bu->bpkbf]);

        $DB->insert_record('auth_shibboleth_link', (object)[
            'idp' => $this->defaultidp,
            'idpusername' => $bu->bpkbf,
            'idpfirstname' => $bu->firstname ?? '',
            'idplastname' => $bu->lastname ?? '',
            'idpemail' => $bu->email ?? '',
            'userid' => $data->id,
            'lastseen' => 0,
            'created' => time(),
            'source' => \auth_shibboleth_link\lib::SOURCE_MANAGER,
            'usermodified' => $USER->id,
        ]);
    }
};

echo $OUTPUT->header();

\local_eduvidual\output::print_manage_menu($orgid, 'users');
\local_eduvidual\output::print_manage_users_tabs($orgid, 'bipmatching');

$table->out();

echo $OUTPUT->footer();
