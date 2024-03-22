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

function get_metalinking_enrolment_group_idnumber(string $course_idnumber) : string {
    return 'ML.' . $course_idnumber .'';
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

/**
 * Create a new group with the course's name.
 *
 * @param int $courseid
 * @param int $linkedcourseid
 * @return int $groupid Group ID for this cohort.
 */
function obu_metalinking_create_new_group(int $course_id, int $linked_course_id) {
    global $DB, $CFG;

    $metalinked_course = $DB->get_record('course', array('id' => $linked_course_id), 'shortname, idnumber', MUST_EXIST);

    $a = new stdClass();
    $a->name = $metalinked_course->shortname;

    // Create a new group for the course meta sync.
    $groupdata = new stdClass();
    $groupdata->courseid = $course_id;
    $groupdata->name = trim(get_string('defaultgroupnametext', 'local_obu_metalinking', $a));
    $groupdata->idnumber = get_metalinking_enrolment_group_idnumber($metalinked_course->idnumber);

    require_once($CFG->dirroot.'/group/lib.php');

    groups_create_group($groupdata);
}

function obu_metalinking_update_group(int $group_id, int $course_id, int $linked_course_id) {
    global $DB, $CFG;

    $metalinked_course = $DB->get_record('course', array('id' => $linked_course_id), 'shortname, idnumber', MUST_EXIST);

    $a = new stdClass();
    $a->name = $metalinked_course->shortname;

    $groupdata = new stdClass();
    $groupdata->id = $group_id;
    $groupdata->courseid = $course_id;
    $groupdata->name = trim(get_string('defaultgroupnametext', 'local_obu_metalinking', $a));;
    $groupdata->idnumber = get_metalinking_enrolment_group_idnumber($metalinked_course->idnumber);

    require_once($CFG->dirroot.'/group/lib.php');

    groups_update_group($groupdata);
}