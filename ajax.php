<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_login();

$action = required_param('action', PARAM_ALPHA);
$cmid = required_param('cmid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

global $DB;
$sql = "SELECT * FROM {assign_graderesponse}
        WHERE userid = :userid AND cmid = :cmid AND isdeleted = 0 AND status = 1";

$params = [
    'userid' => $userid,
    'cmid' => $cmid,
];

$data = $DB->get_record_sql($sql, $params);
$errormessage = ($data->errormessage == '') ? NULL : $data->errormessage;
if (!empty($data)) {
    switch ($action) {
        case 'getgrades':
            $response = ['status' => 200, 'grade' => $data->grade, 'feedback' => $data->feedbackdesc, 'rubricbreakdown' => $data->rubricbreakdown,'errormessage' => $errormessage];
            break;
        default:
            $response = ['status' => 'error', 'message' => 'Invalid action'];
            break;
    }
} else {
    $response = ['status' => 404];
}

echo json_encode($response);
die;
