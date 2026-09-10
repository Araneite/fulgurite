<?php

return [
    'singular' => 'utilisateur',
    'plural' => 'utilisateurs',
    
    'fields'=> [
        'id' => 'Id',
        'username' => "Nom d'utilisateur",
        'email' => 'Adresse email',
        'first_name'=> "Prénom",
        'last_name' => "Nom",
        'job_title' => "Poste",
        'phone'=> [
            'label'=> "Téléphone",
            'extension'=> "Extension",
            'number'=> "Numéro de téléphone",
        ],
        'password'=> "Mot de passe",
        'password_confirmation'=> "Confirmation du mot de passe",
        'active'=> "Statut",
        'admin_notes'=> "Notes administrateur",
        'suspended_until'=> "Bloqué jusqu'au",
        'suspended_reason'=> "Motif de suspension",
        'expire_at'=> "Date d'expiration du compte",
        'roles'=> "Roles",
        'forced_actions'=> "Actions forcées à la prochaine connexion",
        'preferred_locale'=> "Langue de l'interface",
        'preferred_timezone'=> "Fuseau horaire de l'interface",
        'preferred_start_page'=> "Page d'accueil du dashboard",
        'updated_at'=> "Dernière modification",
    ],
    
    'validation'=> [
        'username'=> [
            'unique'=> "Ce nom d'utilisateur est déjà utilisé.",
        ],
        'email'=> [
            'unique'=> "Cette adresse email est déjà utilisée.",
        ],
        'password'=> [
            'confirmed'=> "Les mots de passes ne correspondent pas.",
            'reused'=> "Le nouveau mot de passe doit être différent de l'ancien.",
            'length'=> "8 caractères minimum.",
            'number'=> "1 chiffre minimum.",
            'lowercase'=> "1 minuscule minimum.",
            'uppercase'=> "1 majuscule minimum.",
            'symbol'=> "1 caractère spécial."
        ],
        'roles'=> [
            'exists'=> "Un des rôles sélectionnés est invalide."
        ],
    ],
    
    'messages'=> [
        'created'=> [
            'title'=> "Utilisateur créé.",
            'description'=> "L'utilisateur \":user\" à bien éte créé."
        ],
        'updated'=> [
            'title'=> "Utilisateur modifié.",
            'description'=> "L'utilisateur \":user\" à été modifié avec succès."
        ],
        'deleted'=> [
            'title'=> "Utilisateur déplacer en corbeille",
            'description'=> "L'utilisateur \":user\" a bien éte mis en corbeille, il sera supprimé définitivement dans :retention_days jours."
        ],
        'data_updated'=> "Information utilisateur enregistrées.",
        'security_updated'=> "Paramètres de sécurité utilisateur enregistré.",
        'admin_updated'=> "Information d'administration du compte enregistré."
    ],
    
    'confirmations'=> [
        'delete'=> [
            'title'=> "Êtes-vous sûr de vouloir supprimer cet utilisateur ?",
            'description'=> "Vous êtes sur le le point de supprimer l'utilisateur \":user\", souhaitez vous continuer ?"
        ]
    ],
    
    'errors'=> [
        'passkey_invalid'=> "La clé d'accès n'a pas pu être vérifiée."
    ]
];
