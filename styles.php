/**
 * Styles for the Sports Grades block and search page
 */

/* Common styles */
.sportsgrades-loading {
    text-align: center;
    padding: 20px;
}

.sportsgrades-advanced-fields {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.sportsgrades-course-list {
    max-height: 400px;
    overflow-y: auto;
}

.sportsgrades-course-item {
    cursor: pointer;
}

.sportsgrades-course-item:hover {
    background-color: #f8f9fa;
}

.sportsgrades-course-item.active {
    background-color: #e9ecef;
}

.sportsgrades-course-link {
    display: block;
    color: inherit;
    text-decoration: none;
}

.sportsgrades-course-link:hover {
    text-decoration: none;
}

/* Block-specific styles */
.block_sportsgrades .sportsgrades-block-button {
    margin: 10px 0;
}

/* Page-specific styles */
.path-blocks-sportsgrades .sportsgrades-search-container {
    margin-bottom: 20px;
}

.path-blocks-sportsgrades .sportsgrades-search-results,
.path-blocks-sportsgrades .sportsgrades-grade-display {
    margin-top: 20px;
}

.path-blocks-sportsgrades .sportsgrades-grade-container {
    margin-top: 20px;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .path-blocks-sportsgrades .table-responsive {
        overflow-x: auto;
    }
}
