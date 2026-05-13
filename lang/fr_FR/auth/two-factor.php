<?php

return [
    'subtitle' => 'Confirmez votre identité pour continuer.',
    'fields' => [
        'method' => 'Méthode de vérification',
        'code' => 'Code de vérification',
    ],
    'methods'=> [
        "one_time_code"=> "Code à usage unique"
    ],
    'email_sent_to' => 'Code envoyé à',
    'confirm_button' => 'Confirmer',
    'passkey_button' => 'Utiliser la clé d’accès',
    'resend_email_code' => 'Renvoyer le code',
    'email_code_sent' => 'Un nouveau code a été envoyé.',
    'errors' => [
        'invalid_code' => 'Le code de vérification est invalide.',
        'invalid_passkey' => 'La clé d’accès n’a pas pu être vérifiée.',
    ],
    'success'=> [
        'totp_enabled' => 'Le code à usage unique est activé.',
        'totp_disabled' => 'Le code à usage unique est désactivé.',
    ]
];
