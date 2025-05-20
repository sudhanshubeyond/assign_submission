<?php
namespace local_assign_submission\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class externalcustomlib extends external_api {
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
}
