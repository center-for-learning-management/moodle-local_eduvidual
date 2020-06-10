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
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (block_eduvidual::get('orgrole') != "Manager" && block_eduvidual::get('role') != 'Administrator') {
    ?>
    <p class="alert alert-warning"><?php echo get_string('js:missing_permission', 'block_eduvidual'); ?></p>
    <?php
    exit;
}

require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_import.php');
$helper = new block_eduvidual_lib_import();
$compiler = new block_eduvidual_lib_import_compiler_user();
$helper->set_compiler($compiler);

if (optional_param('datavalidated', 0, PARAM_INT) == 1) {
    $helper->load_post();
    $users = $helper->get_rowobjects();
    $nextusers = array();
    $exportuserids = array();
    ?>
    <p class="alert alert-info"><?php echo get_string('manage:createuserspreadsheet:import:downloadfile', 'block_eduvidual'); ?></p>
    <form action="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/sub/manage_usersdownload.php" method="post" enctype="multipart/form-data" class="no-spinner">
        <input type="hidden" name="orgid" value="<?php echo $org->orgid; ?>" />
        <input type="hidden" name="act" value="users" />
        <table border="1" width="100%">
            <tr>
                <th>chk</th>
                <th>firstname</th>
                <th>lastname</th>
                <th>email</th>
                <th>bunch</th>
                <th>result</th>
                <th>secret</th>
            </tr>
    <?php
    for ($a = 0; $a < count($users); $a++) {
        $user = $users[$a];
        ?>
        <tr<?php if(strtolower($user->role) == 'remove') { echo " style=\"text-decoration: line-through;\""; } ?>>
            <td><img src="/pix/i/<?php echo ((isset($user->payload->processed) && $user->payload->processed)?'completion-auto-pass':'completion-auto-fail'); ?>.svg" /></td>
            <td><?php echo @$user->firstname; ?></td>
            <td><?php echo @$user->lastname; ?></td>
            <td><?php echo @$user->email; ?></td>
            <td><?php echo @$user->bunch; ?></td>
            <td>
        <?php
        if ($user->payload->processed) {
            // Pack the object before database-query
            $user->payload = json_encode($user->payload, JSON_NUMERIC_CHECK);
            if (!empty($user->id)) {
                $u = $DB->get_record('user', array('id' => $user->id));
                $u->firstname = $user->firstname;
                $u->lastname = $user->lastname;
                $u->email = $user->email;
                $u->confirmed = 1;
                $u->mnethostid = 1;
                user_update_user($u, false);
                if (!empty($user->password)) {
                    update_internal_user_password($u, $user->password, false);
                }

                require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                block_eduvidual_lib_enrol::role_set($u->id, $org, $user->role);

                if (strtolower($user->role) == 'remove') {
                    echo 'Removed #' . $u->id;
                } else {
                    echo 'Updated #' . $u->id;
                }
            } else {
                // Create new user and store id
                $u = new stdClass();
                $u->confirmed = 1;
                $u->mnethostid = 1;
                $u->username = $user->username;
                $u->firstname = $user->firstname;
                $u->lastname = $user->lastname;
                $u->email = $user->email;
                $u->auth = 'manual';
                $u->lang = 'de';
                $u->calendartype = 'gregorian';

                $u->id = user_create_user($u, false, false);
                $user->secret = block_eduvidual::get_user_secret($u->id);
                if (empty($user->password)) {
                    $user->password = $user->secret;
                }
                update_internal_user_password($u, $user->password, false);

                $user->id = $u->id;

                if (!empty($user->bunch)) {
                    $userbunch = (object) array(
                        'orgid' => $org->orgid,
                        'userid' => $u->id,
                        'bunch' => $user->bunch,
                    );
                    $DB->insert_record('block_eduvidual_userbunches', $userbunch);
                }

                require_once($CFG->dirroot . '/blocks/eduvidual/classes/lib_enrol.php');
                block_eduvidual_lib_enrol::role_set($user->id, $org, $user->role);
                if (!empty($user->bunch) && strtolower($user->role) != 'remove') {
                    block_eduvidual_lib_enrol::bunch_set($user->id, $org, $user->bunch);
                }
                block_eduvidual_lib_enrol::choose_background($user->id);
                // Trigger event.
                \core\event\user_created::create_from_userid($user->id)->trigger();
                if ($user->id > 0) {
                    echo 'Created #' . $user->id;
                } else {
                    echo 'Failed!';
                }
            }
            // Unpack afterwards to restore previous state
            $user->payload = json_decode($user->payload);
        } else {
            echo 'skipped';
        }
        ?>
                </td>
                <td>
        <?php if(!empty($user->secret)) { echo $user->secret; } else { echo '-'; } ?>
                </td>
            </tr>
        </tr>
        <?php
        if (!empty($user->id)) {
            $exportuserids[] = $user->id;
            $nextusers[] = $user;
        }
    }
    $helper->set_rowobjects($users);
    ?>
        </table>
        <div class="grid-eq-2">
            <a href="#" onclick="require(['block_eduvidual/manager'], function(M) { M.exportUserPopup('<?php echo $org->orgid; ?>', '<?php echo implode(',', $exportuserids); ?>'); }); return false;" class="btn btn-primary">
                <img src="<?php echo $CFG->wwwroot; ?>/pix/i/export.svg" alt="export" />
                <?php echo get_string('export', 'block_eduvidual'); ?>
            </a>
            <a href="<?php echo $CFG->wwwroot . '/blocks/eduvidual/pages/manage_bunch.php?orgid=' . $org->orgid; ?>" target="_blank" class="btn ui-btn">
                <img src="<?php echo $CFG->wwwroot; ?>/pix/t/print.svg" alt="print" />
                <?php echo get_string('manage:users:printcards', 'block_eduvidual'); ?>
            </a>
        </div>
        <a href="<?php echo $CFG->wwwroot; ?>/blocks/eduvidual/pages/manage.php?orgid=<?php echo $org->orgid; ?>" class="btn btn-primary">
            <?php echo get_string('back'); ?>
        </a>
        <?php echo $helper->print_hidden_form(); ?>
    </form>
    <?php
} elseif (isset($_FILES['block_eduvidual_manage_usersimport'])) {
    $helper->set_fields(array('id', 'username', 'email', 'firstname', 'lastname', 'role', 'bunch'));
    $helper->load_file($_FILES['block_eduvidual_manage_usersimport']['tmp_name']);
    $objs = $helper->get_rowobjects();
    $fields = $helper->get_fields();
    ?>
    <table border="1" width="100%">
        <tr>
            <td>chk</td>
            <td>firstname</td>
            <td>lastname</td>
            <td>email</td>
            <td>role</td>
            <td>bunch</td>
            <td>action</td>
        </tr>
        <?php
        foreach($objs AS $obj) {
            ?>
        <tr>
            <td><img src="/pix/i/<?php echo ((isset($obj->payload->processed) && $obj->payload->processed)?'completion-auto-pass':'completion-auto-fail'); ?>.svg" /></td>
            <td><?php echo @$obj->firstname; ?></td>
            <td><?php echo @$obj->lastname; ?></td>
            <td><?php echo @$obj->email; ?></td>
            <td><?php echo @$obj->role; ?></td>
            <td><?php echo @$obj->bunch; ?></td>
            <td><?php echo @$obj->payload->action; ?></td>
        </tr>
            <?php
        }
        ?>
    </table>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="act" value="<?php echo $act; ?>" />
        <input type="hidden" name="import" value="1" />
        <input type="hidden" name="datavalidated" value="1" />
        <?php echo $helper->print_hidden_form(); ?>
        <input type="submit" value="<?php echo get_string('manage:createuserspreadsheet:import:datavalidated', 'block_eduvidual'); ?>" />
    </form>

    <?php
} else {
    ?>
    <ul>
        <li>id:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:id', 'block_eduvidual'); ?></li>
        <li>firstname:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:firstname', 'block_eduvidual'); ?></li>
        <li>lastname:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:lastname', 'block_eduvidual'); ?></li>
        <li>email:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:email', 'block_eduvidual'); ?></li>
        <li>role:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:role', 'block_eduvidual'); ?></li>
        <li>bunch:<br /><?php echo get_string('manage:createuserspreadsheet:import:description:bunch', 'block_eduvidual'); ?></li>
    </ul>
    <?php
}
