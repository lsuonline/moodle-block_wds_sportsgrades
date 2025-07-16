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
 * Sports Grades block
 *
 * @package    block_wds_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/wds_sportsgrades/classes/forms/add_user_form.php');

// Set the context.
$context = context_system::instance();

// Page setup.
$PAGE->set_url(new moodle_url('/blocks/wds_sportsgrades/admin.php'));

$PAGE->set_context($context);

$PAGE->set_title(get_string('wdsaddusertitle', 'block_wds_sportsgrades'));
$PAGE->set_heading(get_string('wdsaddusertitle', 'block_wds_sportsgrades'));
$PAGE->set_pagelayout('standard');

require_login();
require_capability('block/wds_sportsgrades:manageaccess', $context);

$mform = new add_user_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));

} else if ($mform->is_submitted() && $mform->is_validated()) {

    // Get the form data.
    $formdata = $mform->get_data();

    // Process the form data.
    if (!empty($formdata->useradd)) {

        foreach ($formdata->useradd as $userid) {

            $record = new stdClass();
            $record->userid = $userid;
            $record->sportid = $formdata->sportid;
            $record->timecreated = time();
            $record->timemodified = time();
            $record->createdby = $USER->id;
            $record->modifiedby = $USER->id;
            $newentry = $DB->insert_record('block_wds_sportsgrades_access', $record);

        }

        redirect(new moodle_url('/blocks/wds_sportsgrades/admin.php'));
    } else {

        if (optional_param('userremove', 0, PARAM_INT) && confirm_sesskey()) {
            $userremoveid = required_param('userremove', PARAM_INT);

            $DB->delete_records('block_wds_sportsgrades_access', ['id' => $userremoveid]);

            redirect(new moodle_url('/blocks/wds_sportsgrades/admin.php'));
        }
    }
}

echo $OUTPUT->header();

$mform->display();

/*
$existingsql = 'SELECT u.*, sa.*, s.name AS sportname
    FROM {user} u
    INNER JOIN {block_wds_sportsgrades_access} sa ON sa.userid = u.id
    INNER JOIN {enrol_wds_sport} s ON s.id = sa.sportid';
$users = $DB->get_records_sql($existingsql);

if (!empty($users)) {
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('sport', 'block_wds_sportsgrades'),
        get_string('action'),
    ];

    foreach ($users as $user) {
        $removeurl = new moodle_url('/blocks/wds_sportsgrades/admin.php', ['userremove' => $user->id, 'sesskey' => sesskey()]);
        $removebutton = new single_button($removeurl, get_string('remove'), 'post');
        $row = [
            fullname($user),
            $user->sportname,
            $OUTPUT->render($removebutton),
        ];
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}
*/

echo $OUTPUT->footer();
