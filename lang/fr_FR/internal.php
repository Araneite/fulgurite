<?php

return [
    "errors"=> [
        "unauthenticated"=> [
            "message"=> "Vous n'êtes pas connecté.",
            "description"=> "Vous devez être connecté pour accéder à cette page."
        ],
        "unauthorized"=> [
            "message"=> "Vous n'avez pas les droits nécessaires.",
            "description"=> "Vous ne possédez pas les permissions nécessaire pour :end_sentence."
        ],
        "login"=> [
            "message"=> "Connexion échouée.",
            "description"=> "Email ou mot de passe incorrect."
        ],
        "validation"=> [
            "message"=> "La validation des données à échoué",
            "description"=> "Une erreur est survenu lors de la validation des données, pour plus de détail voir les erreurs."
        ],
        "users"=> [
            "404"=> [
                "message"=> "L'utilisateur n'a pas été trouvé.",
                "description"=> "L'utilisateur avec `:user` n'a pas été trouvé."
            ],
            "reused_password"=> [
                "message"=> "Le mot de passe est le même que l'actuel.",
                "description"=> "Le nouveau mot de passe ne peut pas être le même que le mot passe actuellement utilisé."
            ],
            "reset_password"=> [
                "message"=> "La réinitialisation du mot de passe à échouée.",
                "description"=> "La réinitialisation du mot de mot a malheureusement échoué, veuillez réitérer votre demande de réinitialisation ou contacter un administrateur."
            ],
            "not_trashed"=> [
                "message"=> "L'utilisateur n'est pas supprimé.",
                "description"=> "L'utilisateur `:user` n'est pas supprimé, impossible de le restauré."
            ],
            "confirmation"=> [
                "message"=> "L'action n'a pas été confirmé.",
                "description"=> "Cette action sensible nécessite une confirmation, merci de bien vouloir confirmer votre action."
            ],
            "account_disabled"=> [
                "message"=> "L'utilisateur est bloqué.",
                "description"=> "L'utilisateur que vous souhaité utilisé est actuellement bloqué, vous ne pouvez plus l'utiliser jusqu'au déblocage. Veuillez contacter un administrateur."
            ]
        ],
        "pagination"=> [
            "404"=> [
                "message"=> "La page demandé n'existe pas.",
                "description"=> "La page :page n'existe pas. La dernière page disponible est :last_page."
            ]
        ],
        "logs"=> [
            "404"=> [
                "message"=> "Ce journal d'activité n'a pas été trouvé.",
                "description"=> "Le journal avec id `:id` n'a pas été trouvé."
            ], 
            "invalid_date"=> [
                "message"=> "La date est invalide.",
                "description"=> "La date choisie :date est invalide.",
            ]
        ]
    ],
    "success"=> [
        "login"=> [
            "message"=> "Vous êtes bien connecté.",
            "description"=> "Vous êtes connecté en tant que :user.",
        ],
        "logout"=> [
            "message"=> "Vous avez été déconnecté, à bientôt !",
            "description"=> "Le compte `:user` à bien été déconnecté."
        ],
        "users"=> [
            "index"=> [
                "message"=> "La liste des utilisateurs a correctement bien été chargée.",
            ],
            "store"=> [
                "message"=> "L'utilisation à bien été créé.",
                "description"=> "Le nouvel utilisateur `:user` à bien été enregistrer."
            ],
            "show"=> [
                "message"=> "L'utilisateur à bien été trouvé.",
                "description"=> "L'utilisateur `:user` à bien été retrouvé."
            ],
            "update"=> [
                "message"=> "L'utilisateur à bien été modifié.",
                "descriptions"=> "L'utilisateur `:user` à bien été modifié."
            ],
            "change"=> [
                "status"=> [
                    "message"=> "Le statut de l'utilisateur à bien été modifié.",
                    "description"=> "La connexion de l'utilisateur `:user` à bien été :status."
                ]
            ],
            "destroy"=> [
                "message"=> "L'utilisateur à bien été supprimé.",
                "description"=> "L'utilisation `:user` à bien été supprimé."
            ],
            "restore"=> [
                "message"=> "L'utilisateur à bien été restauré.",
                "description"=> "L'utilisateur `:user` à bien été restauré."
            ],
            "reset_password"=> [
                "message"=> 'Le mot de passe à bien été réinitialiser.',
                "description"=> "Le mot de passe de l'utilisateur `:user` à bien été modifié."
            ],
            "get_profile"=> [
                "message"=> "Le profil de l'utilisateur à bien été trouvé",
                "description"=> "Le profil de l'utilisateur `:user` a bien été chargé."
            ],
            "update_profile"=> [
                "message"=> "Le profil utilisateur à bien été mis à jour.",
                "description"=> "Le profil de l'utilisateur `:user` a bien été mis à jour."
            ],
            "destroy_profile"=> [
                "message"=> "L'utilisateur à bien été supprimé.",
                "description"=> "L'utilisateur `:user` à bien été supprimé. Les données vous concernant sont tout de même conservées pour la durée de 1 an comme précisé dans la politique de confidentialité. Vous pouvez vous opposez à cette sauvegarde en contactant un administrateur."
            ],
            "force_delete"=> [
                "message"=> "L'utilisateur à définitivement été supprimé.",
                "description"=> "L'utilisateur `:user` à bien été définitivement supprimé. Et toutes les données qui y sont lié ont correctement été supprimées."
            ]
        ],
        "logs"=> [
            "index"=> [
                "message"=> "La liste des logs a bien été chargé."
            ],
            "show"=> [
                "message"=> "Le journal à bien été trouvé.",
                "description"=> "Le journal avec id :id à bien été retrouvé."
            ]
        ]
    ],
    "actions"=> [
        "users"=> [
            "show"=> "consulter un utilisateur",
            "list"=> "voir la liste des utilisateurs",
            "create"=> "créer un nouveau utilisateur",
            "edit"=> "modifier l'utilisateur",
            "delete"=> "supprimer l'utilisateur",
            "invite"=> "inviter un utilisateur",
            "suspend"=> "suspendre un utilisateur",
            "restore"=> "restaurer l'utilisateur",
            "force_delete"=> "supprimer définitivement l'utilisateur",
            "status"=> [
                "active"=> "activé",
                "inactive"=> "désactivé"
            ]
        ],
        "logs"=> [
            "list"=> "voir la liste de journaux",
            "show"=> "consulter un journal"
        ]
    ],
    "info"=> [
        "forgot_password"=> [
            "message"=> "Si un compte utilisateur existe, un email à été envoyé.",
            "description"=> "Si un compte associé à l'email `:email`, nous avons envoyé un email avec les informations de réinitialisation du mot de passe.",
        ],
    ]
];
