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
 * Grade fetcher for the Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sportsgrades;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

/**
 * Class for fetching student grades
 */
class grade_fetcher {
    
    /**
     * Get course grades for a student
     * 
     * @param int $studentid User ID of the student
     * @return array Course grades
     */
    public static function get_course_grades($studentid) {
        global $DB, $USER;
        
        // Check access first
        $search = new search();
        $access = $search::get_user_access($USER->id);
        
        if (empty($access)) {
            return ['error' => get_string('noaccess', 'block_sportsgrades')];
        }
        
        // Check if user has access to this specific student
        if (!$access['all_students'] && 
            !in_array($studentid, $access['student_ids']) && 
            !self::is_student_in_accessible_sports($studentid, $access['sports'])) {
            return ['error' => get_string('noaccess', 'block_sportsgrades')];
        }
        
        // Check cache first
        $cached_data = self::get_cached_data($studentid);
        if (!empty($cached_data)) {
            return $cached_data;
        }
        
        // Get courses the student is enrolled in
        $sql = "SELECT DISTINCT e.courseid, c.fullname, c.shortname, 
                tca.name as term, c.startdate
                FROM {enrol} e
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                JOIN {course} c ON c.id = e.courseid
                LEFT JOIN {enrol_wds_course_meta} cm ON cm.courseid = c.id AND cm.datatype = 'term_code'
                LEFT JOIN {enrol_wds_term_code_array} tca ON tca.code = cm.data
                WHERE ue.userid = :studentid
                ORDER BY c.startdate DESC, c.fullname ASC";
        
        $courses = $DB->get_records_sql($sql, ['studentid' => $studentid]);
        
        if (empty($courses)) {
            return ['courses' => []];
        }
        
        $results = [];
        foreach ($courses as $course) {
            // Get final grade for the course
            $grade_info = grade_get_course_grade($studentid, $course->courseid);
            
            // Get section information
            $section = self::get_course_section($course->courseid);
            
            $course_data = [
                'id' => $course->courseid,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'section' => $section,
                'term' => $course->term,
                'startdate' => $course->startdate,
                'final_grade' => !empty($grade_info) ? $grade_info->grade : null,
                'final_grade_formatted' => !empty($grade_info) ? number_format($grade_info->grade, 2) : '-',
                'letter_grade' => !empty($grade_info) ? self::get_letter_grade($grade_info->grade) : '-',
                'grade_items' => self::get_grade_items($studentid, $course->courseid)
            ];
            
            $results[] = $course_data;
        }
        
        // Sort by date (newest first) and then alphabetically
        usort($results, function($a, $b) {
            if ($a['startdate'] == $b['startdate']) {
                return strcasecmp($a['fullname'], $b['fullname']);
            }
            return $b['startdate'] - $a['startdate'];
        });
        
        // Cache the results
        self::cache_data($studentid, $results);
        
        return ['courses' => $results];
    }
    
    /**
     * Get grade items for a course
     * 
     * @param int $studentid User ID of the student
     * @param int $courseid Course ID
     * @return array Grade items
     */
    private static function get_grade_items($studentid, $courseid) {
        global $DB;
        
        // Get grade items for the course
        $grade_items = grade_get_grade_items($courseid);
        
        if (empty($grade_items)) {
            return [];
        }
        
        $results = [];
        foreach ($grade_items as $item) {
            if ($item->itemtype == 'course') {
                continue; // Skip the course total item
            }
            
            // Get the grade for this item
            $grade = grade_get_grades($courseid, 'mod', $item->itemmodule, $item->iteminstance, $studentid);
            
            // Get calculation info if available
            $weight = null;
            $contribution = null;
            
            if (!empty($grade->items[0]->weightoverride)) {
                $weight = $grade->items[0]->weightoverride;
            } else if (!empty($grade->items[0]->weight)) {
                $weight = $grade->items[0]->weight;
            }
            
            if (!empty($weight) && !empty($grade->items[0]->grademax) && !empty($grade->items[0]->grades[$studentid]->grade)) {
                $percentage = ($grade->items[0]->grades[$studentid]->grade / $grade->items[0]->grademax) * 100;
                $contribution = ($percentage * $weight) / 100;
            }
            
            $results[] = [
                'id' => $item->id,
                'name' => $item->itemname,
                'type' => $item->itemtype,
                'module' => $item->itemmodule,
                'weight' => $weight,
                'weight_formatted' => !empty($weight) ? number_format($weight, 2) . '%' : '-',
                'grade' => !empty($grade->items[0]->grades[$studentid]->grade) ? $grade->items[0]->grades[$studentid]->grade : null,
                'grade_formatted' => !empty($grade->items[0]->grades[$studentid]->grade) ? number_format($grade->items[0]->grades[$studentid]->grade, 2) : '-',
                'grademax' => $grade->items[0]->grademax,
                'percentage' => !empty($grade->items[0]->grades[$studentid]->grade) ? 
                    ($grade->items[0]->grades[$studentid]->grade / $grade->items[0]->grademax) * 100 : null,
                'percentage_formatted' => !empty($grade->items[0]->grades[$studentid]->grade) ? 
                    number_format(($grade->items[0]->grades[$studentid]->grade / $grade->items[0]->grademax) * 100, 2) . '%' : '-',
                'contribution' => $contribution,
                'contribution_formatted' => !empty($contribution) ? 
                    number_format($contribution, 2) . '%' : '-'
            ];
        }
        
        return $results;
    }
    
    /**
     * Get section information for a course
     * 
     * @param int $courseid Course ID
     * @return string Section information
     */
    private static function get_course_section($courseid) {
        global $DB;
        
        $sql = "SELECT cm.data
                FROM {enrol_wds_course_meta} cm
                WHERE cm.courseid = :courseid AND cm.datatype = 'section_code'";
        
        $result = $DB->get_field_sql($sql, ['courseid' => $courseid]);
        
        return $result ? $result : '';
    }
    
    /**
     * Convert numeric grade to letter grade
     * 
     * @param float $grade Numeric grade
     * @return string Letter grade
     */
    private static function get_letter_grade($grade) {
        if ($grade >= 90) {
            return 'A';
        } else if ($grade >= 80) {
            return 'B';
        } else if ($grade >= 70) {
            return 'C';
        } else if ($grade >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }
    
    /**
     * Check if a student is in a sport that the user has access to
     * 
     * @param int $studentid Student ID
     * @param array $sports Array of sport codes
     * @return bool True if student is in an accessible sport
     */
    private static function is_student_in_accessible_sports($studentid, $sports) {
        global $DB;
        
        if (empty($sports)) {
            return false;
        }
        
        list($in_sql, $params) = $DB->get_in_or_equal($sports);
        $params[] = $studentid;
        
        $sql = "SELECT COUNT(*)
                FROM {enrol_wds_students_meta} 
                WHERE datatype = 'Athletic_Team_ID'
                AND data $in_sql
                AND studentid = ?";
        
        return $DB->count_records_sql($sql, $params) > 0;
    }
    
    /**
     * Get cached grade data for a student
     * 
     * @param int $studentid Student ID
     * @return array|false Cached data or false if not found/expired
     */
    private static function get_cached_data($studentid) {
        global $DB;
        
        $now = time();
        
        $sql = "SELECT data
                FROM {block_sportsgrades_cache}
                WHERE studentid = :studentid
                AND timeexpires > :now
                ORDER BY timecreated DESC
                LIMIT 1";
        
        $cached = $DB->get_field_sql($sql, ['studentid' => $studentid, 'now' => $now]);
        
        if (!empty($cached)) {
            return json_decode($cached, true);
        }
        
        return false;
    }
    
    /**
     * Cache grade data for a student
     * 
     * @param int $studentid Student ID
     * @param array $data Data to cache
     * @return bool Success
     */
    private static function cache_data($studentid, $data) {
        global $DB;
        
        // Cache for 1 hour
        $expiry = time() + (60 * 60);
        
        $cache_record = new \stdClass();
        $cache_record->studentid = $studentid;
        $cache_record->data = json_encode(['courses' => $data]);
        $cache_record->timecreated = time();
        $cache_record->timeexpires = $expiry;
        
        return $DB->insert_record('block_sportsgrades_cache', $cache_record);
    }
}
