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
 * Sports Grades search page
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/sportsgrades/classes/search.php');
require_once($CFG->dirroot . '/blocks/sportsgrades/classes/forms/search_form.php');
require_once($CFG->dirroot . '/blocks/sportsgrades/classes/output/search_results_table.php');

// Page setup
$PAGE->set_url(new moodle_url('/blocks/sportsgrades/view.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('page_title', 'block_sportsgrades'));
$PAGE->set_heading(get_string('page_title', 'block_sportsgrades'));
$PAGE->set_pagelayout('standard');

// Check access
require_login();
require_capability('block/sportsgrades:view', context_system::instance());

// Check if the user has access
$search = new \block_sportsgrades\search();
$access = $search::get_user_access($USER->id);

if (empty($access)) {
    throw new moodle_exception('noaccess', 'block_sportsgrades');
}

// Create the search form
$search_form = new block_sportsgrades_search_form();

// Start output
echo $OUTPUT->header();

// Display the search form
$search_form->display();

// Process form submission
if ($data = $search_form->get_data()) {
    // Convert form data to object for search
    $search_params = new stdClass();
    $search_params->universal_id = $data->universal_id;
    $search_params->username = $data->username;
    $search_params->firstname = $data->firstname;
    $search_params->lastname = $data->lastname;
    $search_params->major = $data->major;
    $search_params->classification = $data->classification;
    $search_params->sport = $data->sport;
    
    // Perform search
    $results = $search::search_students($search_params);
    
    // Display results if search was successful
    if (!empty($results['success']) && !empty($results['results'])) {
        echo html_writer::tag('h4', get_string('search_results', 'block_sportsgrades'));
        
        // Create results table
        $table = new block_sportsgrades_search_results_table('sportsgrades_search_results');
        
        // Prepare data for the table
        $tabledata = [];
        foreach ($results['results'] as $student) {
            $row = new stdClass();
            $row->id = $student->id;
            $row->username = $student->username;
            $row->universal_id = $student->universal_id;
            $row->firstname = $student->firstname;
            $row->lastname = $student->lastname;
            $row->college = $student->college;
            $row->major = $student->major;
            $row->classification = $student->classification;
            $row->sports = serialize($student->sports);
            
            $tabledata[] = $row;
        }
        
        // Configure the table
        $table->setup();
        $table->set_data($tabledata);
        
        // Display the table
        $table->finish_output();
    } else if (!empty($results['error'])) {
        echo html_writer::div($results['error'], 'alert alert-danger');
    } else {
        echo html_writer::div(get_string('search_no_results', 'block_sportsgrades'), 'alert alert-info');
    }
}

// End output
echo $OUTPUT->footer();
