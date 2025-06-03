<?php

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_login();

$action = required_param('action', PARAM_ALPHA);
$cmid = required_param('cmid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

global $DB;
$data = $DB->get_record('assign_graderesponse', array('userid' => $userid, 'cmid' => $cmid));

if(!empty($data)){
switch ($action) {
case 'getgrades':
$response = ['status' => 200, 'grade' => $data->grade, 'feedback' => $data->feedbackdesc];
break;
default:
$response = ['status' => 'error', 'message' => 'Invalid action'];
break;
}
}else {
    $response = ['status' => 404];
}

echo json_encode($response);
die;
