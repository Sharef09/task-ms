<?php

return [
    'host'       => getenv('MAIL_HOST') ?: '',
    'port'       => getenv('MAIL_PORT') ?: 587,
    'username'   => getenv('MAIL_USER') ?: '',
    'password'   => getenv('MAIL_PASS') ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: '',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'Task Management System',
];
