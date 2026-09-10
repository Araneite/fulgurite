<?php

return [
    'title'=> "Confirmer votre identité",
    'subtitle'=> "Cette action nécessite une confirmation de sécuríté.",
    
    'fields'=> [
        'password'=> "Mot de passe",
        'method'=> "Méthode de vérification",
        'code'=> "Code de vérification",
    ],
    
    'methods'=> [
        'email'=> "Email",
        'one_time_code'=> "Application d'authentification",
        'passkey'=> "Clé d'accès"
    ],
    
    'actions'=> [
        'confirm'=> "Confirmer",
        'passkey'=> "Confirmer avec une clé d'accès",
        'resend_email_code'=> "Renvoyer le code",
        'cancel'=> "Annuler",
    ],
    
    'messages'=> [
        'email_sent_to'=> "Un code a été envoyé à : ",
        'email_code_sent'=> "Un nouveau code a été envoyé."
    ],
    
    'errors'=> [
        'invalid_code'=> "Le code de vérification est invalide.",
        'invalid_passkey'=> "La clé d'accès est invalide.",
        'method_unavailable_title'=> "Méthode de vérification indisponible",
        'method_unavailable'=> "Cette méthode de vérification n'est pas disponible pour votre compte.",
        'passkey_unavailable'=> "La confirmation par clé d'accès n'est pas disponible pour votre compte. Vérifiez qu'une clé d'accès est bien configurée.",
    ]
];
