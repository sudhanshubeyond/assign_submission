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
];
