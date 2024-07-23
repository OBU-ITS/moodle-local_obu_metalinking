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
 * OBU Application - Database upgrade
 *
 * @package    obu_metalinking
 * @category   local
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
global $CFG;
require_once($CFG->dirroot.'/group/lib.php');

function xmldb_local_obu_metalinking_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2024072201) {

        $sql = "SELECT g.id
                FROM {groups} g
                JOIN {course} c ON c.id = g.courseid
                JOIN {course_categories} cat ON cat.id = c.category AND cat.idnumber NOT LIKE 'SRS%'
                WHERE g.idnumber LIKE 'obuSys.%'";

        $results = $DB->get_records_sql($sql, array(ENROL_INSTANCE_ENABLED));

        foreach ($results as $result) {
            groups_delete_group($result->id);
        }

        $sql = "SELECT gm.groupid,
                       gm.userid
                FROM {groups_members} gm
                JOIN {groups} g ON gm.groupid = g.id AND g.idnumber LIKE 'obuSys.%'
                JOIN {role_assignments} ra ON ra.userid = gm.userid AND ra.roleid <> 5
                JOIN {context} ctx ON ctx.id = ra.contextid AND c.instanceid = g.courseid AND ctx.contextlevel = 50";

        $results = $DB->get_records_sql($sql);

        foreach ($results as $result) {
            groups_remove_member($result->groupid, $result->userid);
        }

        upgrade_plugin_savepoint(true, 2024072201, 'local', 'obu_metalinking');
    }

    return $result;
}