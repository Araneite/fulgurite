<?php

return [
    'subtitle' => 'Confirm your identity to continue.',
    'fields' => [
        'method' => 'Verification method',
        'code' => 'Verification code',
    ],
    'methods'=> [
        "one_time_code"=> "One time code",
        "email"=> "Email",
        "passkey"=> "Passkey",
    ],
    'email_sent_to' => 'Code sent to',
    'confirm_button' => 'Confirm',
    'passkey_button' => 'Use a security key',
    'resend_email_code' => 'Resend code',
    'email_code_sent' => 'A new verification code has been sent.',
    'errors' => [
        'invalid_code' => 'The verification code is invalid.',
        'invalid_passkey' => 'The access key can\'t be verified.',
    ],
    'success'=> [
        'success'=> [
            'totp_enabled' => 'The one time code 2FA is enabled.',
            'totp_disabled' => 'The one time code 2FA is disabled.',
        ]
    ]
];
