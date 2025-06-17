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
 *
 * @package   local_assign_submission
 * @copyright  Sudhanshu Gupta<sudhanshu.gupta@beyondkey.com>
 */
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');
require_once($CFG->dirroot . '/grade/grading/form/rubric/lib.php');
require_once($CFG->dirroot . '/local/assign_submission/lib.php');

function assessable_submitted(\mod_assign\event\assessable_submitted $event) {
    $response = submission_event_data($event, 'submitted');
}

function submission_removed(\mod_assign\event\submission_removed $event) {
    $response = submission_event_data($event, 'delete');
}

function local_assign_submission_handle_event(\core\event\base $event) {
    try {
        $courseid = $event->courseid ?? 0;

        if (!$courseid) {
            debugging("No course ID found in event: {$event->eventname}", DEBUG_DEVELOPER);
            return;
        }

        $response = execute_curl_putapi($courseid);

        if (isset($response->status) && $response->status === true) {
            debugging("Successfully updated sync status for courseId: {$courseid}", DEBUG_DEVELOPER);
        } else {
            debugging("Failed to update sync status for courseId: {$courseid}", DEBUG_DEVELOPER);      
        }

    } catch (Exception $e) {
        debugging("Error in event {$event->eventname}: " . $e->getMessage(), DEBUG_DEVELOPER);
        write_to_log("Error in event {$event->eventname}: " . $e->getMessage());
    }
}

//end
?>