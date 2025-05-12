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
 * Search functionality for the Sports Grades block
 *
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_sportsgrades;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for searching student athletes
 */
class search {
    
    /**
     * Search for student athletes based on criteria
     * 
     * @param array $params Search parameters
     * @return array Search results
     */
    public static function search_students($params) {
        global $DB, $USER;
        
        // Check if user has access to any sports or specific students
        $access = self::get_user_access($USER->id);
        
        if (empty($access)) {
            return ['error' => get_string('noaccess', 'block_sportsgrades')];
        }
        
        // Build the SQL query
        $sql_select = "SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, 
                      u.email, p.universal_id, p.college, p.major, p.classification";
        
        $sql_from = " FROM {user} u 
                    JOIN {enrol_wds_students_meta} sm ON sm.studentid = u.id AND sm.datatype = 'Athletic_Team_ID'
                    LEFT JOIN (
                        SELECT 
                            userid,
                            MAX(CASE WHEN datatype = 'universal_id' THEN data ELSE NULL END) AS universal_id,
                            MAX(CASE WHEN datatype = 'college' THEN data ELSE NULL END) AS college,
                            MAX(CASE WHEN datatype = 'major' THEN data ELSE NULL END) AS major,
                            MAX(CASE WHEN datatype = 'classification' THEN data ELSE NULL END) AS classification
                        FROM {enrol_wds_students_meta}
                        GROUP BY userid
                    ) p ON p.userid = u.id";
        
        $conditions = [];
        $params_sql = [];
        
        // Filter by access permissions
        if (!empty($access['sports']) && !$access['all_students']) {
            $sport_placeholders = [];
            $i = 0;
            foreach ($access['sports'] as $sport_code) {
                $param_name = 'sport' . $i;
                $sport_placeholders[] = ':' . $param_name;
                $params_sql[$param_name] = $sport_code;
                $i++;
            }
            $conditions[] = "sm.data IN (" . implode(',', $sport_placeholders) . ")";
        }
        
        if (!empty($access['student_ids']) && !$access['all_students']) {
            $student_placeholders = [];
            $i = 0;
            foreach ($access['student_ids'] as $student_id) {
                $param_name = 'student' . $i;
                $student_placeholders[] = ':' . $param_name;
                $params_sql[$param_name] = $student_id;
                $i++;
            }
            
            if (!empty($conditions)) {
                $conditions[] = "OR u.id IN (" . implode(',', $student_placeholders) . ")";
            } else {
                $conditions[] = "u.id IN (" . implode(',', $student_placeholders) . ")";
            }
        }
        
        // Apply search filters
        if (!empty($params['universal_id'])) {
            $conditions[] = "p.universal_id LIKE :universal_id";
            $params_sql['universal_id'] = '%' . $params['universal_id'] . '%';
        }
        
        if (!empty($params['username'])) {
            $conditions[] = "u.username LIKE :username";
            $params_sql['username'] = '%' . $params['username'] . '%';
        }
        
        if (!empty($params['firstname'])) {
            $conditions[] = "u.firstname LIKE :firstname";
            $params_sql['firstname'] = '%' . $params['firstname'] . '%';
        }
        
        if (!empty($params['lastname'])) {
            $conditions[] = "u.lastname LIKE :lastname";
            $params_sql['lastname'] = '%' . $params['lastname'] . '%';
        }
        
        if (!empty($params['major'])) {
            $conditions[] = "p.major LIKE :major";
            $params_sql['major'] = '%' . $params['major'] . '%';
        }
        
        if (!empty($params['classification'])) {
            $conditions[] = "p.classification = :classification";
            $params_sql['classification'] = $params['classification'];
        }
        
        if (!empty($params['sport'])) {
            $conditions[] = "sm.data = :sport";
            $params_sql['sport'] = $params['sport'];
        }
        
        // Build the WHERE clause
        $sql_where = !empty($conditions) ? " WHERE " . implode(' AND ', $conditions) : "";
        
        // Add ORDER BY clause to sort results
        $sql_order = " ORDER BY u.lastname ASC, u.firstname ASC";
        
        // Execute the query
        $sql = $sql_select . $sql_from . $sql_where . $sql_order;
        
        try {
            $students = $DB->get_records_sql($sql, $params_sql);
            
            // Process results to add sports information
            $results = [];
            foreach ($students as $student) {
                // Get sports for each student
                $sports = self::get_student_sports($student->id);
                
                $student->sports = $sports;
                $results[] = $student;
            }
            
            return ['success' => true, 'results' => array_values($results)];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get sports that a student is enrolled in
     * 
     * @param int $studentid User ID of the student
     * @return array Array of sport objects with id, code, and name
     */
    public static function get_student_sports($studentid) {
        global $DB;
        
        $sql = "SELECT s.id, s.code, s.name 
                FROM {enrol_wds_students_meta} sm
                JOIN {enrol_wds_sport} s ON sm.data = s.code
                WHERE sm.studentid = :studentid 
                AND sm.datatype = 'Athletic_Team_ID'
                ORDER BY s.name ASC";
        
        return $DB->get_records_sql($sql, ['studentid' => $studentid]);
    }
    
    /**
     * Get access permissions for a user
     * 
     * @param @int $userid User ID
     * @return @array Array
     */
    public static function get_user_access($userid) {
        global $DB, $USER;
        
        $access = [
            'sports' => [],
            'student_ids' => [],
            'all_students' => false
        ];
        
        // Check if user is in the hardcoded list of allowed users
        $allowed_users = [
            'rrusso33',
        ];
        
        // Admin can access all students
        if ($USER->username == 'admin') {
            $access['all_students'] = true;
            return $access;
        }
        
        // Check if user is in the hardcoded list
        if (in_array($USER->username, $allowed_users)) {
            $access['all_students'] = true;
            return $access;
        }
        
        // Get sports that the user has access to through the mentors table
        $sports_mentors = $DB->get_records('enrol_sports_mentors', ['userid' => $userid]);
        foreach ($sports_mentors as $mentor) {
            $access['sports'][] = $mentor->path; // path is sport code
        }
        
        // Get specific students that the user has access to
        $person_mentors = $DB->get_records('enrol_person_mentors', ['userid' => $userid]);
        foreach ($person_mentors as $mentor) {
            $access['student_ids'][] = $mentor->path; // path is student userid
        }
        
        return $access;
    }
}
