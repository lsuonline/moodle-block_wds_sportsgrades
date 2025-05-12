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
 * Web service definitions for Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_sportsgrades_search_students' => [
        'classname'     => 'block_sportsgrades_external',
        'methodname'    => 'search_students',
        'classpath'     => 'blocks/sportsgrades/classes/external.php',
        'description'   => 'Search for student athletes based on criteria',
        'type'          => 'read',
        'capabilities'  => 'block/sportsgrades:view',
        'ajax'          => true,
    ],
    'block_sportsgrades_get_student_grades' => [
        'classname'     => 'block_sportsgrades_external',
        'methodname'    => 'get_student_grades',
        'classpath'     => 'blocks/sportsgrades/classes/external.php',
        'description'   => 'Get grades for a student athlete',
        'type'          => 'read',
        'capabilities'  => 'block/sportsgrades:viewgrades',
        'ajax'          => true,
    ],
];
