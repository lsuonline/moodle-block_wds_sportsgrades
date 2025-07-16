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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class add_user_form extends moodleform {

    public function definition() {
        global $DB, $OUTPUT;

        // Instantiate the form.
        $mform = $this->_form;

        // Add the area for adding user / sport associations.
        $mform->addElement('header', 'adduserheader', get_string('adduser', 'block_wds_sportsgrades'));

        // Set the context.
        $context = context_system::instance();

        // Get potential users by capability.
        $pusers = get_users_by_capability(
            $context,
            'block/student_gradeviewer:sportsgrades',
            'u.id, u.*',
            'u.lastname ASC, u.firstname ASC');

        // Build out the options array for the form.
        $useroptions = [];

        // Loop through the potential users to build the form.
        foreach ($pusers as $puser) {
            $useroptions[$puser->id] = fullname($puser);
        }

        // Add the element.
        $mform->addElement('select', 'useradd', get_string('adduser', 'block_wds_sportsgrades'), $useroptions);
        $mform->getElement('useradd')->setMultiple(true);

        // Get the sports.
        $sports = $DB->get_records_sql('SELECT * FROM {enrol_wds_sport} GROUP BY name ORDER BY name ASC', null);

        // Make sure we have an option for all sports.
        $sportoptions = [0 => get_string('all_sports', 'block_wds_sportsgrades')];

        // Loop through them and add them to the above array.
        foreach ($sports as $sport) {
            $sportoptions[$sport->id] = $sport->name;
        }

        // Add the sport selector.
        $mform->addElement('select', 'sportid', get_string('sport', 'block_wds_sportsgrades'), $sportoptions);

        $this->add_action_buttons(true, get_string('adduser', 'block_wds_sportsgrades'));

        $mform->addElement('header', 'removeuserheader', get_string('removeuser', 'block_wds_sportsgrades'));

        $existingsql = 'SELECT sa.id AS assignid, sa.sportid, u.*, COALESCE(s.name, "All Sports") AS sportname
            FROM {user} u
            INNER JOIN {block_wds_sportsgrades_access} sa ON sa.userid = u.id
            LEFT JOIN {enrol_wds_sport} s ON s.id = sa.sportid
            ORDER BY s.name ASC, u.lastname ASC, u.firstname ASC';

        $eusers = $DB->get_records_sql($existingsql);

        if (!empty($eusers)) {

            // Group users by sport.
            $sportsgroups = [];
            foreach ($eusers as $euser) {
                $sportname = $euser->sportname == '' ? 'All Sports' : $euser->sportname;
                if (!isset($sportsgroups[$sportname])) {
                    $sportsgroups[$sportname] = [];
                }
                $sportsgroups[$sportname][] = $euser;
            }

            // Create a table for each sport.
            foreach ($sportsgroups as $sportname => $sportusers) {
            // Add a header for the sport.

                $mform->addElement('header', $sportname, $sportname);

                $table = new html_table();
                $table->attributes = ['class' => 'generaltable sportstable'];

                $table->head = [
                    get_string('user'),
                    get_string('sport', 'block_wds_sportsgrades'),
                    get_string('action'),
                ];

                foreach ($sportusers as $euser) {
                    $removeurl = new moodle_url('/blocks/wds_sportsgrades/admin.php', ['userremove' => $euser->assignid, 'sesskey' => sesskey()]);
                    $removebutton = new single_button($removeurl, get_string('remove'), 'post');
                    $removebutton->class = 'sportsbutton';
   


                    $row = [
                        fullname($euser),
                        $euser->sportname == '' ? 'All Sports' : $euser->sportname,
                        $OUTPUT->render($removebutton),
                    ];
                    $table->data[] = $row;
                }

                $mform->addElement('html', html_writer::table($table));
            }
        }
    }
}
