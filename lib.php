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
 * @category   local
 * @copyright  2024, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function get_teaching_course(object $course) : object {
    global $DB;

    $courses = get_teaching_course_ids($course->id);
    if(count($courses) == 0) {
        return $course;
    }

    $course_id = determine_course_id($courses, $course->id);

    return $DB->get_record('course', array('id' => $course_id));
}


function get_teaching_course_id(string $course_id) : string {
    $courses = get_teaching_course_ids($course_id);

    return determine_course_id($courses, $course_id);
}

function determine_course_id(array $courses, string $course_id) : string {
    foreach ($courses as $course) {
        $course_id = $course->id;
    }

    return $course_id;
}

function get_teaching_course_ids(int $course_id) : array {
    global $DB;

    $sql = 'SELECT parent.id FROM {enrol} e'
        . ' JOIN {course} parent ON parent.id = e.courseid'
        . ' WHERE e.enrol = "meta"'
        . '   AND e.customint1 = ?'
        . '   AND parent.shortname LIKE "% (%:%)"'
        . '   AND parent.idnumber LIKE "%.%"'
        . '   AND parent.visible = 1';

    return $DB->get_records_sql($sql, array($course_id));
}