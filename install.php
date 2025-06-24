<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Installation code for the plugin
 *
 * @package   local_assign_submission
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install the plugin
 *
 * @return void
 */
function xmldb_local_assign_submission_install() {
    global $DB;

    $dbman = $DB->get_manager();

    // Define the table and field to be added during installation
    $table = new xmldb_table('assign');

    // Add 'teacher_approval' field if it doesn't exist
    $field_teacher_approval = new xmldb_field('teacher_approval', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'submissionattachments');
    if (!$dbman->field_exists($table, $field_teacher_approval)) {
        $dbman->add_field($table, $field_teacher_approval);
    }

    // Add 'ai_grading' field if it doesn't exist
    $field_ai_grading = new xmldb_field('ai_grading', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'teacher_approval');
    if (!$dbman->field_exists($table, $field_ai_grading)) {
        $dbman->add_field($table, $field_ai_grading);
    }

    // Installation is complete
}
