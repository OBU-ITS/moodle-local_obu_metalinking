<?php

/**
 * @package    local_obu_metalinking
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_obu_metalinking\task;

defined('MOODLE_INTERNAL') || die();

/**
 * An example of a scheduled task.
 */
class ensure_meta_enrol_group extends \core\task\scheduled_task
{
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string
    {
        return "Ensure meta linked enrolment groups";
    }

    /**
     * Execute the task.
     */
    public function execute() {
        $trace = new \text_progress_trace();

        $this->ensure_groups_exist($trace);
        $this->ensure_group_idnumber_format($trace);

    }

    private function ensure_groups_exist($trace) {
        global $DB, $CFG;

        $sql = 'SELECT e.* '
            . 'FROM {enrol} e '
            . 'JOIN {course} parent ON parent.id = e.courseid '
            . 'LEFT JOIN {groups} g ON g.id = e.customint2 '
            . 'WHERE e.enrol = "meta" '
            .   'AND parent.shortname LIKE "% (%:%)" '
            .   'AND parent.idnumber LIKE "%.%" '
            .   'AND parent.visible = 1 '
            .   'AND g.id is null';

        $records = $DB->get_records_sql($sql);

        $records_count = count($records);
        if($records_count == 0) {
            $trace->output("No groups require creating");
            return;
        }

        $trace->output("$records_count require groups creating");

        require_once($CFG->libdir . '/enrollib.php');
        $plugin = enrol_get_plugin('meta');

        require_once($CFG->dirroot . '/local/obu_metalinking/lib.php');
        foreach($records as $instance) {
            $data = new \stdClass();

            $groupid = obu_metalinking_create_new_group($instance->courseid, $instance->customint1);
            $data->customint2 = $groupid;

            $plugin->update_instance($instance, $data);
        }
    }

    private function ensure_group_idnumber_format($trace) {
        global $DB, $CFG;

        $sql = 'SELECT e.customint1, e.courseid, g.id '
            . 'FROM {enrol} e '
            . 'JOIN {groups} g ON g.id = e.customint2 '
            . 'WHERE g.idnumber NOT LIKE "ML.%"';

        $records = $DB->get_records_sql($sql);

        $records_count = count($records);
        if($records_count == 0) {
            $trace->output("No groups require updating");
            return;
        }

        $trace->output("$records_count require groups updating");

        require_once($CFG->dirroot . '/local/obu_metalinking/lib.php');
        foreach($records as $record) {
            obu_metalinking_update_group($record->id, $record->courseid, $record->customint1);
        }
    }
}