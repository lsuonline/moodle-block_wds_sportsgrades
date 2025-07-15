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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('block_wds_sportsgrades_settings', get_string('pluginname', 'block_wds_sportsgrades'));
    $ADMIN->add('root', $settings);

    $url = new moodle_url('/blocks/wds_sportsgrades/admin.php');
    $setting = new admin_setting_heading('block_wds_sportsgrades_manageaccess', '',
        html_writer::link($url, get_string('manageaccess', 'block_wds_sportsgrades')));
    $settings->add($setting);
}
