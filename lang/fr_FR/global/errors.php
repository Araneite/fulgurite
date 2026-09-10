<?php
 return [
     'unauthorized'=> "Vous n'avez pas la permission de :action le modèle :model",
     
     'inputs'=> [
         'email_invalid'=> "Adresse email invalide.",
         'string'=> "La valeur attendu doit être une chaine de caractères",
         'int'=> "La valeur attendu doit être un entier",
     ],

     'permissions' => [
         'invalid_permission' => "La permission demandée est invalide.",
         'missing_permission' => "Vous n'avez pas la permission `:permission` nécessaire pour :action le modèle :model.",
         'user_role_too_high' => "Vous ne pouvez pas :action cet utilisateur. L'utilisateur `:target` possède le rôle `:role`, qui est supérieur ou égal à vos droits.",
         'role_too_high' => "Vous ne pouvez pas :action ce rôle. Le rôle `:role` est supérieur ou égal à vos droits.",
     ],

     'invitations' => [
         'unavailable' => [
             'not_found' => [
                 'title' => 'Invitation introuvable',
                 'description' => 'Le lien utilisé ne correspond à aucune invitation active.',
                 'alert_title' => 'Lien invalide',
                 'alert_description' => 'Cette invitation n’existe pas ou a été supprimée.',
                 'login_link' => 'Retour à la connexion',
                 'footer' => 'Si vous pensez qu’il s’agit d’une erreur, demandez une nouvelle invitation à un administrateur.',
             ],

             'expired' => [
                 'title' => 'Invitation expirée',
                 'description' => 'Ce lien d’invitation n’est plus valide.',
                 'alert_title' => 'Délai dépassé',
                 'alert_description' => 'Demandez une nouvelle invitation pour finaliser votre accès.',
                 'login_link' => 'Retour à la connexion',
                 'footer' => 'Les invitations expirées ne peuvent pas être réactivées depuis ce lien.',
             ],

             'accepted' => [
                 'title' => 'Invitation déjà utilisée',
                 'description' => 'Cette invitation a déjà permis de créer un compte.',
                 'alert_title' => 'Compte déjà créé',
                 'alert_description' => 'Vous pouvez vous connecter avec le compte associé à cette invitation.',
                 'login_link' => 'Se connecter',
                 'footer' => 'Si vous ne retrouvez pas votre accès, utilisez la récupération de mot de passe ou contactez un administrateur.',
             ],

             'revoked' => [
                 'title' => 'Invitation révoquée',
                 'description' => 'Cette invitation a été annulée par un administrateur.',
                 'alert_title' => 'Accès annulé',
                 'alert_description' => 'Ce lien ne permet plus de créer un compte.',
                 'login_link' => 'Retour à la connexion',
                 'footer' => 'Contactez un administrateur si vous avez toujours besoin d’un accès.',
             ],
         ],
     ],
 ];
