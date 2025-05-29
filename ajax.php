<?php 
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_login();

$action = required_param('action', PARAM_ALPHA);
// Basic security: check user capabilities
$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/assign:view', $context);

switch ($action) {
    case 'mycustomaction':
        // Do something
        $response = ['status' => 'ok', 'message' => 'It worked'];
        break;
    default:
        $response = ['status' => 'error', 'message' => 'Invalid action'];
        break;
}

echo json_encode($response);
die;
