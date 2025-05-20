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
                        'moduletype' => $cm->modname,
                        'modulename' => $cm->name,
                        'moudlesecription' => isset($cm->content) ? strip_tags($cm->content) : '',
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
                            'moduletype' => new external_value(PARAM_TEXT, 'Module type'),
                            'modulename' => new external_value(PARAM_TEXT, 'Module name'),
                            'moudlesecription' => new external_value(PARAM_RAW, 'Module description'),
                            'fileid' => new external_value(PARAM_TEXT, 'Comma-separated file IDs'),
                            'filename' => new external_value(PARAM_TEXT, 'Comma-separated filenames'),
                            'externalurl' => new external_value(PARAM_URL, 'External URL if module is of type URL', VALUE_OPTIONAL),
                        ])
                    )
                ])
            )
        ]);
    }
}
