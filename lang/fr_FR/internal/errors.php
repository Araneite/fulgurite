<?php

return [
    "unauthenticated"=> [
        "message"=> "Vous n'êtes pas connecté.",
        "detail"=> "Vous devez être connecté pour accéder à cette ressource."
    ],
    "unauthorized"=> [
        "message"=> "Vous n'avez pas les droits d'accéder à cette ressource.",
        "detail"=> "Vous ne possédez pas les permissions nécessaires pour :end_sentence."
    ],
    "login"=> [
        "message"=> "Identifiant ou mot de passe incorrect.",
        "detail"=> "Vérifier vos identifiants de connexion et réessayer."
    ],
    "validation"=> [
        "message"=> "La validation des données à échoué.",
        "detail"=> "Une erreur est survenu lors de la validation des données, pour plus de détails voir les erreurs."
    ],
    "404"=> [
        "message"=> "La ressource n'a pas été trouvé.",
        "detail"=> "La ressource `:model` n'a pas été trouvé avec `:requested`."
    ],
    "not_trashed"=> [
        "message"=> "La ressource n'est pas supprimée.",
        "detail"=> "La ressource `:model` avec `:requested` n'est pas supprimée, impossible de la restaurée."
    ],
    "confirmation"=> [
        "message"=> "La requête n'a pas été confirmé.",
        "detail"=> "Cette action est une action sensible, et nécessite donc une confirmation. Merci de confirmer votre action."
    ],
    "page_not_found"=> [
        "message"=> "La page demandé n'existe pas.",
        "detail"=> "La page `:page` n'existe pas. La dernière page disponible est `:last_page`."
    ],
    "invalid_date"=> [
        "message"=> "La date n'est pas valide.",
        "detail"=> "La date `:date` n'est pas valide."
    ],
    "disabled"=> [
        "message"=> "Ressource bloqué.",
        "detail"=> "La ressource `:model` pour `:requested` est actuellement bloqué. Impossible de l'utiliser jusqu'au déblocage. En cas de soucis, veuillez contacter un administrateur."
    ],
    "reset_password"=> [
        "message"=> "Réinitialisation du mot de passe échouée.",
        "detail"=> "La réinitialisation du mot de passe à malheureusement échouée. Veuillez réitérer votre demande ou contacter un administrateur."
    ],
    "reused_password"=> [
        "message"=> "Mot de passe similaire à l'ancien.",
        "detail"=> "Le nouveau mot de passe ne peut pas être le même que le mot de passe actuel."
    ]
];
