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
 * @package    local_obu_metalinking
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version = 2024100901;
$plugin->requires = 2012120301;
$plugin->component = 'local_obu_metalinking';
$plugin->maturity = MATURITY_STABLE;

$plugin->release = 'v2.1.2';
$plugin->dependencies = array(
    'enrol_meta' => 2022112800,
    'local_obu_group_manager' => 2024072902,
    'local_obu_attendance_events' => 2024100901,
    'local_obu_metalinking_events' => 2024100901
);