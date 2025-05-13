/**
 * JavaScript for handling grade display
 *
 * @module     block_sportsgrades/grade_display
 * @package    block_sportsgrades
 * @copyright  2025 Onwards - Robert Russo
 * @copyright  2025 Onwards - Louisiana State University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {get_string as getString} from 'core/str';

/**
 * Initialize the grade display functionality
 */
export const init = () => {
    registerEventListeners();
};

/**
 * Register event listeners
 */
const registerEventListeners = () => {
    // Course selection in the grade display
    $(document).on('click', '.sportsgrades-course-link', function(e) {
        e.preventDefault();
        
        // Mark this course as active
        $('.sportsgrades-course-item').removeClass('active');
        $(this).closest('.sportsgrades-course-item').addClass('active');
        
        // Get the course data from the data attribute
        const courseData = $(this).data('course');
        
        // Display grade items for this course
        displayGradeItems(courseData);
    });
    
    // Back to search results
    $(document).on('click', '#sportsgrades-back-to-results', function(e) {
        e.preventDefault();
        
        // Hide grade display and show search results
        $('#sportsgrades-grade-display').hide();
        $('#sportsgrades-search-results').show();
        
        // Update URL to remove studentid parameter
        const url = new URL(window.location);
        url.searchParams.delete('studentid');
        window.history.pushState({}, '', url);
    });
};

/**
 * Load grades for a student
 * 
 * @param {Number} studentId Student ID
 */
export const loadGrades = (studentId) => {
    const $gradeDisplay = $('#sportsgrades-grade-display');
    const $searchResults = $('#sportsgrades-search-results');
    
    // Show loading indicator
    getString('grade_loading', 'block_sportsgrades').then(str => {
        $gradeDisplay.html(`<div class="sportsgrades-loading">${str}</div>`);
        $gradeDisplay.show();
        $searchResults.hide();
        return;
    }).catch(Notification.exception);
    
    // Make AJAX call to get grades
    Ajax.call([{
        methodname: 'block_sportsgrades_get_student_grades',
        args: { studentid: studentId },
        done: function(response) {
            displayGrades(response, studentId);
        },
        fail: Notification.exception
    }]);
};

/**
 * Display grades
 * 
 * @param {Object} response Response from the server
 * @param {Number} studentId Student ID
 */
const displayGrades = (response, studentId) => {
    const $gradeDisplay = $('#sportsgrades-grade-display');
    
    // Check if there was an error
    if (response.error) {
        $gradeDisplay.html(`<div class="alert alert-danger">${response.error}</div>`);
        return;
    }
    
    // Check if there are courses
    if (!response.courses || response.courses.length === 0) {
        getString('grade_no_courses', 'block_sportsgrades').then(str => {
            $gradeDisplay.html(`
                <div class="alert alert-info">${str}</div>
                <div class="text-center mt-3">
                    <button id="sportsgrades-back-to-results" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> 
                        <span class="back-text"></span>
                    </button>
                </div>
            `);
            
            // Set back button text
            getString('grade_back_to_results', 'block_sportsgrades').then(str => {
                $('.back-text').text(str);
                return;
            }).catch(Notification.exception);
            
            return;
        }).catch(Notification.exception);
        return;
    }
    
    // Get student name for the title
    let studentName = '';
    if (response.student && response.student.lastname && response.student.firstname) {
        studentName = `${response.student.lastname}, ${response.student.firstname}`;
    } else {
        studentName = `Student ID: ${studentId}`;
    }
    
    // Render grades using template
    Templates.render('block_sportsgrades/grade_display', {
        student: {
            id: studentId,
            name: studentName
        },
        courses: response.courses
    }).then((html, js) => {
        $gradeDisplay.html(html);
        Templates.runTemplateJS(js);
        
        // Display the first course's grade items by default
        if (response.courses.length > 0) {
            $('.sportsgrades-course-item:first').addClass('active');
            displayGradeItems(response.courses[0]);
        }
        
        return;
    }).catch(Notification.exception);
};

/**
 * Display grade items for a course
 * 
 * @param {Object} courseData Course data including grade items
 */
const displayGradeItems = (courseData) => {
    const $gradeItemsContainer = $('#sportsgrades-grade-items');
    
    // Check if there are grade items
    if (!courseData.grade_items || courseData.grade_items.length === 0) {
        getString('grade_no_items', 'block_sportsgrades').then(str => {
            $gradeItemsContainer.html(`<div class="alert alert-info">${str}</div>`);
            return;
        }).catch(Notification.exception);
        return;
    }
    
    // Render grade items using template
    Templates.render('block_sportsgrades/grade_items', {
        course: courseData,
        grade_items: courseData.grade_items
    }).then((html, js) => {
        $gradeItemsContainer.html(html);
        Templates.runTemplateJS(js);
        return;
    }).catch(Notification.exception);
};
