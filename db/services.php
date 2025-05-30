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
    'local_assign_submission_insert_graderesponse' => [
        'classname'   => 'local_assign_submission\\external\\externallib',
        'methodname'  => 'insert_graderesponse',
        'classpath'   => '',
        'description' => 'Insert a record into mdl_assign_graderesponse table',
        'type'        => 'write',
        'ajax'        => false,
        'capabilities'=> 'moodle/grade:manage'
    ],  
];
