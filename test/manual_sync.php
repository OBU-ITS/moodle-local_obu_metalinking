<?php

/**
 * Example URL : /local/obu_metalinking/test/manual_sync.php?courseid=7
 */
require('../../../config.php');

global $CFG;

$courseid = required_param('courseid', PARAM_INT);

require_once($CFG->dirroot.'/local/obu_metalinking/locallib.php');

$trace = new \text_progress_trace();

local_obu_metalinking_sync($trace, $courseid);

$trace->finished();