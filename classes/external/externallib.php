<?php
namespace local_assign_submission\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
use context_module;
use moodle_exception;

class externallib extends external_api {
    public static function getfile_parameters() {
        return new external_function_parameters([
            'fileid' => new external_value(PARAM_INT, 'The ID of the file to fetch'),
        ]);
    }
 
    public static function getfile($fileid) {
        global $USER;

        self::validate_parameters(self::getfile_parameters(), ['fileid' => $fileid]);

        $fs = get_file_storage();
        $file = $fs->get_file_by_id($fileid);

        if (!$file || $file->is_directory()) {
            throw new moodle_exception('filenotfound', 'error', '', null, "File ID: $fileid");
        }

        // Optional: check access permissions (e.g. file belongs to user)
        $context = $file->get_contextid();
        if (!has_capability('moodle/user:viewdetails', \context::instance_by_id($context), $USER)) {
            throw new required_capability_exception(\context::instance_by_id($context), 'moodle/user:viewdetails', 'nopermissions', '');
        }

        $content = $file->get_content();
        $base64 = base64_encode($content);

        return [
            'filename' => $file->get_filename(),
            'mimetype' => $file->get_mimetype(),
            'content' => $base64,
        ];
    }

    public static function getfile_returns() {
        return new external_single_structure([
            'filename' => new external_value(PARAM_FILE, 'File name'),
            'mimetype' => new external_value(PARAM_TEXT, 'MIME type'),
            'content' => new external_value(PARAM_RAW, 'Base64-encoded file content'),
        ]);
    }

    public static function course_details_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function course_details($courseid) {
        global $DB;

        $params = self::validate_parameters(self::course_details_parameters(), ['courseid' => $courseid]);

        require_login($courseid);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);

        $response = [
            'coursename' => $course->fullname,
            'coursedescription' => strip_tags(format_text($course->summary, $course->summaryformat, ['context' => $context])),
            'sections' => []
        ];

        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if ($section->section == 0 && empty($section->name) && empty($section->summary)) {
                continue;
            }

            $sectiondata = [
                'sectionid' => $section->id,
                'sectionname' => $section->name ?? '',
                'sectiondescription' => strip_tags(format_text($section->summary, $section->summaryformat, ['context' => $context])),
                'modules' => []
            ];

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if (!$cm->uservisible) {
                        continue;
                    }

                    $modulecontext = context_module::instance($cm->id);

                    $fileids = [];
                    $filenames = [];

                    $files = $DB->get_records('files', [
                        'contextid' => $modulecontext->id,
                        'itemid' => $cm->instance
                    ]);

                    if (empty($files)) {
                        $files = $DB->get_records('files', ['contextid' => $modulecontext->id]);
                    }

                    foreach ($files as $file) {
                        if ($file->filename !== '.') {
                            $fileids[] = $file->id;
                            $filenames[] = $file->filename;
                        }
                    }

                    // Get external URL for url module type
                    $externalurl = '';
                    if ($cm->modname === 'url') {
                        $urlrecord = $DB->get_record('url', ['id' => $cm->instance], 'externalurl', IGNORE_MISSING);
                        if ($urlrecord && !empty($urlrecord->externalurl)) {
                            $externalurl = $urlrecord->externalurl;
                        }
                    }

                    $sectiondata['modules'][] = [
                        'moduleid' => $cm->id,
                        'moduletype' => $cm->modname,
                        'modulename' => $cm->name,
                        'moduledescription' => isset($cm->content) ? strip_tags($cm->content) : '',
                        'fileid' => implode(',', $fileids),
                        'filename' => implode(',', $filenames),
                        'externalurl' => $externalurl
                    ];
                }
            }

            $response['sections'][] = $sectiondata;
        }

        return $response;
    }
    
    public static function course_details_returns() {
        return new external_single_structure([
            'coursename' => new external_value(PARAM_TEXT, 'Course full name'),
            'coursedescription' => new external_value(PARAM_TEXT, 'Course summary'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'sectionid' => new external_value(PARAM_INT, 'Section ID'),
                    'sectionname' => new external_value(PARAM_TEXT, 'Section name'),
                    'sectiondescription' => new external_value(PARAM_TEXT, 'Section description'),
                    'modules' => new external_multiple_structure(
                        new external_single_structure([
                            'moduleid' => new external_value(PARAM_INT, 'Module type'),
                            'moduletype' => new external_value(PARAM_TEXT, 'Module type'),
                            'modulename' => new external_value(PARAM_TEXT, 'Module name'),
                            'moduledescription' => new external_value(PARAM_RAW, 'Module description'),
                            'fileid' => new external_value(PARAM_TEXT, 'Comma-separated file IDs'),
                            'filename' => new external_value(PARAM_TEXT, 'Comma-separated filenames'),
                            'externalurl' => new external_value(PARAM_URL, 'External URL if module is of type URL', VALUE_OPTIONAL),
                        ])
                    )
                ])
            )
        ]);
    }

    // No parameters expected, because you'll read raw JSON from php://input
    public static function insert_graderesponse_parameters() {
        return new external_function_parameters([]);
    }

    public static function insert_graderesponse() {
        global $DB, $CFG;

        // Get raw POST data
        $rawdata = file_get_contents('php://input');
        if (!$rawdata) {
            throw new \moodle_exception('No input data received');
        }

        // Decode JSON
        $data = json_decode($rawdata, true);
        if ($data === null) {
            throw new \moodle_exception('Invalid JSON data');
        }

        // Define expected parameters for validation
        $expectedparams = [
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'status' => new external_value(PARAM_INT, 'Status (0 = not graded, 1 = graded)'),
            'grade' => new external_value(PARAM_RAW, 'Grade'),
            'feedbackdesc' => new external_value(PARAM_RAW, 'Feedback description'),
            'rubricbreakdown' => new external_multiple_structure(
                new external_single_structure([
                    'criterionid' => new external_value(PARAM_INT, 'Criterion ID'),
                    'selectedlevelid' => new external_value(PARAM_INT, 'Selected Level ID'),
                    'marksawarded' => new external_value(PARAM_FLOAT, 'Marks Awarded'),
                    'feedback' => new external_value(PARAM_TEXT, 'Criterion Feedback')
                ]),
                'Rubric Breakdown'
            ),
            'errormessage' => new external_value(PARAM_RAW, 'Error Message', VALUE_OPTIONAL),
        ];

        // Validate decoded JSON data
        $params = self::validate_parameters(new external_function_parameters($expectedparams), $data);

        // Get course module id for the assignment
        $modinfo = get_fast_modinfo($params['courseid']);
        $cmid = 0;
        foreach ($modinfo->cms as $cm) {
            if ($cm->modname === 'assign' && $cm->instance == $params['assignmentid']) {
                $cmid = $cm->id;
                break;
            }
        }

        // Validation for grade as integer
        if (!isset($params['grade']) || !is_numeric($params['grade']) || intval($params['grade']) != $params['grade']) {
            throw new \moodle_exception('The grade must be an integer value.');
        }

        if (!$cmid) {
            throw new \moodle_exception('Course module ID not found for the given assignment');
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        
        // Validate context and capability
        $context = context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        // Fetch assignment record
        $assignment = $DB->get_record('assign', ['id' => $params['assignmentid']], 'id, teacher_approval', MUST_EXIST);
        
        $assign = new \assign($context, $cm, $cm->course);
         
        $transaction = $DB->start_delegated_transaction();

        if ((int)$assignment->teacher_approval === 0) {
            // Initialize the validateddata object
            $validateddata = new \stdClass();

            // Check if rubricbreakdown is empty
            if (empty($params['rubricbreakdown'])) {
                // Assign grade and feedback comments
                $validateddata->grade = $params['grade'];
                $validateddata->assignfeedbackcomments_editor = [
                    'text' => $params['feedbackdesc'],
                    'format' => 1,
                ];

                $validateddata->editpdf_source_userid = $params['userid'];
                $validateddata->id = $cmid;
                $validateddata->rownum = 0;
                $validateddata->useridlistid = null;
                $validateddata->attemptnumber = -1;
                $validateddata->ajax = 0;
                $validateddata->userid = $params['userid'];
                $validateddata->sendstudentnotifications = 1;
                $validateddata->action = 'submitgrade';

            } else {
                $grade = $assign->get_user_grade($userid, true, -1);

                $gradingdisabled = $assign->grading_disabled($params['userid']);       

                $gradinginstance = self::get_grading_instance($params['userid'], $grade, $gradingdisabled, $assign, $context);
                // If rubricbreakdown is not empty, create the detailed validateddata structure with rubric breakdown
                $validateddata->advancedgrading = [];
                $validateddata->advancedgrading['criteria'] = [];

                foreach ($params['rubricbreakdown'] as $criterion) {
                    $validateddata->advancedgrading['criteria'][$criterion['criterionid']] = [
                        'levelid' => $criterion['selectedlevelid'],
                        'remark' => $criterion['feedback']
                    ];
                }

                $validateddata->advancedgradinginstanceid = $gradinginstance->get_id();
                $validateddata->assignfeedbackcomments_editor = [
                    'text' => $params['feedbackdesc'],
                    'format' => 1,
                ];

                $validateddata->editpdf_source_userid = $params['userid'];
                $validateddata->id = $cmid;
                $validateddata->rownum = 0;
                $validateddata->useridlistid = null;
                $validateddata->attemptnumber = -1;
                $validateddata->ajax = 0;
                $validateddata->userid = $params['userid'];
                $validateddata->sendstudentnotifications = 1;
                $validateddata->action = 'submitgrade';
            }
            try {
                $assign->save_grade($params['userid'], $validateddata);
                $transaction->allow_commit();
            } catch (Exception $e) {
                $transaction->rollback($e->getMessage());
                throw new \moodle_exception('Error saving grade: ' . $e->getMessage());
            }

            return ['status' => true, 'message' => 'Grade saved and gradebook updated.', 'graderesponseid' => 0
            ];
        } else {
            // Insert or update graderesponse in DB if teacher approval is 1
            $record = new \stdClass();
            $record->userid = $params['userid'];
            $record->courseid = $params['courseid'];
            $record->submissionid = $params['submissionid'];
            $record->assignmentid = $params['assignmentid'];
            $record->cmid = $cmid;
            $record->status = $params['status'];
            $record->grade = $params['grade'];
            $record->feedbackdesc = $params['feedbackdesc'];
            $record->errormessage = isset($params['errormessage']) ? $params['errormessage'] : NULL;
            $record->timemodified = time();
            $record->rubricbreakdown = json_encode($params['rubricbreakdown']);

            $existing = $DB->get_record('assign_graderesponse', ['submissionid' => $params['submissionid']]);

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('assign_graderesponse', $record);
                $message = 'Graderesponse updated successfully.';
            } else {
                $record->timecreated = time();
                $record->id = $DB->insert_record('assign_graderesponse', $record);
                $message = 'Graderesponse inserted successfully.';
            }

            $transaction->allow_commit();

            return ['status' => true, 'message' => $message, 'graderesponseid' => $record->id];
        }
    }

    public static function insert_graderesponse_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True if grading succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Result or warning message'),
            'graderesponseid' => new external_value(PARAM_INT, 'Record ID'),
        ]);
    }
    
    public static function get_grading_instance($userid, $grade, $gradingdisabled, $assign, $context) {
        global $CFG, $USER;

        $grademenu = make_grades_menu($assign->get_instance($userid)->grade);

        $allowgradedecimals = $assign->get_instance()->grade > 0;

        $advancedgradingwarning = false;
        $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
        $gradinginstance = null;
        if ($gradingmethod = $gradingmanager->get_active_method()) {
            $controller = $gradingmanager->get_controller($gradingmethod);
            if ($controller->is_form_available()) {
                $itemid = null;
                if ($grade) {
                    $itemid = $grade->id;
                }
                if ($gradingdisabled && $itemid) {
                    $gradinginstance = $controller->get_current_instance($USER->id, $itemid);
                } else if (!$gradingdisabled) {
                    $instanceid = optional_param('advancedgradinginstanceid', 0, PARAM_INT);
                    $gradinginstance = $controller->get_or_create_instance($instanceid,
                                                                           $USER->id,
                                                                           $itemid);
                }
            } else {
                $advancedgradingwarning = $controller->form_unavailable_notification();
            }
        }
        if ($gradinginstance) {
            $gradinginstance->get_controller()->set_grade_range($grademenu, $allowgradedecimals);
        }
        
        return $gradinginstance;
    }
}
