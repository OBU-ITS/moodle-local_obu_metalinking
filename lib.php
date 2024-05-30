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
const METALINKING_GROUPING_IDENTIFIER = 'obuSystem';
const METALINKING_GROUPING_NAME = 'OBU System';

function local_obu_metalinking_get_teaching_course(object $course) : object {
    global $DB;

    $courses = local_obu_metalinking_get_teaching_course_ids($course->id);
    if(count($courses) == 0) {
        return $course;
    }

    $course_id = local_obu_metalinking_determine_course_id($courses, $course->id);

    return $DB->get_record('course', array('id' => $course_id));
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

function local_obu_metalinking_get_group_idnumber(string $course_idnumber) : string {
    return 'ML.' . $course_idnumber;
}

function local_obu_metalinking_get_teaching_course_ids(int $course_id) : array {
    global $DB;

    $sql = "SELECT id, parent.id
            FROM {enrol} e
            JOIN {course} parent ON parent.id = e.courseid
            WHERE e.enrol = 'meta'
               AND e.status = ?
               AND e.customint1 = ?
               AND parent.shortname LIKE '% (%:%)'
               AND parent.idnumber LIKE '%.%'";

    return $DB->get_records_sql_menu($sql, array(ENROL_INSTANCE_ENABLED, $course_id));
}

function local_obu_metalinking_get_all_teaching_course_ids() : array {
    global $DB;

    $sql = "SELECT id, parent.id
            FROM {enrol} e
            JOIN {course} parent ON parent.id = e.courseid
            WHERE e.enrol = 'meta'
               AND e.status = ?
               AND parent.shortname LIKE '% (%:%)'
               AND parent.idnumber LIKE '%.%'";

    return $DB->get_records_sql_menu($sql, array(ENROL_INSTANCE_ENABLED));
}

function local_obu_metalinking_get_all_nonmeta_enrolled_users($courseid) : array {
    global $DB;

    $sql = "SELECT DISTINCT u.*
                    FROM {enrol} e 
                    JOIN {user_enrolments} ue ON e.id = ue.enrolid
                    JOIN {user} u ON u.id = ue.userid
                    WHERE e.enrol <> 'meta'
                        AND e.courseid = ?";

    return $DB->get_records_sql($sql, [$courseid]);
}

/**
 * Create a new group with the course's name.
 *
 * @param int $courseid
 * @param int $linkedcourseid
 * @return int $groupid Group ID for this cohort.
 */
function local_obu_metalinking_create_new_group(int $course_id, int $linked_course_id) {
    global $DB, $CFG;

    $metalinked_course = $DB->get_record('course', array('id' => $linked_course_id), 'shortname, idnumber', MUST_EXIST);

    $a = new stdClass();
    $a->name = $metalinked_course->shortname;

    // Create a new group for the course meta sync.
    $groupdata = new stdClass();
    $groupdata->courseid = $course_id;
    $groupdata->name = trim(get_string('defaultgroupnametext', 'local_obu_metalinking', $a));
    $groupdata->idnumber = local_obu_metalinking_get_group_idnumber($metalinked_course->idnumber);

    require_once($CFG->dirroot.'/group/lib.php');

    $groupdata->id = groups_create_group($groupdata);

    local_obu_metalinking_add_group_to_grouping($groupdata);
}

function local_obu_metalinking_update_group(int $group_id, int $course_id, int $linked_course_id) {
    global $DB, $CFG;

    $metalinked_course = $DB->get_record('course', array('id' => $linked_course_id), 'shortname, idnumber', MUST_EXIST);

    $a = new stdClass();
    $a->name = $metalinked_course->shortname;

    $groupdata = new stdClass();
    $groupdata->id = $group_id;
    $groupdata->courseid = $course_id;
    $groupdata->name = trim(get_string('defaultgroupnametext', 'local_obu_metalinking', $a));
    $groupdata->idnumber = local_obu_metalinking_get_group_idnumber($metalinked_course->idnumber);

    require_once($CFG->dirroot.'/group/lib.php');

    groups_update_group($groupdata);
}

function local_obu_metalinking_create_parent_group(int $course_id, string $course_idnumber, string $course_shortname) {
    global $CFG;

    $a = new stdClass();
    $a->name = $course_shortname;

    // Create a new group for the course meta sync.
    $groupdata = new stdClass();
    $groupdata->courseid = $course_id;
    $groupdata->name = trim(get_string('defaultteachinggroupnametext', 'local_obu_metalinking', $a));
    $groupdata->idnumber = local_obu_metalinking_get_group_idnumber($course_idnumber);

    require_once($CFG->dirroot.'/group/lib.php');

    $groupdata->id = groups_create_group($groupdata);

    local_obu_metalinking_add_group_to_grouping($groupdata);

    return $groupdata->id;
}

function local_obu_metalinking_add_group_to_grouping($group) {
    global $DB;

    if (!($grouping = $DB->get_record('groupings_groups', array('courseid'=>$group->courseId, 'idnumber'=>METALINKING_GROUPING_IDENTIFIER)))) {

        $grouping = new stdClass();
        $grouping->name = METALINKING_GROUPING_NAME;
        $grouping->courseid = $group->courseId;
        $grouping->idnumber = METALINKING_GROUPING_IDENTIFIER;

        $grouping->id = groups_create_grouping($grouping);
    }

    groups_assign_grouping($grouping->id, $group->id);
}