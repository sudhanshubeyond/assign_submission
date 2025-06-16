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
 * @package   local_custom_events
 * @copyright  Sudhanshu Gupta<sudhanshu.gupta@beyondkey.com>
 */
defined('MOODLE_INTERNAL') || die();

$observers = array(
   array(
       'eventname' => '\mod_assign\event\assessable_submitted',
       'includefile' => '/local/assign_submission/locallib.php',
       'callback' => 'assessable_submitted',
       'internal' => false,
   ), 
   array(
       'eventname' => '\mod_assign\event\submission_removed',
       'includefile' => '/local/assign_submission/locallib.php',
       'callback' => 'submission_removed',
       'internal' => false,
   ), 
    // Course lifecycle events.
    array(
        'eventname'   => '\core\event\course_created',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_updated',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_deleted',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_content_deleted',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_module_created',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_module_updated',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_module_deleted',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    // Section events.
    array(
        'eventname'   => '\core\event\course_section_created',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_section_updated',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\core\event\course_section_deleted',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    // Assignment events
    array(
        'eventname'   => '\mod_assign\event\assignment_created',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_assign\event\assignment_updated',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_assign\event\assignment_deleted',
        'includefile' => '/local/assign_submission/locallib.php',
        'callback'    => 'local_assign_submission_handle_event',
        'internal'    => false,
    ),

);
