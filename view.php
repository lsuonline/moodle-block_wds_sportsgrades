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
require_once($CFG->dirroot . '/blocks/sportsgrades/classes/grade_fetcher.php');

// Page setup
$PAGE->set_url(new moodle_url('/blocks/sportsgrades/view.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'block_sportsgrades'));
$PAGE->set_heading(get_string('pluginname', 'block_sportsgrades'));
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

// Include JS and CSS
$PAGE->requires->js_call_amd('block_sportsgrades/search', 'init');
$PAGE->requires->js_call_amd('block_sportsgrades/grade_display', 'init');

// Start output
echo $OUTPUT->header();

// Create and render search form
$renderer = $PAGE->get_renderer('block_sportsgrades');
$searchform = new \block_sportsgrades\output\search_form();
echo $renderer->render($searchform);

// Add container for search results
echo html_writer::start_div('sportsgrades-search-results', [
    'id' => 'sportsgrades-search-results',
]);
echo html_writer::end_div();

// Add container for grade display
echo html_writer::start_div('sportsgrades-grade-display', [
    'id' => 'sportsgrades-grade-display',
    'style' => 'display: none;'
]);
echo html_writer::end_div();

// End output
echo $OUTPUT->footer();
