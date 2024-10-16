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

use local_obu_metalinking_events\event\metalinking_groups_created;
use local_obu_metalinking_events\event\metalinking_groups_deleted;
use local_obu_attendance_events\event\attendance_sessions_restored;

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
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

        $instance = $event->get_record_snapshot('enrol', $event->objectid);

        if (strcasecmp($instance->enrol, 'meta') != 0) {
            return;
        }

        $parentid = $instance->courseid;
        $childid = $instance->customint1;

        // Immediate synchronization could be expensive, defer to adhoc task.
        $task = new \local_obu_metalinking\task\synchronize();
        $task->set_custom_data(['courseid' => $parentid]);

        \core\task\manager::queue_adhoc_task($task);

        $groupsCreatedEvent = metalinking_groups_created::create_from_metalinked_courses($childid, $parentid);
        $groupsCreatedEvent->trigger();
    }

    /**
     * Enrol instance deleted
     *
     * @param attendance_sessions_restored $event
     * @return void
     */
    public static function attendance_sessions_restored(attendance_sessions_restored $event) {
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

        global $DB;

        $parentid = $event->other['parentid'];
        $childid = $event->other['childid'];

        // Get groups from linked course, and delete them from current course.
        $groups = groups_get_all_groups($childid);
        foreach ($groups as $group) {
            if(!local_obu_group_manager_is_system_group($group->idnumber)) {
                continue;
            }

            if ($metagroup = $DB->get_record('groups', ['courseid' => $parentid, 'idnumber' => $group->idnumber])) {
                groups_delete_group($metagroup);
            }
        }

        $groupsDeletedEvent = metalinking_groups_deleted::create_from_metalinked_courses($childid, $parentid);
        $groupsDeletedEvent->trigger();
    }

    /**
     * Group created
     *
     * @param \core\event\group_created $event
     * @return void
     */
    public static function group_created(\core\event\group_created $event) {
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

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
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

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
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

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
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

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
        $enabled = get_config('local_obu_metalinking', 'enableevents');
        if(!$enabled) {
            return;
        }

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
}
