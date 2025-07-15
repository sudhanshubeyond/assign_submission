<?php

require_once(__DIR__ . '/../../config.php');

use core_course\customfield\course_handler;

function submission_event_data($event, $type = 'submitted') {
    global $DB;
    try {
        // Get basic info
        $context = $event->get_context(); // context_module
        $courseid = $event->courseid;
        $userid = $event->userid;
        $user  = core_user::get_user($userid);


        $fieldshortname = 'ai_required'; // Replace with your field shortname
        $value = get_course_custom_field_value($courseid, $fieldshortname);
        if ($value != '' && $value == 1) {

            // Get course module ID from context
            $cmid = $context->instanceid;

            // Get assign ID from course_modules
            $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
            $assignid = $cm->instance;
            $assign = $DB->get_record('assign', ['id' => $assignid], 'Name, intro, grade');

            $submissionid = $event->objectid;

            if ($type == 'submitted') {

                // Get online text (if used)
                $online_text = '';
                if ($submission) {
                    $plugin_text = $DB->get_record('assignsubmission_onlinetext', ['submission' => $submissionid], 'onlinetext', IGNORE_MISSING);
                    if ($plugin_text) {
                        $online_text = $plugin_text->onlinetext;
                    }
                }

                // Get uploaded file IDs (if file submission is enabled)
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                        $context->id,
                        'assignsubmission_file',
                        'submission_files',
                        $submissionid,
                        "itemid, filepath, filename",
                        false
                );
                $fileids = [];
                foreach ($files as $file) {
                    $fileids[] = $file->get_id();
                }

                $fileIDs = implode(',', $fileids);

                // Get rubric/guide data (if available)
                $gradingdata = '';
                $gradingmethod = '';
                if ($assignid) {
                    // Get the assignment's context and CM
                    $cm = get_coursemodule_from_instance('assign', $assignid, 0, false, MUST_EXIST);
                    $context = context_module::instance($cm->id);

                    // Use grading manager to access the rubric/guide controller
                    $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
                    $gradingmethod = $gradingmanager->get_active_method();
                    if ($gradingmanager->get_active_method()) {
                        $controller = $gradingmanager->get_controller($gradingmethod);
                        if ($controller && $controller->is_form_defined()) {
                            $gradingdata = $controller->get_definition();
                        }
                    } else {
                        $gradingmethod = '';
                    }
                }

                $previoussubmissions = get_previous_submisisons($userid, $assignid);
                $fieldshortname = 'indexing_required'; // Replace with your field shortname
                $courseindexing =     get_course_custom_field_value($courseid, $fieldshortname);

                $data = [
                    'submissionID' => $submissionid,
                    'assignmentID' => $assignid,
                    'assignmentName' => $assign->name,
                    'assignmentDesc' => $assign->intro,
                    'assignmentMaxScore' => $assign->grade,
                    'userAssignentText' => $online_text,
                    'userID' => $user->id,
                    'studentName' => $user->firstname,
                    'courseID' => $courseid,
                    'fileIDs' => $fileIDs,
                    'rubricID' => '',
                    'GradingType' => $gradingmethod,
                    'GradingData' => ($gradingdata) ? json_encode($gradingdata) : '',
                    'indexingFlag' => ($courseindexing == 1) ? true : false,
                    'previousSubmissions' => $previoussubmissions,
                ];

                $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitAssignmentAsync';
                $response = execute_curl_postapi($data, $endpoint);

                $record = new stdClass();
                $record->userid = $userid;
                $record->courseid = $courseid;
                $record->submissionid = $submissionid;
                $record->assignmentid = $assignid;
                $record->grade = " ";
                $record->cmid = $cmid;
                $record->feedbackdesc = '';
                $record->fileids = $fileIDs;
                $record->status = ($response->status) ? 1 : 0;
                $record->timemodified = $timecreated = time();
                $graderrow = $DB->get_record('assign_graderesponse', ['userid' => $userid, 'assignmentid' => $assignid, 'submissionid' => $submissionid], '*', IGNORE_MISSING);

                if (empty($graderrow)) {
                    $oldsubmissionid = $DB->get_field('assign_graderesponse', 'submissionid', ['userid' => $userid, 'assignmentid' => $assignid, 'isdeleted' => 0]);
                    if ($oldsubmissionid) {
                        $data = [
                            'submissionId' => $oldsubmissionid,
                            'userID' => $userid
                        ];
                        $sql = "UPDATE {assign_graderesponse} SET isdeleted = 1 where submissionid = $oldsubmissionid";
                        $DB->execute($sql);
                        $response = execute_curl_deleteapi($data);
                    }
                    $record->timecreated = $timecreated;
                    $DB->insert_record('assign_graderesponse', $record);
                } else {
                    $record->id = $graderrow->id;
                    $record->isdeleted = 0;
                    $DB->update_record('assign_graderesponse', $record);
                }
            } else {

                $data = [
                    'submissionId' => $submissionid,
                    'userID' => $userid
                ];
                $response = execute_curl_deleteapi($data);
                $graderrow = $DB->get_record('assign_graderesponse', ['userid' => $userid, 'assignmentid' => $assignid, 'submissionid' => $submissionid], '*', IGNORE_MISSING);
                if (!empty($graderrow)) {
                    $record = new stdClass();
                    $record->id = $graderrow->id;
                    $record->isdeleted = 1;
                    $record->timemodified = time();
                    $record->status = ($response->status) ? 1 : 0;
                    $DB->update_record('assign_graderesponse', $record);
                }
            }
        }
    } catch (Exception $e) {
        // Catch unexpected exceptions
        debugging('Unexpected error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

function execute_curl_postapi($data, $endpoint = '') {
    $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitAssignmentAsync';

    $headers = get_genapi_headers();

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    return $response;
}

function execute_curl_deleteapi($data, $endpoint = '') {


    $userID = $data['userID'];
    $submissionId = $data['submissionId'];
    $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/DeleteGradingRequest?submissionId=' . $submissionId . '&userID=' . $userID;

    $headers = get_genapi_headers();

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    return $response;
}

function get_course_custom_field_value($courseid, $fieldshortname) {
    $handler = course_handler::create();
    $data = $handler->get_instance_data($courseid);
    foreach ($data as $fielddata) {
        if ($fielddata->get_field()->get('shortname') === $fieldshortname) {
            return $fielddata->get_value();
        }
    }
    return null; // Return null if field not found
}

function show_ai_grading($cmid, $userid) {
    global $DB;
    //$data = $DB->get_record('assign_graderesponse', array('userid' => $userid, 'cmid' => $cmid, 'isdeleted' => 0));


    $sql = "SELECT * FROM {assign_graderesponse}
        WHERE userid = :userid AND cmid = :cmid AND isdeleted = 0 AND grade IS NOT NULL AND grade <> ''";

    $params = [
        'userid' => $userid,
        'cmid' => $cmid
    ];

    $data = $DB->get_record_sql($sql, $params);

    if (!empty($data)) {
        $status = true;
    } else {
        $status = false;
    }

    return $status;
}

function ai_grade_exist($cmid, $userid) {
    global $DB;

    $sql = "SELECT * FROM {assign_graderesponse}
        WHERE userid = :userid AND cmid = :cmid AND isdeleted = 0 and status = 1";

    $params = [
        'userid' => $userid,
        'cmid' => $cmid
    ];

    $status = $DB->record_exists_sql($sql, $params);

    return $status;
}

function get_genapi_headers() {

    $apikey = get_config('local_assign_submission', 'api_keys');
    $headers = [
        "x-api-key: $apikey",
        "Content-Type: application/json"
    ];

    return $headers;
}

function execute_curl_putapi($courseid) {
    if (empty($courseid)) {
        debugging("Error: courseId is empty.", DEBUG_DEVELOPER);
        return null;
    }

    $params = ['courseId' => $courseid];
    $endpoint = get_config('local_assign_submission', 'update_course_sync_end_point');
    $url = new moodle_url($endpoint, $params);

    $headers = get_genapi_headers();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $responseRaw = curl_exec($ch);

    if (curl_errno($ch)) {
        debugging("cURL PUT error: " . curl_error($ch), DEBUG_DEVELOPER);
    }

    curl_close($ch);
    $response = json_decode($responseRaw);

    if (isset($response->errors)) {
        debugging("API error response: " . json_encode($response), DEBUG_DEVELOPER);
    } else {
        debugging("API response success: " . json_encode($response), DEBUG_DEVELOPER);
    }

    return $response;
}

function get_previous_submisisons($userid, $assignid) {
    global $DB;

    $sql = "SELECT id,submissionid
        FROM {assign_graderesponse} 
        WHERE userid = :userid 
          AND assignmentid = :assignid 
          AND isdeleted = 1 
          AND grade IS NOT NULL 
          AND grade != ''";

    $params = [
        'userid' => $userid,
        'assignid' => $assignid,
    ];

    $previoussubmissionids = $DB->get_records_sql($sql, $params);

    $previoussubmissions = [];
    foreach ($previoussubmissionids as $previoussubmissionids) {
        $previoussubmissions[] = $previoussubmissionids->submissionid;
    }

    $previoussubmissions = implode(',', $previoussubmissions);
    return $previoussubmissions;
}

