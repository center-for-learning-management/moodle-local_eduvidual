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

namespace local_eduvidual;

defined('MOODLE_INTERNAL') || die;

class bip_helper {
    public static function do_curl(string $url, ?array $headers = null, $postdata = null, string $basicauth = '', int $timeout = 60) {
        return static::do_curl_full($url, $headers, $postdata, $basicauth, $timeout)->result;
    }

    public static function do_curl_full(string $url, ?array $headers = null, $postdata = null, string $basicauth = '', int $timeout = 60): object {
        $curl = new \curl();
        $options = [
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_FOLLOWLOCATION' => 1,
            'CURLOPT_CONNECTTIMEOUT' => 4,
        ];
        if ($timeout) {
            $options['CURLOPT_TIMEOUT'] = $timeout;
        }
        if (!empty($basicauth)) {
            $options['CURLOPT_HTTPAUTH'] = CURLAUTH_BASIC;
            $options['CURLOPT_USERPWD'] = $basicauth;
        }

        if (!empty($headers)) {
            $strheaders = [];
            foreach ($headers as $key => $value) {
                $strheaders[] = "$key: $value";
            }
            $curl->setHeader($strheaders);
        }

        if ($postdata) {
            if (is_array($postdata) || is_object($postdata)) {
                $postdata = http_build_query($postdata, '', '&');
            }
            $result = $curl->post($url, $postdata, $options);
        } else {
            $result = $curl->get($url, [], $options);
        }

        $returncode = (int)($curl->get_info()['http_code'] ?? 0);
        if ($returncode != 200) {
            throw new \moodle_exception('bip:wronghttpcode', 'local_eduvidual', '', (object)['code' => $returncode, 'output' => $result]);
        }

        $jsondata = json_decode($result);
        if (!$jsondata) {
            throw new \moodle_exception('bip:jsondecodefailed', 'local_eduvidual');
        }
        if ($jsondata->type != 'success') {
            throw new \moodle_exception('bip:statusnotsuccess', 'local_eduvidual', '', $jsondata->type);
        }

        return $jsondata;
    }

    private static function build_bip_url(string $iface): string {
        $host = get_config('local_eduvidual', 'bip_host') ?: 'https://www.bildung.gv.at';
        return rtrim($host, '/') . '/local/eduportal/webservice/server.php/' . $iface;
    }

    private static function get_bip_headers(): array {
        $basicauth = get_config('local_eduvidual', 'bip_basicauth');
        if (!$basicauth) {
            throw new \moodle_exception('bip:missingbasicauth', 'local_eduvidual');
        }
        return ['Authorization' => 'Basic ' . base64_encode($basicauth)];
    }

    public static function call_bip_iface(string $iface, array $params = []): mixed {
        return static::do_curl(static::build_bip_url($iface), static::get_bip_headers(), $params ?: null);
    }

    /**
     * Holt genau eine Seite einer cursor-paginierten BIP-Schnittstelle (readuserdata_v3).
     * Liefert das vollständige Antwortobjekt (->result und ->meta), damit der Aufrufer
     * Pagination zwischen Cron-Läufen persistieren kann.
     */
    public static function call_bip_iface_one_page(string $iface, array $params = [], string $cursor = ''): object {
        if ($cursor !== '') {
            $params['cursor'] = $cursor;
        }
        return static::do_curl_full(static::build_bip_url($iface), static::get_bip_headers(), $params ?: null);
    }

    public static function read_org_data(array $orgids): array {
        return static::call_bip_iface('local_eduportal_iface_readorgdata', ['orgids' => join(',', $orgids)]);
    }

    /**
     * Prüft, ob die Schule aktuell in BIP existiert. Das biporg-Flag in local_eduvidual_org
     * wird von import_orgs() gepflegt - kein Live-Call zu BIP nötig.
     */
    public static function is_bip_org(int $orgid): bool {
        $org = \local_eduvidual\locallib::get_org('orgid', $orgid);
        return !empty($org->biporg);

        // ALT: nicht 6-stellige orgids (Projektgruppen, Ressourcenkatalog, Spielwiese) stammen nie aus BIP.
        /*
        if (strlen((string)$orgid) != 6) {
            return false;
        }
        */
    }

    /**
     * Matcht BIP-User mit Moodle-Usern über Org + Name + Rolle und legt fehlende
     * auth_shibboleth_link-Einträge an. Beschränkt auf registrierte Orgs (authenticated > 0)
     * und role=std/Student auf beiden Seiten.
     *
     * Matching-Regeln:
     * - Vorname: nur das erste Wort (alles nach dem ersten Leerzeichen wird ignoriert),
     *   case-insensitiv, getrimmt
     * - Nachname: case-insensitiv, getrimmt
     * - Bei mehreren Personen mit gleichem Namen + Rolle in derselben Org → kein
     *   automatisches Matching (für die ganze Gruppe übersprungen)
     * - Es wird nur gematcht, wenn weder der Moodle-User noch der bpkbf bereits einen
     *   auth_shibboleth_link-Eintrag haben
     */
    public static function match_users(bool $execute = false): void {
        global $DB;

        if (!$execute) {
            mtrace('*** DRY-RUN: keine Änderungen in DB, nur Ausgabe der geplanten Matches ***');
        }

        $idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
        $defaultidp = $idps ? trim(explode(',', $idps[0])[0]) : '';

        // MANUAL/LOGIN-Links sperren bpkbf/userid endgültig - die fasst der AutoMatcher nicht an.
        // AUTOMATCH-Links werden neu evaluiert: am Ende per Diff (siehe unten) nur tatsächlich
        // geänderte Zeilen geschrieben.
        $lockedbpkbfs = array_flip($DB->get_fieldset_select('auth_shibboleth_link', 'idpusername', 'source <> ?', [\auth_shibboleth_link\lib::SOURCE_AUTOMATCH]));
        $lockeduserids = array_flip($DB->get_fieldset_select('auth_shibboleth_link', 'userid', 'source <> ?', [\auth_shibboleth_link\lib::SOURCE_AUTOMATCH]));

        // Bestehende AUTOMATCH-Links als (userid|bpkbf) -> record.
        $existing = [];
        foreach ($DB->get_records('auth_shibboleth_link', ['source' => \auth_shibboleth_link\lib::SOURCE_AUTOMATCH]) as $rec) {
            $existing["{$rec->userid}|{$rec->idpusername}"] = $rec;
        }

        // ALLE BIP-User indexieren - der Match-Key enthält die Rolle, daher kollidieren
        // std/tch/dir/lgn nie miteinander. Auch bereits verlinkte sind drin, damit
        // Namensgleichheit als mehrdeutig erkannt wird.
        $bipindex = [];
        $totalbipusers = 0;
        foreach ($DB->get_records('local_eduvidual_bip_user') as $bu) {
            $totalbipusers++;
            $key = static::make_match_key($bu->orgid, $bu->role, $bu->firstname, $bu->lastname);
            if (!$key) {
                continue;
            }
            $bipindex[$key][] = $bu;
        }

        // Vorerst nur Moodle-Student-User; weitere Rollen später.
        $candidates = $DB->get_records_sql("
            SELECT ou.id AS ouid, u.id AS userid, u.firstname, u.lastname, ou.orgid, ou.role
            FROM {user} u
            JOIN {local_eduvidual_orgid_userid} ou ON ou.userid=u.id AND ou.role=?
            JOIN {local_eduvidual_org} org ON org.orgid=ou.orgid AND org.authenticated>0
            WHERE u.deleted=0
        ", [\local_eduvidual\locallib::ROLE_STUDENT]);

        $moodleindex = [];
        foreach ($candidates as $c) {
            $biprole = static::moodle_role_to_bip_role($c->role);
            if (!$biprole) {
                continue;
            }
            $key = static::make_match_key($c->orgid, $biprole, $c->firstname, $c->lastname);
            if (!$key) {
                continue;
            }
            $moodleindex[$key][] = $c;
        }

        // Soll-Zustand berechnen: gleiche Logik wie früher, aber Ergebnis landet in $desired
        // statt direkt in der DB. AUTOMATCH-Lock zählt nicht - die Links sind ja re-evaluierbar.
        // Erst pro Key die eindeutigen Treffer sammeln, danach über den GANZEN Lauf prüfen,
        // dass jeder bpkbf und jeder userid nur ein einziges Mal vorkommt.
        // Jeder Moodle-Key fällt in genau einen Bucket - die Summe ergibt count($moodleindex).
        $matched = [];           // Liste von [c, bu] - ein eindeutiger Treffer pro Key
        $ambiguousmoodle = 0;    // >1 Moodle-User mit gleichem Namen in Org+Rolle
        $ambiguousbip = 0;       // Moodle-User passt auf >1 BIP-User
        $nobipmatch = 0;         // kein BIP-User mit gleichem Key
        $skipped_locked = 0;     // bpkbf oder userid via MANUAL/LOGIN gesperrt

        foreach ($moodleindex as $key => $matchedusers) {
            if (count($matchedusers) > 1) {
                $ambiguousmoodle++;
                $c = $matchedusers[0];
                $list = join(', ', array_map(fn($u) => "{$u->firstname} {$u->lastname} (userid={$u->userid})", $matchedusers));
                mtrace("MEHRDEUTIG, kein Match: in Org {$c->orgid} (Rolle {$c->role}) gibt es mehrere Moodle-User mit gleichem Namen: {$list}");
                continue;
            }
            if (empty($bipindex[$key])) {
                $nobipmatch++;
                continue;
            }
            if (count($bipindex[$key]) > 1) {
                $ambiguousbip++;
                $c = $matchedusers[0];
                $list = join(', ', array_map(fn($b) => "{$b->firstname} {$b->lastname} (bpkbf={$b->bpkbf})", $bipindex[$key]));
                mtrace("MEHRDEUTIG, kein Match: Moodle-User {$c->firstname} {$c->lastname} (userid={$c->userid}) in Org {$c->orgid} (Rolle {$c->role}) passt auf mehrere BIP-User: {$list}");
                continue;
            }
            $c = $matchedusers[0];
            $bu = $bipindex[$key][0];

            if (isset($lockeduserids[$c->userid]) || isset($lockedbpkbfs[$bu->bpkbf])) {
                $skipped_locked++;
                continue;
            }
            $matched[] = [$c, $bu];
        }

        // Kollisionen über den ganzen Lauf zählen: derselbe bpkbf kann über mehrere Orgs
        // auf mehrere gleichnamige Moodle-User passen (und umgekehrt). In dem Fall ist NICHT
        // eindeutig, welcher User gemeint ist - also gar nicht verlinken statt den ersten zu nehmen.
        $bpkbfcount = [];
        $useridcount = [];
        foreach ($matched as [$c, $bu]) {
            $bpkbfcount[$bu->bpkbf] = ($bpkbfcount[$bu->bpkbf] ?? 0) + 1;
            $useridcount[$c->userid] = ($useridcount[$c->userid] ?? 0) + 1;
        }

        $crosscollision = 0;     // bpkbf bzw. userid über mehrere Orgs nicht eindeutig
        $desired = [];           // "userid|bpkbf" => [c, bu]
        foreach ($matched as [$c, $bu]) {
            if ($bpkbfcount[$bu->bpkbf] > 1) {
                $crosscollision++;
                mtrace("MEHRDEUTIG, kein Match: BIP-User (bpkbf={$bu->bpkbf}) passt auf mehrere gleichnamige Moodle-User in verschiedenen Orgs - u.a. {$c->firstname} {$c->lastname} (userid={$c->userid}, Org {$c->orgid})");
                continue;
            }
            if ($useridcount[$c->userid] > 1) {
                $crosscollision++;
                mtrace("MEHRDEUTIG, kein Match: Moodle-User {$c->firstname} {$c->lastname} (userid={$c->userid}) passt über mehrere Orgs auf mehrere BIP-User");
                continue;
            }
            $desired["{$c->userid}|{$bu->bpkbf}"] = [$c, $bu];
        }

        // Diff anwenden: nur tatsächliche Änderungen schreiben.
        $now = time();
        $countinsert = 0;
        $countdelete = 0;
        $countkeep = 0;

        foreach ($existing as $compositekey => $rec) {
            if (isset($desired[$compositekey])) {
                $countkeep++;
                continue;
            }
            static::mtrace("AUTOMATCH entfernt (Match nicht mehr eindeutig): userid={$rec->userid} <-> bpkbf={$rec->idpusername}", execute: $execute);
            if ($execute) {
                $DB->delete_records('auth_shibboleth_link', ['id' => $rec->id]);
            }
            $countdelete++;
        }

        foreach ($desired as $compositekey => [$c, $bu]) {
            if (isset($existing[$compositekey])) {
                continue;
            }
            static::mtrace("AUTOMATCH neu: Moodle {$c->firstname} {$c->lastname} (userid={$c->userid}, Org {$c->orgid}) <-> BIP {$bu->firstname} {$bu->lastname} (bpkbf={$bu->bpkbf})", execute: $execute);
            if ($execute) {
                $DB->insert_record('auth_shibboleth_link', (object)[
                    'idp' => $defaultidp,
                    'idpusername' => $bu->bpkbf,
                    'idpfirstname' => $bu->firstname ?? '',
                    'idplastname' => $bu->lastname ?? '',
                    'idpemail' => $bu->email ?? '',
                    'userid' => $c->userid,
                    'lastseen' => 0,
                    'created' => $now,
                    'source' => \auth_shibboleth_link\lib::SOURCE_AUTOMATCH,
                    'usermodified' => 0,
                ]);
            }
            $countinsert++;
        }

        $matchedtotal = count($desired);
        $totalcandidates = count($candidates);
        $totalkeys = count($moodleindex);
        static::mtrace("BIP-User-Match fertig:", execute: $execute);
        static::mtrace("  Eingang: {$totalbipusers} BIP-User, {$totalcandidates} Moodle-Kandidaten (Studierende in registrierten Orgs) -> {$totalkeys} eindeutige Match-Keys (orgid|rolle|vorname|nachname)", execute: $execute);
        static::mtrace("  Eindeutig gematcht (gesamt): {$matchedtotal} - davon {$countinsert} neu verlinkt, {$countkeep} unverändert", execute: $execute);
        static::mtrace("  Verlinkung entfernt (nicht mehr eindeutig): {$countdelete}", execute: $execute);
        static::mtrace("  Kein BIP-Gegenstück mit gleichem Key: {$nobipmatch}", execute: $execute);
        static::mtrace("  Mehrdeutig - mehrere Moodle-User gleichen Namens: {$ambiguousmoodle}", execute: $execute);
        static::mtrace("  Mehrdeutig - Moodle-User passt auf mehrere BIP-User: {$ambiguousbip}", execute: $execute);
        static::mtrace("  Mehrdeutig - bpkbf/userid über mehrere Orgs nicht eindeutig: {$crosscollision}", execute: $execute);
        static::mtrace("  Gesperrt (MANUAL/LOGIN, nicht angefasst): {$skipped_locked}", execute: $execute);
    }

    /**
     * Match-Key aus (orgid, BIP-Rollencode, erstes Vornamen-Wort, Nachname). Liefert null,
     * wenn nicht genügend Daten für einen sinnvollen Vergleich vorhanden sind.
     */
    private static function make_match_key($orgid, ?string $role, ?string $firstname, ?string $lastname): ?string {
        if (!$role || !$firstname || !$lastname) {
            return null;
        }
        $first = preg_replace('!\s.*!', '', trim($firstname));
        $last = trim($lastname);
        if ($first === '' || $last === '') {
            return null;
        }
        return $orgid . '|' . $role . '|' . mb_strtolower($first) . '|' . mb_strtolower($last);
    }

    /**
     * Mappt eine Moodle-eduvidual-Rolle auf den BIP-Rollencode.
     * Liefert null, wenn keine Entsprechung definiert ist.
     */
    private static function moodle_role_to_bip_role(?string $moodlerole): ?string {
        return match ($moodlerole) {
            \local_eduvidual\locallib::ROLE_STUDENT => 'std',
            \local_eduvidual\locallib::ROLE_TEACHER => 'tch',
            \local_eduvidual\locallib::ROLE_MANAGER => 'dir',
            \local_eduvidual\locallib::ROLE_PARENT => 'lgn',
            default => null,
        };
    }

    /**
     * Mappt einen BIP-Rollencode auf die eduvidual-Rolle (Gegenrichtung zu
     * moodle_role_to_bip_role). Liefert null für unbekannte Rollen.
     */
    public static function bip_role_to_moodle_role(string $biprole): ?string {
        return match ($biprole) {
            'adm', 'alr', 'dir', 'emp', 'tch', 'sqm', 'sqb' => \local_eduvidual\locallib::ROLE_TEACHER,
            'lgn' => \local_eduvidual\locallib::ROLE_PARENT,
            'std' => \local_eduvidual\locallib::ROLE_STUDENT,
            default => null,
        };
    }

    /**
     * Ordnet den User der Org zu - mit der höchsten Rolle aus bestehenden + neuen Rollen.
     * Gemeinsame Regeln für Login-Hook und BIP-Import: nur registrierte Orgs
     * (authenticated > 0), bestehende Manager bleiben unangetastet.
     *
     * Liefert die gesetzte Rolle bzw. null, wenn nichts zu tun ist. Mit $execute=false
     * wird nur gerechnet, nicht geschrieben.
     */
    public static function assign_user_org(int $userid, int $orgid, array $newroles, bool $execute = true): ?string {
        global $DB;

        $org = \local_eduvidual\locallib::get_org('orgid', $orgid);
        if (empty($org->orgid) || !$org->authenticated) {
            // Schule nicht gefunden oder nicht registriert.
            return null;
        }

        $currentroles = $DB->get_fieldset_select('local_eduvidual_orgid_userid', 'role', 'userid = ? AND orgid = ?', [$userid, $orgid]);
        if (\local_eduvidual\locallib::find_highest_role($currentroles) == \local_eduvidual\locallib::ROLE_MANAGER) {
            // Manager bleiben Manager.
            return null;
        }

        $highest = \local_eduvidual\locallib::find_highest_role(array_merge($currentroles, $newroles));
        if (!$highest) {
            return null;
        }

        static::mtrace("role_set: userid={$userid}, orgid={$orgid}, role={$highest}", execute: $execute);
        if ($execute) {
            \local_eduvidual\lib_enrol::role_set($userid, $org, $highest);
        }

        return $highest;
    }

    /**
     * Trägt den User aus der Org aus - aber nur, wenn die Schule eine BIP-Schule ist.
     * Mitgliedschaften bei Nicht-BIP-Orgs (Projektgruppen, Ressourcenkatalog, Spielwiese, ...)
     * bleiben unangetastet, weil BIP sie nie bestätigen kann.
     *
     * Liefert true, wenn ausgetragen wurde (bzw. bei $execute=false: würde).
     */
    public static function unassign_user_org(int $userid, int $orgid, bool $execute = true): bool {
        if (!static::is_bip_org($orgid)) {
            return false;
        }

        static::mtrace("role_remove:: userid={$userid} -> orgid={$orgid}", execute: $execute);
        if ($execute) {
            $org = \local_eduvidual\locallib::get_org('orgid', $orgid);
            \local_eduvidual\lib_enrol::role_set($userid, $org, 'remove');
        }

        return true;
    }

    /**
     * Voll-Abgleich der Schulzuordnungen eines Users gegen die gewünschte Liste aus BIP
     * (orgid => [BIP-Rollencodes]): trägt bei allen gewünschten Orgs ein und aus allen
     * anderen aus. Unbekannte Rollencodes und nicht registrierte Orgs werden ignoriert -
     * aus solchen Orgs wird also auch ausgetragen. Die weiteren Regeln stecken in den
     * Primitiven (assign_user_org/unassign_user_org): Manager bleiben Manager, ausgetragen
     * wird nur aus BIP-Orgs.
     *
     * Nur für Quellen verwenden, die ALLE Zugehörigkeiten des Users über alle Rollen
     * abbilden (Login-affiliation, readuserdata mit allen usertypes) - sonst würden
     * Mitgliedschaften gelöscht, die die Quelle gar nicht sehen kann.
     */
    public static function sync_user_orgs(int $userid, array $wantedbiproles, bool $execute = true): void {
        global $DB;

        $wantedroles = [];
        foreach ($wantedbiproles as $orgid => $biproles) {
            $roles = array_filter(array_map(fn($r) => static::bip_role_to_moodle_role((string)$r), (array)$biproles));
            if (!$roles) {
                continue;
            }

            $org = \local_eduvidual\locallib::get_org('orgid', (int)$orgid);
            if (empty($org->orgid) || !$org->authenticated) {
                // schule nicht gefunden oder nicht registriert
                continue;
            }

            $wantedroles[(int)$orgid] = array_values(array_unique($roles));
        }

        $myorgids = $DB->get_fieldset_select('local_eduvidual_orgid_userid', 'DISTINCT orgid', 'userid = ?', [$userid]);
        foreach ($myorgids as $myorgid) {
            if (!isset($wantedroles[(int)$myorgid])) {
                static::unassign_user_org($userid, (int)$myorgid, $execute);
            }
        }

        foreach ($wantedroles as $orgid => $roles) {
            static::assign_user_org($userid, $orgid, $roles, $execute);
        }
    }

    /**
     * Importiert Schulen aus BIP in local_eduvidual_org.
     * - Neue Schulen: vollständig anlegen (authenticated bleibt 0 = noch nicht registriert).
     *   Nicht-echte Schulen (genuine=0) werden hier übersprungen.
     * - Nicht registrierte Schulen (authenticated = 0): alle Felder updaten inkl. Name.
     * - Registrierte Schulen (authenticated > 0, TAN bestätigt): nur Adresse, Telefon und
     *   offizielle Mailadresse updaten. Der Schulname wird nicht überschrieben.
     * - Setzt zusätzlich biporg/bipgenuine/bipoperational: biporg=1 markiert, dass die Schule
     *   aktuell in BIP existiert (Basis für is_bip_org()); Orgs, die nicht mehr in der
     *   BIP-Antwort vorkommen, werden auf biporg=0 zurückgesetzt.
     *
     * Achtung: org->name wird bei der Registrierung in course_categories (categoryid),
     * course.summary (courseid, "Digitaler Schulhof") und course.fullname (supportcourseid,
     * "Helpdesk") kopiert - eine spätere Namens-Synchronisierung müsste diese Stellen mitziehen.
     */
    public static function import_orgs(bool $execute = false): void {
        global $DB;

        if (!$execute) {
            mtrace('*** DRY-RUN: keine Änderungen in DB, nur Ausgabe der geplanten Aktionen ***');
        }

        $bipschulen = static::call_bip_iface('local_eduportal_iface_readorgdata');
        if (!is_array($bipschulen)) {
            throw new \moodle_exception('bip:schulennotarray', 'local_eduvidual', '', gettype($bipschulen));
        }

        $existingorgs = array_column($DB->get_records('local_eduvidual_org'), null, 'orgid');
        $countinsert = 0;
        $countupdate = 0;
        $countunflag = 0;
        $seenorgids = [];

        foreach ($bipschulen as $bipSchule) {
            $bipSchule = (object)array_map(fn($v) => is_string($v) ? trim($v) : $v, (array)$bipSchule);
            if (!$bipSchule->orgid) {
                continue;
            }
            $seenorgids[] = (int)$bipSchule->orgid;
            if (!trim($bipSchule->phone ?? '', '0/ ')) {
                $bipSchule->phone = '';
            }
            if (empty($bipSchule->officialname)) {
                continue;
            }

            if (!isset($existingorgs[$bipSchule->orgid])) {
                // Nicht-echte Schulen werden nicht neu angelegt, bestehende Datensätze
                // dürfen aber weiter upgedated werden (z.B. Adresse).
                if (empty($bipSchule->genuine)) {
                    static::mtrace("Schule {$bipSchule->orgid} übersprungen: nicht echt (genuine=0) und nicht vorhanden", execute: $execute);
                    continue;
                }
                // Neue Schule anlegen - noch nicht vollständig registriert (categoryid bleibt 0).
                $new = (object)[
                    'orgid' => (int)$bipSchule->orgid,
                    'name' => mb_substr($bipSchule->officialname, 0, 250),
                    'mail' => $bipSchule->email ?? '',
                    'phone' => $bipSchule->phone ?? '',
                    'street' => $bipSchule->street ?? '',
                    'zip' => $bipSchule->zip ?? '',
                    'city' => $bipSchule->city ?? '',
                    'customcss' => '',
                    'banner' => '',
                    // subcats1/2/3 sind in der Tabelle NOT NULL ohne Default - analog zu
                    // lib_register::create_neworg() leer initialisieren.
                    'subcats1' => get_string('createcourse:subcat1:defaults', 'local_eduvidual'),
                    'subcats2' => '',
                    'subcats3' => '',
                    'biporg' => 1,
                    'bipgenuine' => (int)!empty($bipSchule->genuine),
                    'bipoperational' => (int)!empty($bipSchule->operational),
                ];
                static::mtrace("insert org: {$new->orgid} - {$new->name}", execute: $execute);
                if ($execute) {
                    $DB->insert_record('local_eduvidual_org', $new);
                }
                $countinsert++;
                continue;
            }

            $existing = $existingorgs[$bipSchule->orgid];
            $isregistered = !empty($existing->authenticated);

            $data = [];
            if (!empty($bipSchule->email)) {
                $data['mail'] = $bipSchule->email;
            }
            if (!empty($bipSchule->phone)) {
                $data['phone'] = $bipSchule->phone;
            }
            if (!empty($bipSchule->street)) {
                $data['street'] = $bipSchule->street;
            }
            if (!empty($bipSchule->zip)) {
                $data['zip'] = $bipSchule->zip;
            }
            if (!empty($bipSchule->city)) {
                $data['city'] = $bipSchule->city;
            }
            // Name nur bei noch nicht registrierten Schulen aktualisieren.
            if (!$isregistered && !empty($bipSchule->officialname)) {
                $data['name'] = mb_substr($bipSchule->officialname, 0, 250);
            }

            // BIP-Flags immer mitführen - der Diff unten schreibt nur bei Änderung.
            $data['biporg'] = 1;
            $data['bipgenuine'] = (int)!empty($bipSchule->genuine);
            $data['bipoperational'] = (int)!empty($bipSchule->operational);

            $alt = (array)$existing;
            $diff = [];
            foreach ($data as $key => $value) {
                $altvalue = $alt[$key] ?? null;
                if (is_string($altvalue)) {
                    $altvalue = mb_substr($altvalue, 0, 250);
                }
                if (is_string($value)) {
                    $value = mb_substr($value, 0, 250);
                }
                if ((string)$altvalue !== (string)$value) {
                    $diff[$key] = ['old' => $altvalue, 'new' => $value];
                }
            }

            if ($diff) {
                static::mtrace("update org: {$bipSchule->orgid} - " . json_encode($diff), execute: $execute);
                $updatedata = (object)$data;
                $updatedata->id = $existing->id;
                if ($execute) {
                    $DB->update_record('local_eduvidual_org', $updatedata);
                }
                $countupdate++;
            }
        }

        // Orgs, die noch als BIP-Schule markiert sind, aber nicht mehr in der BIP-Antwort
        // vorkommen: biporg=0 setzen, damit is_bip_org() sie nicht mehr als BIP-Schule wertet.
        if ($seenorgids) {
            [$insql, $inparams] = $DB->get_in_or_equal($seenorgids, SQL_PARAMS_QM, 'param', false);
            $gone = $DB->get_records_select('local_eduvidual_org', "biporg = 1 AND orgid {$insql}", $inparams, '', 'id, orgid, name');
            foreach ($gone as $g) {
                static::mtrace("Schule nicht mehr in BIP, setze biporg=0: {$g->orgid} - {$g->name}", execute: $execute);
                if ($execute) {
                    $DB->update_record('local_eduvidual_org', (object)[
                        'id' => $g->id,
                        'biporg' => 0,
                        'bipgenuine' => 0,
                        'bipoperational' => 0,
                    ]);
                }
                $countunflag++;
            }
        }

        static::mtrace(count($bipschulen) . ' Schulen aus BIP gelesen, ' . $countinsert . ' neu angelegt, ' . $countupdate . ' aktualisiert, ' . $countunflag . ' nicht mehr in BIP', execute: $execute);
    }

    /**
     * Importiert BIP-Userdaten in die Tabelle local_eduvidual_bip_user. Geladen und
     * gespiegelt werden alle usertypes (eine Zeile pro bpkbf/orgid/role), damit der
     * Org-Sync (sync_user_orgs) pro User das vollständige Userobjekt über alle Rollen
     * sieht. Pro Aufruf eine Seite via readuserdata_v3.
     *
     * BIPs next_cursor wird in bip_userimport_cursor persistiert; der nächste Cron-Lauf
     * macht von dort aus weiter. Auf der letzten Sweep-Seite liefert BIP has_more=false
     * mit gültigem next_cursor; erst ein Folge-Request darauf liefert next_cursor=''.
     * In diesem Fall behalten wir den bisherigen Cursor, damit der nächste Lauf wieder
     * als Delta von dort fortsetzt. Löschungen kommen alle inline aus der BIP-Antwort
     * (deleted=1 oder leere orgs-Liste). Ein separater Stale-Cleanup ist nicht nötig,
     * weil BIP abgemeldete User mit reduziertem orgs und gesetztem unassigned_orgs im
     * Delta mitliefert. Manueller Voll-Resync bei Bedarf: bip_userimport_cursor leeren.
     *
     * Nur Orgs mit authenticated>0 werden abgefragt.
     */
    public static function import_users(bool $execute = false): void {
        global $DB;

        if (!$execute) {
            mtrace('*** DRY-RUN: keine Änderungen in DB, nur Ausgabe der geplanten Aktionen ***');
        }

        // Nur vollständig registrierte (TAN-bestätigte) Orgs.
        // Konsistent mit locallib::get_organisations(), das ebenfalls authenticated>0 als
        // Filter für "registriert" verwendet. Reine BIP-Mirror-Einträge sind ausgeschlossen.
        $orgids = $DB->get_fieldset_select('local_eduvidual_org', 'orgid', 'authenticated > 0', [], 'orgid');
        if (!$orgids) {
            mtrace('keine registrierten Orgs - nichts zu tun');
            return;
        }

        // Default-IdP, unter dem die BIP-Links angelegt werden (wie in match_users) - Teil
        // des eindeutigen Schlüssels (idp, idpusername) in auth_shibboleth_link.
        $idps = explode("\n", get_config('auth_shibboleth', 'organization_selection'));
        $defaultidp = $idps ? trim(explode(',', $idps[0])[0]) : '';

        $cursor = (string)(get_config('local_eduvidual', 'bip_userimport_cursor') ?: '');
        mtrace("BIP-User-Import: starte mit cursor='{$cursor}'");
        mtrace('Namen-Update und Org-Sync der verknüpften Moodle-User: vorerst deaktiviert');

        $now = time();
        $countinsert = 0;
        $countupdate = 0;
        $countdelete = 0;
        $countuseredit = 0;

        $jsondata = static::call_bip_iface_one_page('local_eduportal_iface_readuserdata_v3', [
            'orgids' => join(',', $orgids),
            // Leer = alle für den Zugang freigeschalteten usertypes (std, tch, lgn, dir, ...).
            // 'usertypes' => '',
            'limitnum' => 25000,
        ], $cursor);

        foreach ($jsondata->result as $bipuser) {
            // Ohne bpkbf ist der User nicht eindeutig identifizierbar - überspringen.
            // Sonst würden mehrere leere bpkbf-User unter bpkbf='' kollidieren und sich
            // gegenseitig beim Re-Insert wieder löschen.
            if (empty($bipuser->bpkbf)) {
                $orgsdebug = join(',', array_map(fn($o) => ($o->orgid ?? '?'), $bipuser->orgs ?? []));
                static::mtrace("übersprungen: User ohne bpkbf (firstname='" . ($bipuser->firstname ?? '') . "', lastname='" . ($bipuser->lastname ?? '') . "', orgs=[{$orgsdebug}])", execute: $execute);
                continue;
            }

            // Entscheiden, was mit diesem User passiert:
            // - deleted=1: in BIP gelöscht → lokal komplett löschen, kein Re-Insert
            // - orgs leer/fehlt: User hat keine aktive Org mehr → lokal komplett löschen, kein Re-Insert
            // - sonst: vorhandene Einträge pro (orgid, role) updaten, fehlende anlegen, abgehängte
            //   (z. B. via unassigned_orgs entfernte oder zu nicht getrackten Orgs gewechselte) löschen.
            //   IDs der bestehenden Zeilen bleiben damit stabil - die Matching-Seite serialisiert
            //   bip_user.id in ihrem Dropdown.
            $purgereason = null;
            if (!empty($bipuser->deleted)) {
                $purgereason = 'BIP: deleted=1';
            } elseif (empty($bipuser->orgs)) {
                $purgereason = 'BIP: orgs leer';
            }

            // Verknüpften Moodle-User (via auth_shibboleth_link) mit BIP-Stammdaten aktualisieren
            // und Schulzuordnungen per Voll-Abgleich syncen: BIP liefert im Delta immer das
            // komplette Userobjekt mit allen aktuellen Orgs über alle usertypes - "Org fehlt in
            // orgs" ist damit ein verlässliches Lösch-Signal, unassigned_orgs muss nicht separat
            // ausgewertet werden. Bei Purge trägt die leere orgs-Liste aus allen BIP-Orgs aus.
            // Eindeutig über (idp, idpusername) - ohne idp würden Zeilen anderer IdPs mit
            // gleicher bpkbf mitmatchen (get_field wirft sonst "duplicate value").
            $userid = $DB->get_field('auth_shibboleth_link', 'userid', ['idp' => $defaultidp, 'idpusername' => $bipuser->bpkbf]);
            if ($userid) {
                // Namen-Update vorerst nur im Dry-Run ("false &&").
                if ($purgereason === null && static::update_linked_moodle_user($bipuser, (int)$userid, false && $execute)) {
                    $countuseredit++;
                }

                $hasstd = false;
                $wantedbiproles = [];
                foreach (($bipuser->orgs ?? []) as $org) {
                    if (empty($org->orgid)) {
                        continue;
                    }
                    $roles = (array)($org->roles ?? []);
                    $wantedbiproles[(int)$org->orgid] = $roles;
                    $hasstd = $hasstd || in_array('std', $roles);
                }
                foreach ((array)($bipuser->unassigned_orgs ?? []) as $org) {
                    $hasstd = $hasstd || ($org->role ?? '') == 'std';
                }

                // Vorerst nur User syncen, die laut BIP Schüler:in sind oder gerade waren -
                // reine Lehrer-/Eltern-User bleiben dem Login-Hook überlassen. Und nur im
                // Dry-Run ("false &&"), bis sich der Sync in den Cron-Logs bewährt hat.
                if ($hasstd) {
                    static::sync_user_orgs((int)$userid, $wantedbiproles, false && $execute);
                }
            }

            $existingforuser = $DB->get_records('local_eduvidual_bip_user', ['bpkbf' => $bipuser->bpkbf]);
            $existingkeyed = [];
            foreach ($existingforuser as $r) {
                $existingkeyed["{$r->orgid}|{$r->role}"] = $r;
            }

            if ($purgereason !== null) {
                if ($existingforuser) {
                    $details = join(', ', array_map(fn($r) => "{$r->orgid}/{$r->role}", $existingforuser));
                    static::mtrace("löscht " . count($existingforuser) . " Einträge für bpkbf={$bipuser->bpkbf} ({$purgereason}): {$details}", execute: $execute);
                    if ($execute) {
                        $DB->delete_records('local_eduvidual_bip_user', ['bpkbf' => $bipuser->bpkbf]);
                    }
                    $countdelete += count($existingforuser);
                }
                continue;
            }

            // Pflichtfelder für jeden User mit aktiven Orgs prüfen.
            // Test auf Key-Existenz - bei fehlendem Key Exception. Leere Werte sind erlaubt;
            // sie werden im DB-Datensatz übernommen.
            foreach (['firstname', 'middlename', 'lastname', 'dateofbirth', 'emails'] as $field) {
                if (!property_exists($bipuser, $field)) {
                    mtrace("FEHLER: Pflichtfeld '{$field}' fehlt - vollständiger BIP-User:");
                    mtrace(var_export($bipuser, true));
                    throw new \moodle_exception('bip:userfieldmissing', 'local_eduvidual', '', (object)[
                        'field' => $field,
                        'bpkbf' => $bipuser->bpkbf ?? 'unknown',
                    ]);
                }
            }

            foreach ($bipuser->orgs as $org) {
                if (empty($org->orgid)) {
                    continue;
                }
                if (empty($org->roles)) {
                    continue;
                }
                $orgid = $org->orgid;
                if (!in_array($orgid, $orgids)) {
                    continue;
                }

                // offizielle Mail bevorzugt für die aktuelle Schule, sonst irgendeine offizielle.
                $email = null;
                $emailfallback = null;
                foreach (($bipuser->emails ?? []) as $entry) {
                    if (empty($entry->official) || empty($entry->value)) {
                        continue;
                    }
                    if (isset($entry->orgid) && $entry->orgid == $orgid) {
                        $email = $entry->value;
                        break;
                    }
                    if ($emailfallback === null) {
                        $emailfallback = $entry->value;
                    }
                }
                if ($email === null) {
                    $email = $emailfallback;
                }

                // Pro Rolle des Users in dieser Org einen eigenen Datensatz - Unique-Key ist (bpkbf, orgid, role).
                foreach ((array)$org->roles as $role) {
                    $role = (string)$role;

                    $key = "{$orgid}|{$role}";
                    $data = (object)[
                        'bpkbf' => $bipuser->bpkbf,
                        'orgid' => $orgid,
                        'role' => $role,
                        'firstname' => $bipuser->firstname ?? null,
                        'middlename' => $bipuser->middlename ?? null,
                        'lastname' => $bipuser->lastname ?? null,
                        'email' => $email,
                        'dateofbirth' => $bipuser->dateofbirth ?? null,
                        'timechanged' => (int)($bipuser->timechanged ?? 0),
                        'timeimported' => $now,
                    ];

                    if (isset($existingkeyed[$key])) {
                        $data->id = $existingkeyed[$key]->id;
                        if ($execute) {
                            $DB->update_record('local_eduvidual_bip_user', $data);
                        }
                        unset($existingkeyed[$key]);
                        $countupdate++;
                    } else {
                        if ($execute) {
                            $DB->insert_record('local_eduvidual_bip_user', $data);
                        }
                        $countinsert++;
                    }
                }
            }

            // Was in $existingkeyed übrig bleibt, war nicht mehr in $bipuser->orgs (oder die Org
            // ist nicht mehr getrackt) - jetzt entfernen.
            foreach ($existingkeyed as $key => $r) {
                static::mtrace("löscht abgehängten Eintrag für bpkbf={$bipuser->bpkbf}: orgid={$r->orgid}, role={$r->role}", execute: $execute);
                if ($execute) {
                    $DB->delete_records('local_eduvidual_bip_user', ['id' => $r->id]);
                }
                $countdelete++;
            }

        }

        $hasmore = !empty($jsondata->meta->has_more);
        $nextcursor = (string)($jsondata->meta->next_cursor ?? '');

        // BIP liefert auf der letzten Seite des Sweeps noch einen gültigen Cursor mit
        // (has_more=false, next_cursor=lastToken). Erst ein Folge-Request mit diesem Token
        // antwortet mit next_cursor=''. In dem Fall nicht überschreiben, damit der nächste
        // Lauf wieder als Delta vom bisherigen Cursor fortsetzt.
        if ($execute && $nextcursor) {
            set_config('bip_userimport_cursor', $nextcursor, 'local_eduvidual');
        }

        $status = $hasmore ? 'weiter beim nächsten Lauf' : 'Sweep abgeschlossen';
        $cursorinfo = "next_cursor='{$nextcursor}'" . ($nextcursor ? '' : " (keep old cursor)");
        static::mtrace("BIP-User-Import ({$status}): {$countinsert} angelegt, {$countupdate} aktualisiert, {$countdelete} gelöscht, {$countuseredit} Moodle-User aktualisiert, {$cursorinfo}", execute: $execute);
    }

    /**
     * Aktualisiert firstname/middlename/lastname im mdl_user-Datensatz des per
     * auth_shibboleth_link mit diesem bpkbf verknüpften Moodle-Users.
     *
     * Rückgabe: true wenn tatsächlich ein Feld geändert wurde (für Statistik).
     */
    private static function update_linked_moodle_user(object $bipuser, int $userid, bool $execute): bool {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            return false;
        }

        $update = [];
        $changes = [];
        foreach (['firstname', 'middlename', 'lastname'] as $field) {
            $newval = $bipuser->{$field} ?? '';
            if ($newval !== '' && $user->{$field} !== $newval) {
                $update[$field] = $newval;
                $changes[$field] = ['old' => $user->{$field}, 'new' => $newval];
            }
        }

        if (!$update) {
            return false;
        }

        static::mtrace("update Moodle-User userid={$user->id} (bpkbf={$bipuser->bpkbf}): " . json_encode($changes, JSON_UNESCAPED_UNICODE), execute: $execute);
        if ($execute) {
            $update['id'] = $user->id;
            $DB->update_record('user', (object)$update);
        }

        return true;
    }

    // $execute is intentionally not a static variable, because each function has its own execute on/off.
    private static function mtrace(string $message, bool $execute = true): void {
        \local_eduvidual\output::mtrace((!$execute ? 'DRY RUN: ' : '') . $message);
    }
}
