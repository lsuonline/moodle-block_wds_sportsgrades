/**
 * JavaScript for handling student search
 *
 * @module     block_sportsgrades/search
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
import * as GradeDisplay from 'block_sportsgrades/grade_display';

/**
 * Initialize the search functionality
 */
export const init = () => {
    registerEventListeners();
    toggleAdvancedSearch();
    
    // If URL has a studentid parameter, directly load that student
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('studentid');
    if (studentId) {
        GradeDisplay.loadGrades(parseInt(studentId));
    }
};

/**
 * Register event listeners
 */
const registerEventListeners = () => {
    // Search form submission
    $(document).on('submit', '#sportsgrades-search-form', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Advanced search toggle
    $(document).on('click', '#sportsgrades-advanced-toggle', function(e) {
        e.preventDefault();
        toggleAdvancedSearch();
    });
    
    // Clicking on a student in the results
    $(document).on('click', '.sportsgrades-view-grades', function(e) {
        e.preventDefault();
        const studentId = $(this).data('student-id');
        GradeDisplay.loadGrades(studentId);
        
        // Update URL with student ID without refreshing the page
        const url = new URL(window.location);
        url.searchParams.set('studentid', studentId);
        window.history.pushState({}, '', url);
    });
};

/**
 * Toggle advanced search fields
 */
const toggleAdvancedSearch = () => {
    const $advancedFields = $('.sportsgrades-advanced-fields');
    const $toggle = $('#sportsgrades-advanced-toggle');
    
    if ($advancedFields.is(':visible')) {
        $advancedFields.hide();
        getString('search_advanced', 'block_sportsgrades').then(str => {
            $toggle.text(str);
            return;
        }).catch(Notification.exception);
    } else {
        $advancedFields.show();
        getString('search_advanced_hide', 'block_sportsgrades').then(str => {
            $toggle.text(str);
            return;
        }).catch(err => {
            // If the string doesn't exist, use a default
            $toggle.text('Hide Advanced Search');
        });
    }
};

/**
 * Perform the search operation
 */
const performSearch = () => {
    const $form = $('#sportsgrades-search-form');
    const $results = $('#sportsgrades-search-results');
    
    // Show loading indicator
    $results.html('<div class="sportsgrades-loading">Searching...</div>');
    
    // Get form data
    const formData = {
        universal_id: $form.find('[name="universal_id"]').val(),
        username: $form.find('[name="username"]').val(),
        firstname: $form.find('[name="firstname"]').val(),
        lastname: $form.find('[name="lastname"]').val(),
        major: $form.find('[name="major"]').val(),
        classification: $form.find('[name="classification"]').val(),
        sport: $form.find('[name="sport"]').val(),
    };
    
    // Make AJAX call to search
    Ajax.call([{
        methodname: 'block_sportsgrades_search_students',
        args: { params: formData },
        done: function(response) {
            displaySearchResults(response);
        },
        fail: Notification.exception
    }]);
};

/**
 * Display search results
 * 
 * @param {Object} response Response from the server
 */
const displaySearchResults = (response) => {
    const $results = $('#sportsgrades-search-results');
    
    // Hide grade display when showing new search results
    $('#sportsgrades-grade-display').hide();
    
    // Check if there was an error
    if (response.error) {
        $results.html(`<div class="alert alert-danger">${response.error}</div>`);
        return;
    }
    
    // Check if there are results
    if (!response.success || !response.results || response.results.length === 0) {
        getString('search_no_results', 'block_sportsgrades').then(str => {
            $results.html(`<div class="alert alert-info">${str}</div>`);
            return;
        }).catch(Notification.exception);
        return;
    }
    
    // Render results using template
    Templates.render('block_sportsgrades/search_results', {
        results: response.results
    }).then((html, js) => {
        $results.html(html);
        Templates.runTemplateJS(js);
        $results.show();
        return;
    }).catch(Notification.exception);
};
