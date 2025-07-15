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
        global $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'adduserheader', get_string('adduser', 'block_wds_sportsgrades'));

        $users = $DB->get_records('user', [], 'lastname, firstname', 'id, firstname, lastname');
        $useroptions = [];
        foreach ($users as $user) {
            $useroptions[$user->id] = fullname($user);
        }

        $mform->addElement('select', 'useradd', get_string('adduser', 'block_wds_sportsgrades'), $useroptions);
        $mform->getElement('useradd')->setMultiple(true);

        $sports = $DB->get_records('enrol_wds_sport', [], 'name', 'id, name');
        $sportoptions = [0 => get_string('all_sports', 'block_wds_sportsgrades')];
        foreach ($sports as $sport) {
            $sportoptions[$sport->id] = $sport->name;
        }

        $mform->addElement('select', 'sportid', get_string('sport', 'block_wds_sportsgrades'), $sportoptions);

        $this->add_action_buttons(true, get_string('adduser', 'block_wds_sportsgrades'));

        $mform->addElement('header', 'removeuserheader', get_string('removeuser', 'block_wds_sportsgrades'));

        $users = $DB->get_records_sql('
            SELECT u.id, u.firstname, u.lastname
            FROM {block_wds_sportsgrades_access} a
            INNER JOIN {user} u ON a.userid = u.id
        ');
        $useroptions = [];
        foreach ($users as $user) {
            $useroptions[$user->id] = fullname($user);
        }

        $mform->addElement('select', 'userremove', get_string('removeuser', 'block_wds_sportsgrades'), $useroptions);
        $mform->getElement('userremove')->setMultiple(true);

        $this->add_action_buttons(true, get_string('removeuser', 'block_wds_sportsgrades'));
    }
}
