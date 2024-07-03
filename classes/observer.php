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

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB;
require_once($CFG->dirroot . '/local/obu_metalinking/locallib.php');
require_once($CFG->dirroot . '/local/obu_group_manager/lib.php');

/**
 * Event observers
 *
 * @package    local_obu_metalinking (from local_metagroups)
 * @copyright  2024 Joe Souch (from 2014 Paul Holden <paulh@moodle.com>)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_obu_metalinking_observer {

    /**
     * Enrol instance created
     *
     * @param \core\event\enrol_instance_created $event
     * @return void
     */
    public static function enrol_instance_created(\core\event\enrol_instance_created $event) {
        $instance = $event->get_record_snapshot('enrol', $event->objectid);

        if (strcasecmp($instance->enrol, 'meta') == 0) {
            $course = get_course($instance->courseid);

            // Return early if course doesn't use groups.
            if (groups_get_course_groupmode($course) == NOGROUPS) {
                return;
            }

            // Immediate synchronization could be expensive, defer to adhoc task.
            $task = new \local_obu_metalinking\task\synchronize();
            $task->set_custom_data(['courseid' => $course->id]);

            \core\task\manager::queue_adhoc_task($task);
        }
    }

    /**
     * Enrol instance deleted
     *
     * @param \core\event\enrol_instance_deleted $event
     * @return void
     */
    public static function enrol_instance_deleted(\core\event\enrol_instance_deleted $event) {
        global $DB;

        $instance = $event->get_record_snapshot('enrol', $event->objectid);

        if (strcasecmp($instance->enrol, 'meta') == 0) {
            $course = get_course($instance->courseid);

            // Get groups from linked course, and delete them from current course.
            $groups = groups_get_all_groups($instance->customint1);
            foreach ($groups as $group) {
                if(!local_obu_group_manager_is_system_group($group->idnumber)) {
                    continue;
                }

                if ($metagroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $group->idnumber])) {
                    groups_delete_group($metagroup);
                }
            }
        }
    }

    /**
     * Group created
     *
     * @param \core\event\group_created $event
     * @return void
     */
    public static function group_created(\core\event\group_created $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        if(!local_obu_group_manager_is_system_group($group->idnumber)) {
            return;
        }

        $courseids = local_obu_metalinking_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            local_obu_group_manager_create_system_group($course, $group->name, $group->idnumber);
        }
    }

    /**
     * Group updated
     *
     * @param \core\event\group_updated $event
     * @return void
     */
    public static function group_updated(\core\event\group_updated $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        if(!local_obu_group_manager_is_system_group($group->idnumber)) {
            return;
        }

        $courseids = local_obu_metalinking_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $group->idnumber])) {
                $metagroup->name = $group->name;

                groups_update_group($metagroup);
            }
        }
    }

    /**
     * Group deleted
     *
     * @param \core\event\group_deleted $event
     * @return void
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        if(!local_obu_group_manager_is_system_group($group->idnumber)) {
            return;
        }

        $courseids = local_obu_metalinking_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $group->idnumber])) {
                groups_delete_group($metagroup);
            }
        }
    }

    /**
     * Group member added
     *
     * @param \core\event\group_member_added $event
     * @return void
     */
    public static function group_member_added(\core\event\group_member_added $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        if(!local_obu_group_manager_is_system_group($group->idnumber)) {
            return;
        }

        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_obu_metalinking_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $group->idnumber])) {
                groups_add_member($metagroup, $user, "local_obu_metalinking", $group->id);
            }
        }
    }

    /**
     * Group member removed
     *
     * @param \core\event\group_member_removed $event
     * @return void
     */
    public static function group_member_removed(\core\event\group_member_removed $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        if(!local_obu_group_manager_is_system_group($group->idnumber)) {
            return;
        }

        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_obu_metalinking_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $group->idnumber])) {
                groups_remove_member($metagroup, $user);
            }
        }
    }

    /**
     * User enrolment created
     *
     * @param \core\event\user_enrolment_created $event
     * @return bool
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        if($event->other['enrol'] === 'meta') {
            return;
        }

        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);
        $group = local_obu_metalinking_get_all_group($event->courseid);

        groups_add_member($group, $user);

        return true;
    }

    private static function write_to_log(string $message) {
        global $DB;

        $obj = new stdClass();
        $obj->time = time();
        $obj->userid = 1;
        $obj->ip = "";
        $obj->course = 1;
        $obj->module = "";
        $obj->cmid = 1;
        $obj->ation = "test";
        $obj->url = "/";
        $obj->info = $message;

        $DB->insert_record("log", $obj);
    }
}
