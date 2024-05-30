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
 * Plugin event observers
 *
 * @package    local_metagroups
 * @copyright  2014 Paul Holden <paulh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\enrol_instance_created',
        'callback'  => 'local_obu_metalinking_observer::enrol_instance_created',
    ],

    [
        'eventname' => '\core\event\enrol_instance_deleted',
        'callback'  => 'local_obu_metalinking_observer::enrol_instance_deleted',
    ],

    [
        'eventname' => '\core\event\group_created',
        'callback'  => 'local_obu_metalinking_observer::group_created',
    ],

    [
        'eventname' => '\core\event\group_updated',
        'callback'  => 'local_obu_metalinking_observer::group_updated',
    ],

    [
        'eventname' => '\core\event\group_deleted',
        'callback'  => 'local_obu_metalinking_observer::group_deleted',
    ],

    [
        'eventname' => '\core\event\group_member_added',
        'callback'  => 'local_obu_metalinking_observer::group_member_added',
    ],

    [
        'eventname' => '\core\event\group_member_removed',
        'callback'  => 'local_obu_metalinking_observer::group_member_removed',
    ],

    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => 'local_obu_metalinking_observer::user_enrolment_created',
    ],
];
