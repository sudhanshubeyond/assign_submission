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
       'eventname' => '\mod_assign\event\submission_created',
       'includefile' => '/local/assign_submission/locallib.php',
       'callback' => 'submission_created',
       'internal' => false,
   ), 
    array(
       'eventname' => '\mod_assign\event\submission_updated',
       'includefile' => '/local/assign_submission/locallib.php',
       'callback' => 'submission_updated',
       'internal' => false,
   ), 
    array(
       'eventname' => '\mod_assign\event\submission_removed',
       'includefile' => '/local/assign_submission/locallib.php',
       'callback' => 'submission_removed',
       'internal' => false,
   ), 
);
