<?php

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
 * OBU Metalinking - service functions
 * @package   local_obu_metalinking
 * @author    Emir Kamel
 * @copyright 2024, Oxford Brookes University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the web service functions to install.
$functions = array(
    'local_get_teaching_course_id_number' => array(
        'classname'   => 'local_obu_metalinking',
        'methodname'  => 'get_teaching_course_id_number',
        'classpath'   => 'local/obu_metalinking/externallib.php',
        'description' => 'Takes in a course ID number and returns the parent course ID number',
        'type'        => 'read',
        'capabilities'=> ''
    )
);

// Define the services to install as pre-build services.
$services = array(
    'OBU Metalinking' => array(
        'shortname' => 'obu_metalinking',
        'functions' => array(
            'local_obu_metalinking_get_teaching_course_id_number'
        ),
        'restrictedusers' => 1,
        'enabled' => 1
    )
);
