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
 * @copyright  2022 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eduvidual\educloud;

defined('MOODLE_INTERNAL') || die;

class school {
    /**
     * Accepts the data transfer on behalf of the org.
     * @param orgid
     * @return object record from database of local_eduvidual_educloud or false.
     */
    public static function accept($orgid) {
        global $DB, $USER;
        try {
            $transaction = $DB->start_delegated_transaction();
            $record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $orgid]);
            if (empty($record->id)) {
                $record = (object) [
                    'orgid' => $orgid,
                    'permitted' => 0,
                    'permittedby' => 0,
                    'accepted' => time(),
                    'acceptedby' => $USER->id,
                ];
                $record->id = $DB->insert_record('local_eduvidual_educloud', $record);
            } else {
                $record->accepted = time();
                $record->acceptedby = $USER->id;
                $DB->update_record('local_eduvidual_educloud', $record);
            }
            self::sync($orgid);
            $transaction->allow_commit();
            return $record;
        } catch(\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    /**
     * Create an org in univention portal and store its ucsurl.
     * @param orgid.
     * @return ucsurl.
     */
    public static function create($orgid) {
        global $DB;
        $org = \local_eduvidual\locallib::get_org('orgid', $orgid);

        // Check if this organisation already exists.
        $exists = json_decode(\local_eduvidual\educloud\lib::curl(
            '/ucsschool/kelvin/v1/schools/' . $org->orgid,
            [],
            [],
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
                'Content-Type' => 'application/json',
            ],
            'GET',
            false
        ));
        if (!empty($exists->url)) {
            $DB->set_field('local_eduvidual_educloud', 'ucsurl', $exists->url, [ 'orgid' => $org->orgid ]);
            $DB->set_field('local_eduvidual_educloud', 'ucsname', $exists->name, [ 'orgid' => $org->orgid ]);
            return $exists->url;
        }

        // Does not exist, create it.
        $created = json_decode(\local_eduvidual\educloud\lib::curl(
            '/ucsschool/kelvin/v1/schools/',
            [],
            json_encode((object)[
                'name' => $org->orgid,
                'display_name' => $org->name,
            ]),
            [
                'Accept' => 'application/json',
                'Authorization' => \local_eduvidual\educloud\lib::token(),
                'Content-Type' => 'application/json',
            ],
            'POST',
            false
        ));
        if (!empty($created->url)) {
            $DB->set_field('local_eduvidual_educloud', 'ucsurl', $created->url, [ 'orgid' => $org->orgid ]);
            $DB->set_field('local_eduvidual_educloud', 'ucsname', $created->name, [ 'orgid' => $org->orgid ]);
            return $created->url;
        }
    }
    /**
     * Disables the feature for a particular org.
     * @param orgid
     * @return object record from database of local_eduvidual_educloud or false.
     */
    public static function disable($orgid) {
        global $DB;
        try {
            $transaction = $DB->start_delegated_transaction();
            $record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $orgid]);
            if (!empty($record->id)) {
                $record->permitted = 0;
                $record->permittedby = 0;
                $DB->update_record('local_eduvidual_educloud', $record);
            }
            self::sync($orgid);
            $transaction->allow_commit();
            return $record;
        } catch(\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    /**
     * Enables the feature for a particular org.
     * @param orgid
     * @return object record from database of local_eduvidual_educloud or false.
     */
    public static function enable($orgid) {
        global $DB, $USER;
        try {
            $transaction = $DB->start_delegated_transaction();
            $record = $DB->get_record('local_eduvidual_educloud', [ 'orgid' => $orgid]);
            if (empty($record->id)) {
                $record = (object) [
                    'orgid' => $orgid,
                    'permitted' => time(),
                    'permittedby' => $USER->id,
                    'accepted' => 0,
                    'acceptedby' => 0,
                ];
                $record->id = $DB->insert_record('local_eduvidual_educloud', $record);
            } else {
                $record->permitted = time();
                $record->permittedby = $USER->id;
                $DB->update_record('local_eduvidual_educloud', $record);
            }
            self::sync($orgid);
            $transaction->allow_commit();
            return $record;
        } catch(\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }
    /**
     * Schedules a sync for all users of an org.
     * @param int orgid
     */
    public static function sync($orgid) {
        global $DB, $OUTPUT;
        $members = $DB->get_records('local_eduvidual_orgid_userid', [ 'orgid' => $orgid ]);
        foreach ($members as $member) {
            \local_eduvidual\educloud\user::action($member->userid);
        }
        return true;
    }
}
