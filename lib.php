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

defined('MOODLE_INTERNAL') || die();

function local_obu_metalinking_get_teaching_course(object $course) : object {
    global $DB;

    $courses = local_obu_metalinking_get_teaching_course_ids($course->id);
    if(count($courses) == 0) {
        return $course;
    }

    $course_id = local_obu_metalinking_determine_course_id($courses, $course->id);

    return $DB->get_record('course', array('id' => $course_id));
}

function local_obu_metalinking_is_metalinked_course(int $course_id) : bool {
    global $DB;

    $sql = "SELECT 1
            FROM {enrol} e
            JOIN {course} parent ON parent.id = e.courseid AND parent.shortname LIKE '% (%:%)' AND parent.idnumber LIKE '%.%'
            JOIN {course_categories} parentcat ON parentcat.id = parent.category AND parentcat.idnumber LIKE 'SRS%'
            JOIN {course} child ON child.id = e.customint1 AND child.shortname LIKE '% (%:%)' AND child.idnumber LIKE '%.%'
            JOIN {course_categories} childcat ON childcat.id = child.category AND childcat.idnumber LIKE 'SRS%'
            WHERE e.enrol = 'meta'
                AND e.status = ?
                AND (e.customint1 = ? OR e.courseid = ?)";

    $results = $DB->get_records_sql($sql, array(ENROL_INSTANCE_ENABLED, $course_id, $course_id));

    return count($results) > 0;
}


function local_obu_metalinking_get_teaching_course_id(string $course_id) : string {
    $courses = local_obu_metalinking_get_teaching_course_ids($course_id);

    return local_obu_metalinking_determine_course_id($courses, $course_id);
}

function local_obu_metalinking_determine_course_id(array $courses, string $course_id) : string {
    foreach ($courses as $course) {
        $course_id = $course->id;
    }

    return $course_id;
}

function local_obu_metalinking_get_teaching_course_ids(int $course_id) : array {
    global $DB;

    $sql = "SELECT DISTINCT parent.id
            FROM {enrol} e
            JOIN {course} parent ON parent.id = e.courseid
            JOIN {course_categories} cat ON cat.id = parent.category AND cat.idnumber LIKE 'SRS%'
            WHERE e.enrol = 'meta'
               AND e.status = ?
               AND e.customint1 = ?
               AND parent.shortname LIKE '% (%:%)'
               AND parent.idnumber LIKE '%.%'";

    return $DB->get_records_sql($sql, array(ENROL_INSTANCE_ENABLED, $course_id));
}

function local_obu_metalinking_get_all_teaching_course_ids() : array {
    global $DB;

    $sql = "SELECT DISTINCT parent.id
            FROM {enrol} e
            JOIN {course} parent ON parent.id = e.courseid
            JOIN {course_categories} cat ON cat.id = parent.category AND cat.idnumber LIKE 'SRS%'
            WHERE e.enrol = 'meta'
               AND e.status = ?
               AND parent.shortname LIKE '% (%:%)'
               AND parent.idnumber LIKE '%.%'";

    return $DB->get_records_sql($sql, array(ENROL_INSTANCE_ENABLED));
}
