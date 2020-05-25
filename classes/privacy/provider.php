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
 * @package    block_eduvidual
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_eduvidual\privacy;
use core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die;

class provider implements \core_privacy\local\metadata\provider {
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'block_eduvidual_courseshow',
             array(),
            'privacy:metadata:privacy:metadata:block_eduvidual_courseshow'
        );
        $collection->add_database_table(
            'block_eduvidual_orgid_userid',
             array(),
            'privacy:metadata:privacy:metadata:block_eduvidual_orgid_userid'
        );
        $collection->add_database_table(
            'block_eduvidual_userbunch',
             array(
                'orgid' => 'privacy:metadata:block_eduvidual_userbunch:orgid',
                'bunch' => 'privacy:metadata:block_eduvidual_userbunch:bunch',
            ),
            'privacy:metadata:privacy:metadata:block_eduvidual_userbunch'
        );
        $collection->add_database_table(
            'block_eduvidual_userqcats',
             array(),
            'privacy:metadata:privacy:metadata:block_eduvidual_userqcats'
        );
        $collection->add_database_table(
            'block_eduvidual_usertoken',
             array(
                'token' => 'privacy:metadata:block_eduvidual_usertoken:token',
                'created' => 'privacy:metadata:block_eduvidual_usertoken:created',
                'used' => 'privacy:metadata:block_eduvidual_usertoken:used',
            ),
            'privacy:metadata:privacy:metadata:block_eduvidual_usertoken'
        );
        return $collection;
    }
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
    */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT * FROM {block_eduvidual_courseshow} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_eduvidual_orgid_userid} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_eduvidual_userbunch} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_eduvidual_userqcats} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        $sql = "SELECT * FROM {block_eduvidual_usertoken} WHERE userid=?";
        $params = ['userid' => $userid ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param   int         $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $markasreadonnotification = get_user_preference('markasreadonnotification', null, $userid);
        if (null !== $markasreadonnotification) {
            switch ($markasreadonnotification) {
                case 0:
                    $markasreadonnotificationdescription = get_string('markasreadonnotificationno', 'mod_forum');
                    break;
                case 1:
                default:
                    $markasreadonnotificationdescription = get_string('markasreadonnotificationyes', 'mod_forum');
                    break;
            }
            writer::export_user_preference('mod_forum', 'markasreadonnotification', $markasreadonnotification, $markasreadonnotificationdescription);
        }
    }


    // @TODO Export user data

    // @TODO Delete user data (only partly - some data is managed by organizations!)

}
