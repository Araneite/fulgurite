<?php

return [
    'title'=> "Confirmer votre identité",
    "subtitle"=> "Cette action est une action sensible, vous devez confirmer votre identité pour pouvoir la réaliser.",
    'email_sent_to'=> "Email envoyé à :",
    "actions"=> [
        "confirm_button"=> "Confirmer",
        "passkey_button"=> "S'authentifier avec un clé d'accès",
        "resend_email_code"=> "Renvoyer le mail"
    ],
    "fields"=> [
        "method"=> "Méthode d'authentification",
        "password"=> "Mot de passe",
    ],
    "errors"=> [
        'invalid_passkey'=> "Impossible de vous authentifié avec cette clé d'accès.", 
    ],
];
