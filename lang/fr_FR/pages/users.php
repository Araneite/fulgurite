<?php

return [
    'title'=> "Utilisateurs",
    'description'=> "Liste des utilisateurs",
    
    'tables'=> [
        'users'=> [
            'name'=> "Liste des utilisateurs",
            'columns'=> "Colonnes",
            'header'=> [
                'username'=> "Utilisateur",
                'contact'=> "Contact",
                'active'=> "Statut",
                'admin_notes'=> "Notes",
                'updated_at'=> "Dernière modification",
            ],
            'cell'=> [
                'username'=> [
                    'badge'=> 'Vous',
                ],
                'active'=> [
                    'true'=> "Actif",
                    'false'=> "Bloqué",
                ],
                'admin_notes'=> [
                    'expand'=> "Voir la note complète",
                    'collapse'=> "Réduire la note",
                ],
            ],
        ],
        'invitations'=> [
            'name'=> "Invitations",
            'columns'=> "Colonnes",
            'header'=> [
                'email'=> "Email",
                'username'=> "Nom d'utilisateur",
                'status'=> "Statut",
                'expires_at'=>  "Expire le",
                'created_at'=> "Créer le",
                'invited_by'=> "Invité par"
            ],
            'cell'=> [
                'username'=> [
                    'undefined'=> 'Non défini',
                ],
                'status'=> [
                    'pending'=> "En attente",
                    'accepted'=> "Accepté",
                    'revoked'=> 'Révoqué',
                    'expired'=> 'Expiré',
                ],
                'expires_at'=> [
                    'no_date'=> "Pas de date d'expiration",
                ],
                'invited_by'=> [
                    'unknown'=> 'Inconnu'
                ]
            ],
        ]
    ],

    'user_actions'=> [
        'activate'=> "Activer le compte",
        'create'=> "Création complète d'un utilisateur",
        'copy_email'=> "Copier l'adresse email",
        'edit'=> "Modifier l'utilisateur",
        'deactivate'=> "Désactiver le compte",
        'delete'=> "Supprimer",
        'fast_create'=> "Création rapide d'un utilisateur",
        'fast_edit'=> "Modification rapide de l'utilisateur",
        'force_delete'=> "Supprimer définitivement le compte",
        'invite'=> "Inviter un utilisateur",
        'invite_by_email'=> "Inviter par email",
        'invite_by_link'=> "Inviter par lien",
        'reset_password'=> "Réinitialiser le mot de passe",
        'revoke_sessions'=> "Révoquer les connexion",
        'view_details'=> "Voir la fiche",
        'verbs'=> [
            'create'=> "Créer",
            'edit'=> "Modifier",
        ],
    ],

    'drawer'=> [
        'title'=> "Détails de l'utilisateur",
        'description'=> "Informations principales de l'utilisateur",
        'open_full_page'=> "Ouvrir la fiche complète",
        'loading'=> "Chargement de l'utilisateur…",
        'unknown_status'=> "Statut inconnu",
        'not_provided'=> "Non renseigné",
        'sections'=> [
            'contact'=> "Coordonnées",
            'information'=> "Informations",
            'administration'=> "Administration",
        ],
        'fields'=> [
            'id'=> "Identifiant",
            'created_at'=> "Créé le",
            'updated_at'=> "Modifié le",
            'roles'=> "Rôles",
            'last_login'=> "Dernière connexion",
            'expire_at'=> "Expiration",
            'suspended_until'=> "Suspension",
            'suspension_reason'=> "Motif de suspension",
            'admin_notes'=> "Notes administratives",
        ],
        'errors'=> [
            'title'=> "Chargement impossible",
            'unknown'=> "Une erreur inattendue est survenue.",
            'load'=> "Impossible de charger l'utilisateur (:status).",
        ],
        'actions'=> [
            'view_details'=> "Voir la fiche",
            'retry'=> "Réessayer",
            'close'=> "Fermer",
        ],
    ],
    
    'invitation_actions'=> [
        'reactive'=> "Réactiver l'invitation",
        'renew_expiration'=> "Renouveler la validité",
        'remove_expiration'=> "Supprimer la date d'expiration",
        'send_email'=> "Envoyer l'email d'invitation",
        'resend_email'=> "Renvoyer le mail d'invitation",
        'restore'=> "Restaurer l'invitation",
        'revoke'=> "Révoquer l'invitation",
        'delete'=> 'Supprimer',
        'force_delete'=> 'Supprimer définitivement l\'invitation',
    ],
    
    'tabs'=> [
        'data'=> [
            'title'=> "Informations",
            'description'=> "les informations de l'utilisateur.",
        ],
        'security'=> [
            'title'=> "Sécurité",
            'description'=> " le mot de passe et les accès."
        ],
        'admin'=> [
            'title'=> 'Administration',
            'description'=> "Administrer le compte utilisateur."
        ],
        'settings'=> [
            'title'=> "Paramètres",
            'description'=> ' les paramètres utilisateurs.'
        ]
    ],
    
    'modals'=> [
        'discard'=> [
            'title'=> "Modification non sauvegardées",
            'description'=> "Des champs ont été modifiés. Si vous fermez cette fenêtre, les données saisie seront perdues." 
        ],
        'invite'=> [
            'title'=> "Inviter un utilisateur",
            'description'=> "Remplissez le formulaire pour invité un utilisateur à gérer vos sauvegardes.",
            'mode'=> [
                'email'=> 'par email',
                'link'=> 'par lien',
            ]
        ],
        'bulk_actions'=> [
            'delete'=> [
                'title'=> "Confirmer la mise en corbeille",
                'description'=> "Vous êtes sur le point de mettre en corbeille les utilisateurs sélectionnés.",
                'action_description'=> "Les utilisateurs mis en corbeille pourront être restaurés avant leur suppression définitive.",
                'confirm'=> "Confirmer et mettre à la corbeille",
                'ignored_title'=> ":count utilisateur(s) ignoré(s)",
                'counters'=> [
                    'selected'=> "Sélectionnés",
                    'actionable'=> "Traités",
                    'ignored'=> "Ignorés",
                ],
                'ignored_reasons'=> [
                    'already_trashed'=> "déjà dans la corbeille.",
                    'missing_permission'=> "ne peut/peuvent pas être mis en corbeille avec vos permissions actuelles.",
                    'current_user'=> "correspond à votre propre compte et ne peut pas être traité par une action groupée.",
                ],
            ],
            'force_delete'=> [
                'title'=> "Confirmer la suppression définitive",
                'description'=> "Vous êtes sur le point de supprimer définitivement les utilisateurs sélectionnés.",
                'action_description'=> "Cette action est définitive. Les utilisateurs supprimés ne pourront plus être restaurés.",
                'confirm'=> "Confirmer et supprimer définitivement",
                'ignored_title'=> ":count utilisateur(s) ignoré(s)",
                'counters'=> [
                    'selected'=> "Sélectionnés",
                    'actionable'=> "Traités",
                    'ignored'=> "Ignorés",
                ],
                'ignored_reasons'=> [
                    'not_trashed'=> "n'est/ne sont pas dans la corbeille.",
                    'missing_permission'=> "ne peut/peuvent pas être restauré(s) avec vos permissions actuelles.",
                    'current_user'=> "correspond à votre propre compte et ne peut pas être traité par une action groupée.",
                ],
            ],
            'restore'=> [
                'title'=> "Confirmer la restauration",
                'description'=> "Vous êtes sur le point de restaurer les utilisateurs sélectionnés.",
                'action_description'=> "Les utilisateurs restaurés redeviendront visibles dans la liste active.",
                'confirm'=> "Confirmer et restaurer",
                'ignored_title'=> ":count utilisateur(s) ignoré(s)",
                'counters'=> [
                    'selected'=> "Sélectionnés",
                    'actionable'=> "Traités",
                    'ignored'=> "Ignorés",
                ],
                'ignored_reasons'=> [
                    'not_trashed'=> "n'est/ne sont pas dans la corbeille.",
                    'missing_permission'=> "ne peut/peuvent pas être restauré(s) avec vos permissions actuelles.",
                    'current_user'=> "correspond à votre propre compte et ne peut pas être traité par une action groupée.",
                ],
            ],
        ],
    ],
    
    'context_menu'=> [
        'sections'=> [
            'copy'=> "Copier",
            'inspect'=> "Consulter"
        ],
        'copy_email'=> "Copier l'email",
        'copy_identifier' => "Copier l'identifiant",
        'copy_phone' => "Copier le téléphone",
        'view_roles' => "Voir les rôles",
        'view_logs' => "Voir dans les logs",
        'roles' => [
            'title' => "Rôles de l'utilisateur",
            'empty' => "Aucun rôle attribué.",
        ],
        
        'copy_link'=> "Copier le lien d'invitation"
    ],
    
    'toasts'=> [
        'copy_email'=> [
            'title'=> "Adresse email copié."
        ],
        'copy_identifier'=> [
            'title'=> "Identifiant copié."
        ],
        'copy_phone'=> [
            'title'=> "Numéro de téléphone copié."
        ],
        'invite_delete'=> [
            'title'=> "Invitation supprimé déplacé en corbeille."
        ],
        'invite_link_copy'=> [
            'title'=> "Lien d'invitation copié dans le presse-papier."
        ]
    ]
];
