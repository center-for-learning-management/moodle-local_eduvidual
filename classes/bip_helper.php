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

    private static function buildBipUrl(string $iface): string {
        $host = get_config('local_eduvidual', 'bip_host') ?: 'https://www.bildung.gv.at';
        return rtrim($host, '/') . '/local/eduportal/webservice/server.php/' . $iface;
    }

    private static function getBipHeaders(): array {
        $basicauth = get_config('local_eduvidual', 'bip_basicauth');
        if (!$basicauth) {
            throw new \moodle_exception('bip:missingbasicauth', 'local_eduvidual');
        }
        return ['Authorization' => 'Basic ' . base64_encode($basicauth)];
    }

    public static function callBipIface(string $iface, array $params = []): mixed {
        return static::do_curl(static::buildBipUrl($iface), static::getBipHeaders(), $params ?: null);
    }

    /**
     * Holt genau eine Seite einer cursor-paginierten BIP-Schnittstelle (readuserdata_v3).
     * Liefert das vollständige Antwortobjekt (->result und ->meta), damit der Aufrufer
     * Pagination zwischen Cron-Läufen persistieren kann.
     */
    public static function callBipIfaceOnePage(string $iface, array $params = [], string $cursor = ''): object {
        if ($cursor !== '') {
            $params['cursor'] = $cursor;
        }
        return static::do_curl_full(static::buildBipUrl($iface), static::getBipHeaders(), $params ?: null);
    }

    public static function readOrgData(array $orgids): array {
        return static::callBipIface('local_eduportal_iface_readorgdata', ['orgids' => join(',', $orgids)]);
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
    public static function matchUsers(bool $execute = false): void {
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
        foreach ($DB->get_records('local_eduvidual_bip_user') as $bu) {
            $key = static::makeMatchKey($bu->orgid, $bu->role, $bu->firstname, $bu->lastname);
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
            $biprole = static::moodleRoleToBipRole($c->role);
            if (!$biprole) {
                continue;
            }
            $key = static::makeMatchKey($c->orgid, $biprole, $c->firstname, $c->lastname);
            if (!$key) {
                continue;
            }
            $moodleindex[$key][] = $c;
        }

        // Soll-Zustand berechnen: gleiche Logik wie früher, aber Ergebnis landet in $desired
        // statt direkt in der DB. AUTOMATCH-Lock zählt nicht - die Links sind ja re-evaluierbar.
        $desired = [];           // "userid|bpkbf" => [c, bu]
        $useridtaken = [];       // verhindert doppel-Match desselben userid in diesem Lauf
        $bpkbftaken = [];
        $ambiguous = 0;
        $skipped_locked = 0;

        foreach ($moodleindex as $key => $matchedusers) {
            if (count($matchedusers) > 1) {
                $ambiguous++;
                $list = join(', ', array_map(fn($u) => "{$u->firstname} {$u->lastname} (userid={$u->userid})", $matchedusers));
                mtrace("ambiguous moodle user for key '{$key}': {$list}");
                continue;
            }
            if (empty($bipindex[$key])) {
                continue;
            }
            if (count($bipindex[$key]) > 1) {
                $ambiguous++;
                $c = $matchedusers[0];
                $list = join(', ', array_map(fn($b) => "{$b->firstname} {$b->lastname} (bpkbf={$b->bpkbf})", $bipindex[$key]));
                mtrace("ambiguous bip user for key '{$key}' (moodle: {$c->firstname} {$c->lastname} userid={$c->userid}): {$list}");
                continue;
            }
            $c = $matchedusers[0];
            $bu = $bipindex[$key][0];

            if (isset($lockeduserids[$c->userid]) || isset($lockedbpkbfs[$bu->bpkbf])) {
                $skipped_locked++;
                continue;
            }
            if (isset($useridtaken[$c->userid]) || isset($bpkbftaken[$bu->bpkbf])) {
                continue;
            }
            $desired["{$c->userid}|{$bu->bpkbf}"] = [$c, $bu];
            $useridtaken[$c->userid] = true;
            $bpkbftaken[$bu->bpkbf] = true;
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
            mtrace("entferne AUTOMATCH (Match nicht mehr eindeutig): userid={$rec->userid} <-> bpkbf={$rec->idpusername}");
            if ($execute) {
                $DB->delete_records('auth_shibboleth_link', ['id' => $rec->id]);
            }
            $countdelete++;
        }

        foreach ($desired as $compositekey => [$c, $bu]) {
            if (isset($existing[$compositekey])) {
                continue;
            }
            if (!$execute) {
                mtrace("would match: moodle [{$c->firstname} {$c->lastname}] (userid={$c->userid}) <-> bip [{$bu->firstname} {$bu->lastname}] (bpkbf={$bu->bpkbf})");
            } else {
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
                mtrace("matched: userid={$c->userid} <-> bpkbf={$bu->bpkbf}");
            }
            $countinsert++;
        }

        mtrace("BIP-User-Match: {$countinsert} neu, {$countdelete} entfernt, {$countkeep} unverändert, {$ambiguous} mehrdeutig, {$skipped_locked} übersprungen (MANUAL/LOGIN-Lock)");
    }

    /**
     * Match-Key aus (orgid, BIP-Rollencode, erstes Vornamen-Wort, Nachname). Liefert null,
     * wenn nicht genügend Daten für einen sinnvollen Vergleich vorhanden sind.
     */
    private static function makeMatchKey($orgid, ?string $role, ?string $firstname, ?string $lastname): ?string {
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
    private static function moodleRoleToBipRole(?string $moodlerole): ?string {
        return match ($moodlerole) {
            \local_eduvidual\locallib::ROLE_STUDENT => 'std',
            \local_eduvidual\locallib::ROLE_TEACHER => 'tch',
            \local_eduvidual\locallib::ROLE_MANAGER => 'dir',
            \local_eduvidual\locallib::ROLE_PARENT => 'lgn',
            default => null,
        };
    }

    /**
     * Importiert Schulen aus BIP in local_eduvidual_org.
     * - Neue Schulen: vollständig anlegen (authenticated bleibt 0 = noch nicht registriert).
     * - Nicht registrierte Schulen (authenticated = 0): alle Felder updaten inkl. Name.
     * - Registrierte Schulen (authenticated > 0, TAN bestätigt): nur Adresse, Telefon und
     *   offizielle Mailadresse updaten. Der Schulname wird nicht überschrieben.
     */
    public static function importSchools(bool $execute = false): void {
    }

    /**
     * Importiert BIP-Userdaten (vorerst nur Schüler:innen, role=std) in die Tabelle
     * local_eduvidual_bip_user. Pro Aufruf eine Seite via readuserdata_v3.
     *
     * BIPs next_cursor wird in bip_userimport_cursor persistiert; der nächste Cron-Lauf
     * macht von dort aus weiter. Löschungen kommen alle inline aus der BIP-Antwort
     * (deleted=1 oder leere orgs-Liste). Ein separater Stale-Cleanup ist nicht nötig,
     * weil BIP abgemeldete User mit reduziertem orgs und gesetztem unassigned_orgs im
     * Delta mitliefert. Manueller Voll-Resync bei Bedarf: bip_userimport_cursor leeren.
     *
     * Nur Orgs mit authenticated>0 werden abgefragt.
     */
    public static function importUsers(bool $execute = false): void {
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

        $cursor = (string)(get_config('local_eduvidual', 'bip_userimport_cursor') ?: '');
        mtrace("BIP-User-Import: starte mit cursor='{$cursor}'");

        $now = time();
        $countinsert = 0;
        $countupdate = 0;
        $countdelete = 0;

        $jsondata = static::callBipIfaceOnePage('local_eduportal_iface_readuserdata_v3', [
            'orgids' => join(',', $orgids),
            'usertypes' => 'std',
            'limitnum' => 25000,
        ], $cursor);

        foreach ($jsondata->result as $bipuser) {
            // Ohne bpkbf ist der User nicht eindeutig identifizierbar - überspringen.
            // Sonst würden mehrere leere bpkbf-User unter bpkbf='' kollidieren und sich
            // gegenseitig beim Re-Insert wieder löschen.
            if (empty($bipuser->bpkbf)) {
                $orgsdebug = join(',', array_map(fn($o) => ($o->orgid ?? '?'), $bipuser->orgs ?? []));
                mtrace("  übersprungen: User ohne bpkbf (firstname='" . ($bipuser->firstname ?? '') . "', lastname='" . ($bipuser->lastname ?? '') . "', orgs=[{$orgsdebug}])");
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

            $existingforuser = $DB->get_records('local_eduvidual_bip_user', ['bpkbf' => $bipuser->bpkbf]);
            $existingkeyed = [];
            foreach ($existingforuser as $r) {
                $existingkeyed["{$r->orgid}|{$r->role}"] = $r;
            }

            if ($purgereason !== null) {
                if ($existingforuser) {
                    $details = join(', ', array_map(fn($r) => "{$r->orgid}/{$r->role}", $existingforuser));
                    mtrace("  löscht " . count($existingforuser) . " Einträge für bpkbf={$bipuser->bpkbf} ({$purgereason}): {$details}");
                    if ($execute) {
                        $DB->delete_records('local_eduvidual_bip_user', ['bpkbf' => $bipuser->bpkbf]);
                    }
                    $countdelete += count($existingforuser);
                }
                continue;
            }

            // Pflichtfelder pro aktiver User (nur wenn nicht gelöscht). Test auf Key-Existenz -
            // bei fehlendem Key Exception. Leere Werte sind erlaubt; sie werden im DB-Datensatz übernommen.
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
                    if ($role !== 'std') {
                        continue;
                    }

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
                mtrace("  löscht abgehängten Eintrag für bpkbf={$bipuser->bpkbf}: orgid={$r->orgid}, role={$r->role}");
                if ($execute) {
                    $DB->delete_records('local_eduvidual_bip_user', ['id' => $r->id]);
                }
                $countdelete++;
            }
        }

        $hasmore = !empty($jsondata->meta->has_more);
        $nextcursor = (string)($jsondata->meta->next_cursor ?? '');

        if ($execute) {
            set_config('bip_userimport_cursor', $nextcursor, 'local_eduvidual');
        }

        $status = $hasmore ? 'weiter beim nächsten Lauf' : 'Sweep abgeschlossen';
        mtrace("BIP-User-Import ({$status}): {$countinsert} angelegt, {$countupdate} aktualisiert, {$countdelete} gelöscht, cursor='{$nextcursor}'");
    }
}
