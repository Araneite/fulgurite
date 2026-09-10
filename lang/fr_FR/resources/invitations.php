<?php

return [
    'status'=> [
        'pending'=> 'En attente',
        'accepted'=> 'Accepté',
        'revoked'=> 'Révoqué',
        'expired'=> 'Expiré'
    ],
    
    'messages'=> [
        'sent'=> 'Invitation envoyée.',
        'link_created'=> "Lien d'invitation généré.",
        'mail_sent'=> "Le mail a bien été envoyé à \":email\".",
        'accepted'=> "Invitation acceptée. Vous pouvez maintenant vous connecter.",
        
        // Errors
        'link_unavailable'=> "Le lien d'invitation n'existe pas.",
        
        // Actions
        'created'=> [
            'title'=> "Invitations créé.",
            'description'=> "L'invitation pour \":email\" à bien éte créé."
        ],
        'revoked'=> [
            'title'=> "Invitation révoquée.",
            'description'=> "L'invitation pour \":email\" a bien éte révoquée. L'utilisateur ne pourra plus créer son compte avec le lien reçu."
        ],
        'reactive'=> [
            'title'=> "Invitation réactivée.",
            'description'=> "L'invitation pour \":email\" a bien été réactivée. Le lien de création de compte est à nouveau fonctionnel."
        ],
        'restored'=> [
            'title'=> "Invitation restaurée.",
            'description'=> "L'invitation pour \":email\"a bien été restaurée."
        ],
        'deleted'=> [
            'title'=> "Invitation déplacer en corbeille",
            'description'=> "L'invitation pour \":email\" a bien éte mis en corbeille, il sera supprimé définitivement dans :retention_days jours."
        ],
        'force_deleted'=> [
            'title'=> "Invitation supprimé définitivement",
            'description'=> "L'invitation pour \":email\" a bien été supprimée définitivement, toutes les données liées ont bien été supprimée également."
        ],
        'updated'=> [
            'renew'=> [
                'title'=> "La durée de validité de l'invitation modifié.",
                'description'=> "La date d'expiration de l'invitation à bien été modifié pour :date."
            ], 
            'remove_expiration'=> [
                'title'=> "Date d'expiration supprimé",
                'description'=> "La date d'expiration a bien été supprimé pour l'invitation de \":email\"."
            ]
        ]
    ],
    
    'accept'=> [
        'title'=> "Finaliser votre inscription",
        'description'=> "Votre compte sera créer pour l'adresse :email",
        'submit'=> "Créer le compte"
    ],
    
    'modals'=> [
        'expiration_renew'=> [
            'title'=> "Renouveler la date d'expiration",
            'description'=> "Ajouter une durée supplémentaire à la date d'expiration."
        ]
    ],
    
    'validation'=> [
        'renew_amount'=> [
            'integer'=> "La valeur d'ajout de jours d'expiration doit être un entier.",
            'min'=> "La valeur d'ajout de jours d'expiration doit être supérieur à :value.",
            'max'=> "La valeur d'ajout de jours d'expiration doit être inférieur à :value."
        ],
        'expiration_date'=> [
            'date'=> "La nouvelle date d'expiration doit être une date valide.",
            'after'=> "La nouvelle date d'expiration doit être futur."
        ]
    ]
];
