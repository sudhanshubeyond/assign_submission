<?php

$functions = [
    'local_assign_submission_getfile' => [
        'classname'   => 'local_assign_submission\\external\\externallib',
        'methodname'  => 'getfile',
        'classpath'   => '', // Not needed when using autoloading
        'description' => 'Send file content to external API by file ID',
        'type'        => 'write',
        'ajax'        => false,
        'capabilities'=> '',
    ],
    'local_assign_submission_get_course_details' => [
        'classname'   => 'local_assign_submission\\external\\externallib',
        'methodname'  => 'course_details',
        'classpath'   => '',
        'description' => 'Returns course name, summary, and file details',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'moodle/course:view'
    ],
];
