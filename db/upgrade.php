<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the plugin
 *
 * @package   local_assign_submission
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin instance
 *
 * @param int $oldversion The old version of the plugin
 * @return bool
 */
function xmldb_local_assign_submission_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Check if the plugin is at a version lower than 2025050502
    if ($oldversion < 2025050502) {

        // Define the table and field to be modified
        $table = new xmldb_table('assign');
        $field = new xmldb_field('teacher_approval', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'submissionattachments');

        // Conditionally launch add field teacher_approval
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mark this upgrade step as complete
        upgrade_plugin_savepoint(true, 2025050502, 'local', 'assign_submission');
    }

    if ($oldversion < 2025050504) {

        $table = new xmldb_table('assign');
        // Define the field for ai_grading
        $field_ai_grading = new xmldb_field('ai_grading', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'teacher_approval');

        // Conditionally launch add field ai_grading
        if (!$dbman->field_exists($table, $field_ai_grading)) {
            $dbman->add_field($table, $field_ai_grading);
        }

        // Mark this upgrade step as complete and update the version to 2025050504
        upgrade_plugin_savepoint(true, 2025050504, 'local', 'assign_submission');
    }

    return true;
}
