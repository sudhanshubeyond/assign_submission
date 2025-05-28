<?php

require_once(__DIR__ . '/../../config.php');

function submission_event_data($event, $type='submitted') {
    global $DB;

    try {
        // Get basic info
        $context = $event->get_context(); // context_module
        $courseid = $event->courseid;
        $userid = $event->userid;

        // Get course module ID from context
        $cmid = $context->instanceid;

        // Get assign ID from course_modules
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        $assignid = $cm->instance;
        $assign = $DB->get_record('assign', ['id' => $assignid], 'Name, intro, grade');

        $submission = $DB->get_record('assign_submission', [
            'assignment' => $assignid,
            'userid' => $userid
        ]);
        $submissionid = $submission->id;

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

            // Get rubric data (if available)
            $rubric_data = null;
            if ($assignid) {
                // Get the assignment's context and CM
                $cm = get_coursemodule_from_instance('assign', $assignid, 0, false, MUST_EXIST);
                $context = context_module::instance($cm->id);

                // Use grading manager to access the rubric controller
                $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
                $controller = $gradingmanager->get_controller('rubric');

                if ($controller && $controller->is_form_defined()) {
                    $rubric_data = $controller->get_definition();
                }
            }

            $data = [
                'submissionID' => $submissionid,
                'assignmentID' => $assignid,
                'assignmentName' => $assign->name,
                'assignmentDesc' => $assign->intro,
                'assignmentMaxScore' => $assign->grade,
                'userAssignentText' => $online_text,
                'userID' => $userid,
                'courseID' => $courseid,
                'fileIDs' => $fileIDs,
                'rubricID' => '',
                'rubricData' => json_encode($rubric_data),
                'indexingFlag' => false
            ];

            $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitAssignmentAsync';
            $response = execute_curl_postapi($data, $endpoint);

            $record = new stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->submissionid = $submissionid;
            $record->assignmentid = $assignid;
            $record->grade = $assign->grade;
            $record->cmid = $cmid;
            $record->feedbackdesc = '';
            $record->status = $response->status;
            $record->timemodified = $timecreated = time();
            $graderrow = $DB->get_record('assign_graderesponse', ['userid' => $userid, 'assignmentid' => $assignid, 'submissionid' => $submissionid], '*', IGNORE_MISSING);

            if (empty($graderrow)) {
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
    } catch (Exception $e) {
        // Catch unexpected exceptions
        debugging('Unexpected error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

function execute_curl_postapi($data, $endpoint= '') {
    $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/SubmitAssignmentAsync';

    $headers = [
        'x-api-key: 123456',
        'Content-Type: application/json'
    ];
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    return $response;
}

function execute_curl_deleteapi($data, $endpoint= '') {
    
    // $base_url = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/DeleteGradingRequest';
    // $endpoint = "'".$base_url.'?'.$data."'";

    $userID = $data['userID'];
    $submissionId = $data['submissionId'];
    $endpoint = 'https://genai-woodmontcollege-app.azurewebsites.net/api/StudentGrading/DeleteGradingRequest?submissionId='.$submissionId.'&userID='.$userID;

    $headers = [
        'x-api-key: 123456',
        'Content-Type: application/json'
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = json_decode(curl_exec($ch));
    curl_close($ch);

    return $response;
}