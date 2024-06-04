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
 * Plugin local library methods
 *
 * @package    local_obu_metalinking (from local_metagroups)
 * @copyright  2024 Joe Souch (from 2014 Paul Holden <paulh@moodle.com>)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/local/obu_metalinking/lib.php');
require_once($CFG->dirroot . '/local/obu_group_manager/lib.php');

/**
 * Get a list of parent courses for a given course ID
 *
 * @param int|null $courseid or null for all parents
 * @return array of course IDs
 */
function local_obu_metalinking_parent_courses($courseid = null) {
    $courseidobjs = ($courseid !== null)
        ? local_obu_metalinking_get_teaching_course_ids($courseid)
        : local_obu_metalinking_get_all_teaching_course_ids();

    return array_map(function($n) { return $n->id; }, $courseidobjs);
}

/**
 * Get a list of all child courses for a given course ID
 *
 * @param int $courseid
 * @return array of course IDs
 */
function local_obu_metalinking_child_courses($courseid) {
    global $DB;

    $conditions = ['enrol' => 'meta', 'courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED];

    return $DB->get_records_menu('enrol', $conditions, 'sortorder', 'id, customint1');
}

/**
 * Run synchronization process
 *
 * @param progress_trace $trace
 * @param int|null $courseid or null for all courses
 * @return void
 */
function local_obu_metalinking_sync(progress_trace $trace, $courseid = null) {
    global $DB;

    if ($courseid !== null) {
        $courseids = [$courseid];
    } else {
        $courseids = local_obu_metalinking_parent_courses();
    }

    foreach ($courseids as $courseid) {
        $parent = get_course($courseid);

        $trace->output($parent->fullname, 1);

        $parentgrooupall = local_obu_metalinking_get_all_group($courseid);
        $parentnonmetaenrolments = local_obu_metalinking_get_all_nonmeta_enrolled_students($courseid);
        foreach ($parentnonmetaenrolments as $user) {
            groups_add_member($parentgrooupall->id, $user->id, 'local_obu_metalinking');
        }


        $children = local_obu_metalinking_child_courses($parent->id);
        foreach ($children as $childid) {
            $childgrooupall = local_obu_metalinking_get_all_group($childid);
            $childnonmetaenrolments = local_obu_metalinking_get_all_nonmeta_enrolled_students($childid);
            foreach ($childnonmetaenrolments as $user) {
                groups_add_member($childgrooupall->id, $user->id, 'local_obu_metalinking');
            }

            $child = get_course($childid);
            $trace->output($child->fullname, 2);

            $groups = groups_get_all_groups($child->id);
            foreach ($groups as $group) {
                if(!local_obu_group_manager_is_system_group($group->idnumber)) {
                    continue;
                }

                if (! $metagroup = $DB->get_record('groups', ['courseid' => $parent->id, 'idnumber' => $group->idnumber])) {
                    $metagroup = new stdClass();
                    $metagroup->courseid = $parent->id;
                    $metagroup->idnumber = $group->idnumber;
                    $metagroup->name = $group->name;

                    $metagroup->id = groups_create_group($metagroup, false, false);

                    local_obu_group_manager_link_system_grouping($metagroup);
                }

                $trace->output($metagroup->name, 3);

                $users = groups_get_members($group->id);
                foreach ($users as $user) {
                    groups_add_member($metagroup->id, $user->id, 'local_obu_metalinking', $group->idnumber);
                }
            }
        }
    }
}

function local_obu_metalinking_get_all_group($courseid) {

    $course = get_course($courseid);

    return local_obu_group_manager_create_system_group($course);
}
