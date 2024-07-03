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

/*
 * OBU metalinking - external library
 *
 * @package    local_obu_metalinking
 * @author     Emir Kamel
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/local/obu_metalinking/locallib.php");

class local_obu_metalinking extends external_api {
    public static function get_teaching_course_id_number_parameters() {
        return new external_function_parameters(
            array(
                'idnumber' => new external_value(PARAM_TEXT, 'Course ID number')
            )
        );
    }

    public static function get_teaching_course_id_number_returns() {
        return new external_single_structure(
            array(
                'teachingidnumber' => new external_value(PARAM_TEXT, 'Teaching Course ID number')
            )
        );
    }

    public static function get_teaching_course_id_number($idnumber){
        // Context validation
        self::validate_context(context_system::instance());

        // Parameter validation
        $params = self::validate_parameters(
            self::get_teaching_course_id_number_parameters(), array(
                'idnumber' => $idnumber
            )
        );

        if (strlen($params['idnumber']) < 1) {
            return array('result' => -1);
        }

        $teachingIdNumber = local_obu_metalinking_get_teaching_course_id_number($idnumber);

        return array('teachingidnumber' => $teachingIdNumber);
    }
}