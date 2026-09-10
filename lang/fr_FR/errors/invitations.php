<?php

return [
    'link_unavailable'=> [
        'title'=> "Le lien d'invitation n'existe pas."
    ],
    'already_status'=> [
        'title'=> "L'invitation est déjà \":status\".",
        'description'=> "L'invitation pour \":email\" est déjà :status."
    ],
    'unavailable_revoke'=> [
        'title'=> "Impossible de révoquer cette invitation.",
        'description'=> "Vous ne pouvez pas révoquer une invitation déjà accepté ou expiré."
    ],
    'unavailable_reactive'=> [
        'title'=> "Impossible de réactiver cette invitation.",
        'description'=> "Vous ne pouvez pas réactiver une invitattion déjà accepté, expiré ou en attente."
    ],
    'unavailable_force_delete'=> [
        'title'=> "Impossible de supprimer définitivement cette invitation.",
        'description'=> "Pour supprimer définitivement une invitation, il faut que cette dernière soit en corbeille."
    ],
    'unavailable_restore'=> [
        'title'=> "Impossible de restaurer cette invitation.",
        'description'=> "Impossible de restauré l'invitation pour \":email\", assurez-vous que l'invitation est en corbeille."
    ]
];
